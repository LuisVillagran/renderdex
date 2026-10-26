<?php

use App\Enums\Support;
use App\Models\Game;
use App\Models\GameSteamAppid;
use Illuminate\Support\Facades\Http;

function cyberpunk(): Game
{
    $game = Game::factory()->create([
        'pcgw_page_id' => 12345,
        'title' => 'Cyberpunk 2077',
        'dlss' => Support::Yes,
        'dlss_fg' => Support::Yes,
        'dlss_mfg' => Support::Yes,
        'fsr' => Support::Yes,
        'xess' => Support::No,
        'ray_tracing' => Support::Yes,
        'upscaling_tech' => 'DLSS 4,FSR 2.1,FSR 3.1',
        'frame_gen_tech' => 'DLSS Frame Generation,DLSS Multi-Frame Generation',
    ]);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => 1091500]);

    return $game;
}

it('shows every technology with its versions, the raw wiki text and the credit', function () {
    Http::preventStrayRequests();
    $game = cyberpunk();

    $this->get('/juegos/12345-cyberpunk-2077')
        ->assertOk()
        ->assertSee('Cyberpunk 2077')
        ->assertSee('Según PCGamingWiki, soporta DLSS 4, DLSS MFG, FSR 3.1 y Ray Tracing.')
        ->assertSee('DLSS (reescalado) 4:')
        ->assertSee('DLSS 4, FSR 2.1, FSR 3.1')
        ->assertSee($game->wikiUrl())
        ->assertSee($game->wikiEditUrl())
        ->assertSee('https://store.steampowered.com/app/1091500/')
        ->assertSee('CC BY-NC-SA 3.0');

    Http::assertNothingSent();
});

it('has its own title, description, canonical url and Open Graph image', function () {
    cyberpunk();

    $this->get('/juegos/12345-cyberpunk-2077')
        ->assertSee('<title>¿Cyberpunk 2077 tiene DLSS, FSR o XeSS? · Renderdex</title>', escape: false)
        ->assertSee('<meta name="description" content="Según PCGamingWiki, Cyberpunk 2077 soporta DLSS 4, DLSS MFG, FSR 3.1 y Ray Tracing.">', escape: false)
        ->assertSee('<link rel="canonical" href="'.url('/juegos/12345-cyberpunk-2077').'">', escape: false)
        ->assertSee('steam/apps/1091500/header.jpg')
        ->assertDontSee('<meta name="robots" content="noindex">', escape: false);
});

it('redirects to the canonical url when the slug is missing or outdated', function (string $path) {
    cyberpunk();

    $this->get($path)->assertRedirect('/juegos/12345-cyberpunk-2077')->assertStatus(301);
})->with(['/juegos/12345', '/juegos/12345-old-title']);

it('returns 404 for unknown games', function () {
    $this->get('/juegos/999')->assertNotFound();
});

it('invites to complete the wiki and is not indexed when there is no data', function () {
    $game = Game::factory()->create(['pcgw_page_id' => 7, 'title' => 'Obscure Game']);

    $this->get('/juegos/7-obscure-game')
        ->assertOk()
        ->assertSee(__('game.no_data.title'))
        ->assertSee($game->wikiEditUrl())
        ->assertSee('<meta name="robots" content="noindex">', escape: false)
        ->assertDontSee(__('game.technologies.title'));
});

it('uses only the page id when the title has no latin characters', function () {
    $game = Game::factory()->create(['pcgw_page_id' => 8, 'title' => '東方']);

    expect($game->url())->toBe(url('/juegos/8'));
    $this->get('/juegos/8')->assertOk();
});
