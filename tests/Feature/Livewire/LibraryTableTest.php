<?php

use App\Enums\Support;
use App\Enums\Vendor;
use App\Livewire\LibraryTable;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Providers\AppServiceProvider;
use App\Services\Steam\DemoSteamClient;
use App\Services\Steam\SteamClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.steam.key' => 'test-steam-key']);
});

/**
 * @param  list<array{0: int, 1: string, 2?: int}>  $games  [appid, nombre, minutos jugados]
 */
function fakeOwnedGames(array $games): void
{
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(['response' => [
            'game_count' => count($games),
            'games' => array_map(fn (array $game) => [
                'appid' => $game[0],
                'name' => $game[1],
                'playtime_forever' => $game[2] ?? 0,
            ], $games),
        ]]),
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function wikiGame(int $appid, string $title, array $attributes = []): Game
{
    $game = Game::factory()->create(['title' => $title, ...$attributes]);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => $appid]);

    return $game;
}

/**
 * @return list<string>
 */
function listedNames($component): array
{
    return $component->viewData('games')->getCollection()->map(fn ($entry) => $entry->owned->name)->all();
}

it('lists every game with its support and a link to its wiki page', function () {
    $cyberpunk = wikiGame(1091500, 'Cyberpunk 2077', [
        'dlss' => Support::Yes,
        'fsr' => Support::Yes,
        'xess' => Support::No,
        'upscaling_tech' => 'DLSS 4,FSR 2.1,FSR 3.1',
    ]);
    fakeOwnedGames([[1091500, 'Cyberpunk 2077', 5410]]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee('Cyberpunk 2077')
        ->assertSee('https://www.pcgamingwiki.com/w/index.php?curid='.$cyberpunk->pcgw_page_id)
        ->assertSee($cyberpunk->url())
        ->assertSee('90,2')
        ->assertSee('DLSS (reescalado) 4:')
        ->assertSee('FSR (reescalado) 3.1:')
        ->assertDontSee('FSR (reescalado) 2.1:')
        ->assertSeeHtml('<span class="sr-only">Sí</span>')
        ->assertSeeHtml('<span class="sr-only">No</span>')
        ->assertSeeHtml('<span class="sr-only">Sin datos</span>');
});

it('loads covers lazily from the Steam CDN with a fallback image', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSeeHtml('steam/apps/570/capsule_184x69.jpg')
        ->assertSeeHtml('loading="lazy"')
        ->assertSeeHtml('images/cover-placeholder.svg');
});

