<?php

use App\Enums\Support;
use App\Livewire\CatalogTable;
use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('serves the catalog without a Steam account and without calling Steam', function () {
    Http::preventStrayRequests();
    Http::fake();
    Game::factory()->create(['title' => 'Celeste']);

    $this->get(route('catalog'))
        ->assertOk()
        ->assertSeeLivewire(CatalogTable::class)
        ->assertSee('Celeste');

    Http::assertNothingSent();
    expect(route('catalog', absolute: false))->toBe('/juegos');
});

it('links each game to its wiki page and, when it has an app id, to the Steam store', function () {
    $cyberpunk = Game::factory()->create(['title' => 'Cyberpunk 2077', 'dlss' => Support::Yes, 'upscaling_tech' => 'DLSS 4']);
    GameSteamAppid::factory()->for($cyberpunk)->create(['steam_appid' => 1091500]);
    Game::factory()->create(['title' => 'Not On Steam']);

    Livewire::test(CatalogTable::class)
        ->assertSee('https://www.pcgamingwiki.com/w/index.php?curid='.$cyberpunk->pcgw_page_id)
        ->assertSee('https://store.steampowered.com/app/1091500/')
        ->assertSeeHtml('steam/apps/1091500/capsule_184x69.jpg')
        ->assertSee('DLSS (reescalado) 4:')
        ->assertSee(__('catalog.store_link_label', ['name' => 'Cyberpunk 2077']))
        ->assertDontSee(__('catalog.store_link_label', ['name' => 'Not On Steam']));
});

it('searches by name and highlights the match', function () {
    Game::factory()->create(['title' => 'Outer Wilds']);
    Game::factory()->create(['title' => 'Celeste']);

    Livewire::test(CatalogTable::class)
        ->set('search', 'wilds')
        ->assertSee('Outer')
        ->assertDontSee('Celeste')
        ->assertSeeHtml('Outer <mark class="bg-accent/20 text-fg">Wilds</mark>');
});

it('escapes game titles while highlighting', function () {
    Game::factory()->create(['title' => '<b>Bold</b> Game']);

    Livewire::test(CatalogTable::class)
        ->set('search', 'game')
        ->assertDontSeeHtml('<b>Bold</b>')
        ->assertSeeHtml('&lt;b&gt;Bold&lt;/b&gt;');
});

it('reads the search from the URL, as the home page form sends it', function () {
    Game::factory()->create(['title' => 'Outer Wilds']);
    Game::factory()->create(['title' => 'Celeste']);

    $this->get(route('catalog', ['q' => 'celeste']))
        ->assertOk()
        ->assertSee('Celeste')
        ->assertDontSee('Outer Wilds');
});

it('filters by technology and preselects the filter of the chosen GPU', function () {
    Game::factory()->create(['title' => 'With FSR', 'fsr' => Support::Yes]);
    Game::factory()->create(['title' => 'Without FSR', 'fsr' => Support::No]);

    Livewire::test(CatalogTable::class)
        ->call('selectGpu', 'amd')
        ->assertSet('filters', ['fsr'])
        ->assertSee('With FSR')
        ->assertDontSee('Without FSR')
        ->call('toggleFilter', 'fsr')
        ->assertSee('Without FSR');
});

it('says when nothing matches', function () {
    Game::factory()->create(['title' => 'Celeste']);

    Livewire::test(CatalogTable::class)
        ->set('search', 'zzz')
        ->assertSee(__('catalog.no_results'));
});

it('offers the catalog from the home page and the header', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(__('home.catalog.title'))
        ->assertSee('action="'.route('catalog').'"', escape: false)
        ->assertSee(__('layout.header.catalog'));
});
