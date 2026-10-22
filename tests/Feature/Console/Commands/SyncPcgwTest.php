<?php

use App\Enums\Support;
use App\Enums\SyncStatus;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Models\SyncRun;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config([
        'pcgw.bot_username' => 'RenderdexTest@Renderdex',
        'pcgw.bot_password' => 'test-bot-password',
        'pcgw.page_size' => 2,
    ]);
});

/**
 * Simula la API: 5 páginas de la wiki repartidas en lotes de 2 (offsets 0, 2 y 4).
 *
 * @param  array<int, mixed>  $overrides  Respuestas que reemplazan al lote de un offset.
 */
function fakePcgwApi(array $overrides = []): void
{
    Http::preventStrayRequests();
    Sleep::fake();

    $batches = [
        0 => jsonFixture('pcgw/games-page-1'),
        2 => jsonFixture('pcgw/games-page-2'),
        4 => jsonFixture('pcgw/games-page-3'),
    ];

    Http::fake(function (Request $request) use ($batches, $overrides) {
        return match ($request['action']) {
            'query' => Http::response(jsonFixture('pcgw/login-token')),
            'login' => Http::response(jsonFixture('pcgw/login-success')),
            'cargofields' => Http::response(jsonFixture('pcgw/cargofields-video')),
            'cargoquery' => match (true) {
                $request['fields'] === 'COUNT(*)=Total' => Http::response(jsonFixture('pcgw/games-count')),
                array_key_exists($request['offset'], $overrides) => $overrides[$request['offset']],
                default => Http::response($batches[$request['offset']] ?? ['cargoquery' => []]),
            },
        };
    });
}

/**
 * @return list<int>
 */
function requestedOffsets(): array
{
    return Http::recorded()
        ->map(fn (array $pair) => $pair[0])
        ->filter(fn (Request $request) => $request['action'] === 'cargoquery' && isset($request['offset']))
        ->map(fn (Request $request) => $request['offset'])
        ->values()
        ->all();
}

it('syncs every page of the wiki in batches', function () {
    fakePcgwApi();

    $this->artisan('pcgw:sync')
        ->expectsOutputToContain('Synced 5 pages from PCGamingWiki.')
        ->assertSuccessful();

    expect(Game::pluck('title')->sort()->values()->all())
        ->toBe(['Dota 2', "Mirror's Edge", 'Titan Quest', 'War Thunder', 'World of Warcraft']);
    expect(requestedOffsets())->toBe([0, 2, 4]);
    expect(SyncRun::sole())
        ->status->toBe(SyncStatus::Completed)
        ->pages_processed->toBe(5)
        ->last_offset->toBe(5)
        ->finished_at->not->toBeNull()
        ->error->toBeNull();
});

it('stores the precomputed classification and the raw technology text', function () {
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    expect(Game::firstWhere('pcgw_page_id', 45))
        ->title->toBe("Mirror's Edge")
        ->dlss->toBe(Support::Yes)
        ->dlss_fg->toBe(Support::Yes)
        ->dlss_mfg->toBe(Support::Yes)
        ->fsr->toBe(Support::No)
        ->xess->toBe(Support::Yes)
        ->xess_fg->toBe(Support::No)
        ->ray_tracing->toBe(Support::Yes)
        ->upscaling_tech->toBe('DLSS 4.5,NIS,TAAU,XeSS 2.1')
        ->frame_gen_tech->toBe('DLSS Frame Generation,DLSS Multi-Frame Generation');
    expect(Game::firstWhere('title', 'Titan Quest'))
        ->dlss->toBe(Support::Unknown)
        ->ray_tracing->toBe(Support::No)
        ->upscaling_tech->toBeNull();
});

it('links every steam app id listed on a page to its game', function () {
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    $dota = Game::firstWhere('title', 'Dota 2');
    $titanQuest = Game::firstWhere('title', 'Titan Quest');
    expect($dota->steamAppids()->pluck('steam_appid')->sort()->values()->all())->toBe([570, 652720, 1241930]);
    expect($titanQuest->steamAppids()->pluck('steam_appid')->sort()->values()->all())->toBe([4540, 4550]);
    expect(Game::firstWhere('title', 'World of Warcraft')->steamAppids()->count())->toBe(0);
});