it('marks games found by title and games without a wiki page', function () {
    Game::factory()->create(['title' => 'Euro Truck Simulator 2', 'dlss' => Support::Yes]);
    fakeOwnedGames([[227300, 'Euro Truck Simulator 2'], [999999, 'Some Indie Game']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee(__('library.matched_by_title'))
        ->assertSee(__('library.no_wiki_page'));
});

it('summarizes the library and the games that support each technology', function () {
    wikiGame(1091500, 'Cyberpunk 2077', ['dlss' => Support::Yes, 'ray_tracing' => Support::Yes]);
    wikiGame(570, 'Dota 2', ['fsr' => Support::Yes]);
    fakeOwnedGames([[1091500, 'Cyberpunk 2077'], [570, 'Dota 2'], [999999, 'Some Indie Game']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID]);

    expect($component->viewData('stats'))
        ->total->toBe(3)
        ->with_wiki_page->toBe(2)
        ->and($component->viewData('stats')['technologies'])
        ->dlss->toBe(1)
        ->fsr->toBe(1)
        ->xess->toBe(0)
        ->ray_tracing->toBe(1);
});

it('searches by name ignoring case', function () {
    fakeOwnedGames([[1091500, 'Cyberpunk 2077'], [570, 'Dota 2'], [413150, 'Stardew Valley']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->set('search', 'cyber');

    expect(listedNames($component))->toBe(['Cyberpunk 2077']);
});

it('searches by words in any order, ignoring punctuation', function () {
    fakeOwnedGames([[292030, 'The Witcher® 3: Wild Hunt'], [570, 'Dota 2']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->set('search', 'wild hunt witcher 3');

    expect(listedNames($component))->toBe(['The Witcher® 3: Wild Hunt']);
});

it('shows only games that support every selected technology', function () {
    wikiGame(1091500, 'Cyberpunk 2077', ['dlss' => Support::Yes, 'fsr' => Support::Yes]);
    wikiGame(570, 'Dota 2', ['dlss' => Support::No, 'fsr' => Support::Yes]);
    wikiGame(1174180, 'Red Dead Redemption 2', ['dlss' => Support::Yes, 'fsr' => Support::Unknown]);
    fakeOwnedGames([[1091500, 'Cyberpunk 2077'], [570, 'Dota 2'], [1174180, 'Red Dead Redemption 2']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->call('toggleFilter', 'dlss');

    expect(listedNames($component))->toBe(['Cyberpunk 2077', 'Red Dead Redemption 2']);

    $component->call('toggleFilter', 'fsr');

    expect(listedNames($component))->toBe(['Cyberpunk 2077']);

    $component->call('toggleFilter', 'dlss');

    expect(listedNames($component))->toBe(['Cyberpunk 2077', 'Dota 2']);
});

it('ignores unknown technologies in the filters', function () {
    wikiGame(1091500, 'Cyberpunk 2077', ['dlss' => Support::Yes]);
    fakeOwnedGames([[1091500, 'Cyberpunk 2077'], [570, 'Dota 2']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->call('toggleFilter', 'not-a-technology')
        ->set('filters', ['dlss', 'also-not-a-technology']);

    expect(listedNames($component))->toBe(['Cyberpunk 2077']);
    expect($component->viewData('activeFilters'))->toBe(['dlss']);
});

it('clears the search and the filters', function () {
    fakeOwnedGames([[1091500, 'Cyberpunk 2077'], [570, 'Dota 2']]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->set('search', 'cyber')
        ->call('toggleFilter', 'dlss')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('filters', []);

    expect(listedNames($component))->toHaveCount(2);
});

it('sorts by name and by hours played', function () {
    fakeOwnedGames([[570, 'Dota 2', 125430], [1091500, 'Cyberpunk 2077', 5410], [413150, 'Stardew Valley', 0]]);

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID]);

    expect(listedNames($component))->toBe(['Cyberpunk 2077', 'Dota 2', 'Stardew Valley']);

    $component->call('sortBy', 'playtime');

    expect(listedNames($component))->toBe(['Dota 2', 'Cyberpunk 2077', 'Stardew Valley']);

    $component->call('sortBy', 'playtime');

    expect(listedNames($component))->toBe(['Stardew Valley', 'Cyberpunk 2077', 'Dota 2']);

    $component->call('sortBy', 'name')->call('sortBy', 'name');

    expect(listedNames($component))->toBe(['Stardew Valley', 'Dota 2', 'Cyberpunk 2077']);
});

it('paginates large libraries on the server', function () {
    fakeOwnedGames(array_map(fn (int $i) => [100000 + $i, sprintf('Game %02d', $i)], range(1, 30)));

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID]);

    expect(listedNames($component))->toHaveCount(25)->toContain('Game 01')->not->toContain('Game 26');

    $component->call('gotoPage', 2);

    expect(listedNames($component))->toBe(['Game 26', 'Game 27', 'Game 28', 'Game 29', 'Game 30']);
});

it('goes back to the first page when the search changes', function () {
    fakeOwnedGames(array_map(fn (int $i) => [100000 + $i, sprintf('Game %02d', $i)], range(1, 30)));

    $component = Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->call('gotoPage', 2)
        ->set('search', 'Game 07');

    expect(listedNames($component))->toBe(['Game 07']);
});

it('preselects the filter of the chosen GPU vendor and highlights its columns', function (string $gpu, array $filters, ?Vendor $highlighted) {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->call('selectGpu', $gpu)
        ->assertSet('gpu', $gpu)
        ->assertSet('filters', $filters)
        ->assertViewHas('highlightedVendor', $highlighted);
})->with([
    'NVIDIA' => ['nvidia', ['dlss'], Vendor::Nvidia],
    'AMD' => ['amd', ['fsr'], Vendor::Amd],
    'Intel' => ['intel', ['xess'], Vendor::Intel],
    'Todas' => ['all', [], null],
]);

it('keeps shared filters when restoring the saved GPU', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->set('filters', ['ray_tracing'])
        ->call('selectGpu', 'amd', false)
        ->assertSet('gpu', 'amd')
        ->assertSet('filters', ['ray_tracing']);
});

it('ignores an unknown GPU vendor', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->call('selectGpu', 'voodoo')
        ->assertSet('gpu', 'all');
});

it('explains how to fix a private profile or private game details', function () {
    Http::preventStrayRequests();
    Http::fake(['api.steampowered.com/*' => Http::response(jsonFixture('steam/owned-games-private'))]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee(__('library.errors.private.title'))
        ->assertSee(__('library.errors.private.body'))
        ->assertSee(__('home.privacy_help.steps'))
        ->assertDontSee(__('library.table.caption'));
});

it('offers to retry when Steam does not respond, without technical details', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake(['api.steampowered.com/*' => Http::response('Service Unavailable', 503)]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee(__('library.errors.unavailable.title'))
        ->assertSee(__('library.errors.unavailable.retry'))
        ->assertDontSee('503');
});

it('says when the library has no games', function () {
    Http::preventStrayRequests();
    Http::fake(['api.steampowered.com/*' => Http::response(jsonFixture('steam/owned-games-empty'))]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee(__('library.empty'));
});

it('says when no game matches the search', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->set('search', 'zelda')
        ->assertSee(__('library.no_results'));
});

it('warns about Family Sharing games', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertSee(__('home.privacy_help.family_sharing'));
});

it('warns when the library is the local demo', function () {
    app()->bind(SteamClient::class, DemoSteamClient::class);

    Livewire::test(LibraryTable::class, ['steamId' => DemoSteamClient::STEAM_ID])
        ->assertSee(__('library.demo'))
        ->assertSee('Cyberpunk 2077');
});

it('does not warn about the demo with a real Steam library', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    Livewire::test(LibraryTable::class, ['steamId' => TEST_STEAM_ID])
        ->assertDontSee(__('library.demo'));
});

it('serves the library page at a shareable URL', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    $this->get(route('library.show', TEST_STEAM_ID))
        ->assertOk()
        ->assertSeeLivewire(LibraryTable::class)
        ->assertSee('Dota 2');
});

it('returns 404 for an invalid SteamID64 in the URL', function (string $steamId) {
    $this->get('/u/'.$steamId)->assertNotFound();
})->with([
    'texto' => 'gabelogannewell',
    'demasiado corto' => '12345',
    'fuera de rango' => '12345678901234567',
]);

it('limits library pages per IP and explains it in Spanish', function () {
    fakeOwnedGames([[570, 'Dota 2']]);

    foreach (range(1, AppServiceProvider::STEAM_REQUESTS_PER_MINUTE) as $attempt) {
        $this->get(route('library.show', TEST_STEAM_ID))->assertOk();
    }

    $this->get(route('library.show', TEST_STEAM_ID))
        ->assertTooManyRequests()
        ->assertSee(__('errors.429.title'));
});
