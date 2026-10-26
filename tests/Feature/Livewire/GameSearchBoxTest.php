<?php

use App\Enums\Support;
use App\Livewire\GameSearchBox;
use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('suggests games while typing, with a link to each page and what they support', function () {
    Http::preventStrayRequests();
    $hades = Game::factory()->create([
        'title' => 'Hades',
        'dlss' => Support::Yes,
        'ray_tracing' => Support::No,
        'upscaling_tech' => 'DLSS 3.5',
    ]);
    GameSteamAppid::factory()->for($hades)->create(['steam_appid' => 1145360]);
    Game::factory()->create(['title' => 'Hades II']);
    Game::factory()->create(['title' => 'Celeste']);

    Livewire::test(GameSearchBox::class)
        ->set('query', 'hades')
        ->assertSee($hades->url())
        ->assertSee('DLSS 3.5')
        ->assertSee(__('search.no_data'))
        ->assertSeeHtml('steam/apps/1145360/capsule_184x69.jpg')
        ->assertSeeHtml('<mark class="bg-accent/20 text-fg">Hades</mark>')
        ->assertSee(__('search.see_all', ['query' => 'hades']))
        ->assertDontSee('Celeste');

    Http::assertNothingSent();
});

it('waits for at least two characters', function () {
    Game::factory()->create(['title' => 'Hades']);

    Livewire::test(GameSearchBox::class)
        ->set('query', 'h')
        ->assertViewHas('suggestions', fn ($suggestions) => $suggestions->isEmpty())
        ->assertDontSee(__('search.no_results', ['query' => 'h']));
});

it('says when no game matches', function () {
    Livewire::test(GameSearchBox::class)
        ->set('query', 'zzzz')
        ->assertSee(__('search.no_results', ['query' => 'zzzz']));
});

it('works as a plain search form towards the catalog', function () {
    Livewire::test(GameSearchBox::class)
        ->assertSeeHtml('action="'.route('catalog').'"')
        ->assertSeeHtml('name="q"')
        ->assertSeeHtml('role="combobox"');
});

it('limits the suggestions per IP', function () {
    Game::factory()->create(['title' => 'Hades']);
    RateLimiter::increment('game-suggestions:'.sha1('127.0.0.1'), amount: GameSearchBox::SUGGESTIONS_PER_MINUTE);

    Livewire::test(GameSearchBox::class)
        ->set('query', 'hades')
        ->assertSee(__('search.throttled'))
        ->assertViewHas('suggestions', fn ($suggestions) => $suggestions->isEmpty());
});

it('is in the header of every page except the catalog, which has its own search', function () {
    $this->get(route('privacy'))->assertSeeHtml('id="header-search"');
    $this->get(route('catalog'))->assertDontSeeHtml('id="header-search"');
});
