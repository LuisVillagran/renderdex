<?php

use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config(['services.steamgriddb.key' => null]);
    Http::preventStrayRequests();
    Sleep::fake();
});

/**
 * Juego con sus AppIDs en el orden de la wiki.
 */
function gameWithAppids(int ...$appids): Game
{
    $game = Game::factory()->create();

    foreach ($appids as $appid) {
        GameSteamAppid::factory()->for($game)->create(['steam_appid' => $appid]);
    }

    return $game;
}

function fakeSteamStore(): void
{
    Http::fake(['api.steampowered.com/IStoreBrowseService/GetItems/v1/*' => Http::response(jsonFixture('steam/store-items'))]);
}

it('saves the steam store artwork of each game', function () {
    fakeSteamStore();
    $game = gameWithAppids(3059520);

    $this->artisan('covers:sync')
        ->expectsOutputToContain('Checked Steam artwork for 1 games.')
        ->expectsOutputToContain('SteamGridDB skipped')
        ->assertSuccessful();

    expect($game->fresh())
        ->steam_artwork->toMatchArray(['cover' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/660d69e1717e93fcabc23f85100d123892ace85c/library_capsule.jpg?t=1786646076'])
        ->steam_artwork_synced_at->not->toBeNull();
});

it('prefers the first app id that has a vertical cover', function () {
    fakeSteamStore();
    $game = gameWithAppids(4496490, 242050);

    $this->artisan('covers:sync')->assertSuccessful();

    expect($game->fresh()->steam_artwork['cover'])->toContain('/apps/242050/library_600x900.jpg');
});

it('keeps whatever artwork there is when no app id has a cover', function () {
    fakeSteamStore();
    $soundtrackOnly = gameWithAppids(4496490);
    $unknown = gameWithAppids(9999990);

    $this->artisan('covers:sync')->assertSuccessful();

    expect($soundtrackOnly->fresh()->steam_artwork)->toMatchArray(['cover' => null, 'header' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/4496490/aa11/header.jpg?t=1786000000']);
    expect($unknown->fresh())->steam_artwork->toBeNull()->steam_artwork_synced_at->not->toBeNull();
});

it('does not ask again for games checked this week unless --fresh', function () {
    fakeSteamStore();
    gameWithAppids(3059520)->update(['steam_artwork_synced_at' => now()->subDay()]);

    $this->artisan('covers:sync')->expectsOutputToContain('Checked Steam artwork for 0 games.')->assertSuccessful();
    Http::assertNothingSent();

    $this->artisan('covers:sync --fresh')->expectsOutputToContain('Checked Steam artwork for 1 games.')->assertSuccessful();
    Http::assertSentCount(1);
});

it('processes only --limit games', function () {
    fakeSteamStore();
    $games = collect([gameWithAppids(3059520), gameWithAppids(242050), gameWithAppids(4496490)]);

    $this->artisan('covers:sync --limit=2')->expectsOutputToContain('Checked Steam artwork for 2 games.')->assertSuccessful();

    expect($games->map(fn (Game $game) => $game->fresh()->steam_artwork_synced_at !== null)->all())->toBe([true, true, false]);
});

it('splits the app ids into requests the store accepts', function () {
    fakeSteamStore();
    gameWithAppids(...range(1, 250));

    $this->artisan('covers:sync')->assertSuccessful();

    Http::assertSentCount(2);
});

it('stops with a failure and leaves the game pending when steam is down', function () {
    Http::fake(['api.steampowered.com/*' => Http::response('', 503)]);
    $game = gameWithAppids(3059520);

    $this->artisan('covers:sync')
        ->expectsOutputToContain('Steam artwork stopped')
        ->assertFailed();

    expect($game->fresh()->steam_artwork_synced_at)->toBeNull();
});

it('looks up steamgriddb covers for games that steam does not cover', function () {
    config(['services.steamgriddb.key' => 'test-grid-key']);
    Http::fake([
        'api.steampowered.com/*' => Http::response(jsonFixture('steam/store-items')),
        'www.steamgriddb.com/api/v2/grids/steam/4496490*' => Http::response(jsonFixture('steamgriddb/grids')),
        'www.steamgriddb.com/api/v2/search/autocomplete/*' => Http::response(jsonFixture('steamgriddb/search')),
        'www.steamgriddb.com/api/v2/grids/game/5001*' => Http::response(jsonFixture('steamgriddb/grids-empty')),
    ]);
    $withCover = gameWithAppids(3059520);
    $soundtrackOnly = gameWithAppids(4496490);
    $withoutAppid = Game::factory()->create(['title' => 'Euro Truck Simulator 2']);

    $this->artisan('covers:sync')
        ->expectsOutputToContain('Checked SteamGridDB for 2 games.')
        ->assertSuccessful();

    expect($soundtrackOnly->fresh())
        ->grid_cover_url->toBe('https://cdn2.steamgriddb.com/grid/aaaa.png')
        ->grid_cover_thumb_url->toBe('https://cdn2.steamgriddb.com/thumb/aaaa.jpg');
    expect($withoutAppid->fresh())->grid_cover_url->toBeNull()->grid_checked_at->not->toBeNull();
    expect($withCover->fresh()->grid_checked_at)->toBeNull();
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'grids/steam/3059520'));
});

it('reports a rejected steamgriddb key as a failure', function () {
    config(['services.steamgriddb.key' => 'wrong-key']);
    Http::fake([
        'api.steampowered.com/*' => Http::response(jsonFixture('steam/store-items')),
        'www.steamgriddb.com/*' => Http::response(['success' => false], 401),
    ]);
    Game::factory()->create();

    $this->artisan('covers:sync')
        ->expectsOutputToContain('SteamGridDB stopped: SteamGridDB responded with HTTP 401.')
        ->assertFailed();
});

it('is scheduled daily after the wiki sync', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'covers:sync'));

    expect($event)->not->toBeNull()
        ->expression->toBe('0 5 * * *')
        ->withoutOverlapping->toBeTrue();
});