it('updates existing games and replaces app ids that the wiki no longer lists', function () {
    $mirrorsEdge = Game::factory()->create(['pcgw_page_id' => 45, 'title' => 'Old title', 'dlss' => Support::No]);
    GameSteamAppid::factory()->for($mirrorsEdge)->create(['steam_appid' => 999999]);
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    expect($mirrorsEdge->refresh())
        ->title->toBe("Mirror's Edge")
        ->dlss->toBe(Support::Yes);
    expect($mirrorsEdge->steamAppids()->pluck('steam_appid')->all())->toBe([17410]);
    $this->assertDatabaseMissing('game_steam_appids', ['steam_appid' => 999999]);
    expect(Game::count())->toBe(5);
});

it('moves an app id to the page that lists it in the latest sync', function () {
    $otherGame = Game::factory()->create();
    GameSteamAppid::factory()->for($otherGame)->create(['steam_appid' => 17410]);
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    $this->assertDatabaseHas('game_steam_appids', [
        'steam_appid' => 17410,
        'game_id' => Game::firstWhere('pcgw_page_id', 45)->id,
    ]);
});

it('resumes from the last offset of an interrupted run', function () {
    $interrupted = SyncRun::factory()->interrupted(lastOffset: 2)->create();
    fakePcgwApi();

    $this->artisan('pcgw:sync')
        ->expectsOutputToContain('Resuming from page 2.')
        ->assertSuccessful();

    expect(requestedOffsets())->toBe([2, 4]);
    expect(Game::count())->toBe(3);
    expect($interrupted->refresh())
        ->status->toBe(SyncStatus::Failed)
        ->error->toBe('Interrupted before finishing.');
    expect(SyncRun::latest('id')->first())
        ->status->toBe(SyncStatus::Completed)
        ->pages_processed->toBe(3)
        ->last_offset->toBe(5);
});

it('resumes from the last offset of a failed run', function () {
    SyncRun::factory()->failed()->create(['last_offset' => 4]);
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    expect(requestedOffsets())->toBe([4]);
});

it('starts from the first page after a completed run', function () {
    SyncRun::factory()->create(['last_offset' => 5, 'pages_processed' => 5]);
    fakePcgwApi();

    $this->artisan('pcgw:sync')->assertSuccessful();

    expect(requestedOffsets())->toBe([0, 2, 4]);
});

it('starts from the first page with --fresh even when a run is unfinished', function () {
    SyncRun::factory()->interrupted(lastOffset: 2)->create();
    fakePcgwApi();

    $this->artisan('pcgw:sync --fresh')->assertSuccessful();

    expect(requestedOffsets())->toBe([0, 2, 4]);
    expect(Game::count())->toBe(5);
});

it('stops after the number of pages given with --limit', function () {
    fakePcgwApi();

    $this->artisan('pcgw:sync --limit=3')
        ->expectsOutputToContain('Synced 3 pages from PCGamingWiki.')
        ->assertSuccessful();

    expect(Game::count())->toBe(3);
    expect(requestedOffsets())->toBe([0, 2]);
    Http::assertSent(fn (Request $request) => $request['action'] === 'cargoquery'
        && ($request['offset'] ?? null) === 2
        && $request['limit'] === 1);
});

it('records the failure and keeps the progress made before it', function () {
    fakePcgwApi(overrides: [2 => Http::response('Bad Gateway', 502)]);

    $this->artisan('pcgw:sync')
        ->expectsOutputToContain('Sync failed after 2 pages')
        ->assertFailed();

    expect(Game::count())->toBe(2);
    expect(SyncRun::sole())
        ->status->toBe(SyncStatus::Failed)
        ->last_offset->toBe(2)
        ->pages_processed->toBe(2)
        ->error->toContain('HTTP 502')
        ->finished_at->not->toBeNull();
});

it('fails without saving anything when the bot login is rejected', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-failed')),
    ]);

    $this->artisan('pcgw:sync')->assertFailed();

    expect(Game::count())->toBe(0);
    expect(SyncRun::sole())
        ->status->toBe(SyncStatus::Failed)
        ->error->toContain('login failed')
        ->error->not->toContain('test-bot-password');
});

it('is scheduled to run once a day without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'pcgw:sync'));

    expect($event)->not->toBeNull()
        ->expression->toBe('0 4 * * *')
        ->withoutOverlapping->toBeTrue();
});
