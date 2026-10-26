<?php

use App\Enums\Support;
use App\Models\Game;
use Illuminate\Support\Arr;

it('is in Spanish by default', function () {
    $this->withHeader('Accept-Language', '')
        ->get(route('home'))
        ->assertSee('<html lang="es">', escape: false)
        ->assertSee(__('home.catalog.title', locale: 'es'));
});

it('follows the browser language when it is supported', function () {
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
        ->get(route('home'))
        ->assertSee('<html lang="en">', escape: false)
        ->assertSee('About to buy a game?')
        ->assertSee('<meta property="og:locale" content="en_US">', escape: false);

    $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
        ->get(route('home'))
        ->assertSee('<html lang="es">', escape: false);
});

it('remembers the language chosen with ?lang= for the whole session', function () {
    $this->withHeader('Accept-Language', 'es')
        ->get(route('privacy', ['lang' => 'en']))
        ->assertSee('Privacy policy')
        ->assertSessionHas('locale', 'en');

    $this->get(route('catalog'))->assertSee('Search any game');

    $this->get(route('catalog', ['lang' => 'xx']))->assertSee('Search any game');
});

it('formats numbers and dates in the chosen language', function () {
    $game = Game::factory()->create(['pcgw_page_id' => 9, 'title' => 'Hades', 'ray_tracing' => Support::No, 'synced_at' => '2026-10-26 10:00:00']);

    $this->get($game->url().'?lang=en')->assertSee('October 26, 2026');
    $this->get($game->url().'?lang=es')->assertSee('26 de octubre de 2026');
});

it('offers a language switcher in the header', function () {
    $this->get(route('home'))
        ->assertSee('hreflang="en"', escape: false)
        ->assertSee('lang=en');
});

it('has the same translation keys in Spanish and English', function () {
    $keys = fn (string $locale) => collect(glob(lang_path("{$locale}/*.php")))
        ->flatMap(fn (string $file) => collect(Arr::dot(require $file))->keys()->map(fn (string $key) => basename($file, '.php').'.'.$key))
        ->merge(array_keys(json_decode(file_get_contents(lang_path("{$locale}.json")), true)))
        ->sort()
        ->values()
        ->all();

    expect($keys('en'))->toBe($keys('es'));
});
