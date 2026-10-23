<?php

use App\Enums\Support;
use App\Http\Controllers\HomeController;
use App\Livewire\LibraryLookup;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Models\SyncRun;

it('offers signing in with Steam and pasting a profile to guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(route('auth.steam'));
    $response->assertSee(__('home.steam_login.alt'));
    $response->assertSee(__('home.steam_login.note'));
    $response->assertSeeLivewire(LibraryLookup::class);
});

it('explains how to make the library public and the family sharing limit', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(__('home.privacy_help.steps'));
    $response->assertSee(__('home.privacy_help.family_sharing'));
});

it('shows the signed in user and a link to their library', function () {
    $this->withSession(['steam_user' => [
        'steam_id' => TEST_STEAM_ID,
        'name' => 'Rabscuttle',
        'avatar' => 'https://avatars.steamstatic.com/avatar_medium.jpg',
    ]]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Rabscuttle');
    $response->assertSee('https://avatars.steamstatic.com/avatar_medium.jpg');
    $response->assertSee(route('library.show', TEST_STEAM_ID));
    $response->assertSee(__('auth.logout'));
    $response->assertDontSee(route('auth.steam'));
});

it('shows a generic account label when the name is unknown', function () {
    $this->withSession(['steam_user' => ['steam_id' => TEST_STEAM_ID, 'name' => null, 'avatar' => null]]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(__('layout.header.your_account'));
});

it('shows the message of a failed Steam sign in', function () {
    $response = $this->withSession(['error' => __('auth.steam.cancelled')])->get(route('home'));

    $response->assertOk();
    $response->assertSee(__('auth.steam.cancelled'));
});

it('credits PCGamingWiki with its license and shows the last successful sync', function () {
    SyncRun::factory()->create(['finished_at' => '2026-10-21 04:03:00']);
    SyncRun::factory()->failed()->create(['finished_at' => '2026-10-22 04:01:00']);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('https://www.pcgamingwiki.com/');
    $response->assertSee('CC BY-NC-SA 3.0');
    $response->assertSee('21 de octubre de 2026');
    $response->assertSee(__('layout.footer.not_affiliated'));
    $response->assertSee(config('app.repository_url'));
});

it('says when the data has never been synced', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(__('layout.footer.never_synced'));
});

it('previews real games from the wiki and how many games it documents', function () {
    [$first, $second] = HomeController::SHOWCASE_APPIDS;
    GameSteamAppid::factory()->for(Game::factory()->create(['title' => 'Showcase Two']))->create(['steam_appid' => $second]);
    GameSteamAppid::factory()->for(Game::factory()->create(['title' => 'Showcase One', 'dlss' => Support::Yes, 'upscaling_tech' => 'DLSS 4.5']))->create(['steam_appid' => $first]);
    Game::factory()->count(3)->create();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee(__('home.eyebrow', ['count' => 5]));
    $response->assertSeeInOrder(['Showcase One', 'DLSS (reescalado) 4.5:', 'Showcase Two']);
    $response->assertSee("steam/apps/{$first}/capsule_184x69.jpg", escape: false);
});

it('hides the preview until the wiki has been synced', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee(__('home.preview.label'));
    $response->assertDontSee(__('home.eyebrow', ['count' => 0]));
});

it('describes the page for link previews with Open Graph', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('<meta property="og:title" content="Renderdex">', escape: false);
    $response->assertSee('<meta property="og:description" content="'.e(__('layout.description')).'">', escape: false);
    $response->assertSee('<meta property="og:image" content="'.asset('images/og.png').'">', escape: false);
    $response->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);
    expect(public_path('images/og.png'))->toBeFile();
});

it('uses the page title in link previews of inner pages', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Privacidad · Renderdex">', escape: false)
        ->assertSee('<meta property="og:url" content="'.route('privacy').'">', escape: false);
});
