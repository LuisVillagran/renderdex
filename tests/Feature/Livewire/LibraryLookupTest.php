<?php

use App\Livewire\LibraryLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.steam.key' => 'test-steam-key']);
});

it('opens the library of a SteamID64 without calling Steam', function (string $input) {
    Http::preventStrayRequests();
    Http::fake();

    Livewire::test(LibraryLookup::class)
        ->set('profile', $input)
        ->call('lookup')
        ->assertHasNoErrors()
        ->assertRedirect(route('library.show', TEST_STEAM_ID));

    Http::assertNothingSent();
})->with([
    'SteamID64' => TEST_STEAM_ID,
    'URL de perfil' => 'https://steamcommunity.com/profiles/'.TEST_STEAM_ID,
]);

it('resolves a custom URL before opening the library', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/ResolveVanityURL/v1/*' => Http::response(jsonFixture('steam/resolve-vanity-success')),
    ]);

    Livewire::test(LibraryLookup::class)
        ->set('profile', 'steamcommunity.com/id/gabelogannewell')
        ->call('lookup')
        ->assertRedirect(route('library.show', TEST_STEAM_ID));
});

it('asks for a profile when the field is empty', function () {
    Livewire::test(LibraryLookup::class)
        ->set('profile', '')
        ->call('lookup')
        ->assertHasErrors('profile')
        ->assertSee(__('lookup.errors.required'))
        ->assertNoRedirect();
});

it('explains that the input is not a Steam profile', function () {
    Livewire::test(LibraryLookup::class)
        ->set('profile', 'https://example.com/profiles/'.TEST_STEAM_ID)
        ->call('lookup')
        ->assertHasErrors('profile')
        ->assertSee(__('lookup.errors.invalid'))
        ->assertNoRedirect();
});

it('explains that the custom URL does not exist', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/ResolveVanityURL/v1/*' => Http::response(jsonFixture('steam/resolve-vanity-not-found')),
    ]);

    Livewire::test(LibraryLookup::class)
        ->set('profile', 'this-profile-does-not-exist')
        ->call('lookup')
        ->assertHasErrors('profile')
        ->assertSee(__('lookup.errors.not_found'))
        ->assertNoRedirect();
});

it('asks to try again later when Steam does not respond, without technical details', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake(['api.steampowered.com/*' => Http::response('Service Unavailable', 503)]);

    Livewire::test(LibraryLookup::class)
        ->set('profile', 'gabelogannewell')
        ->call('lookup')
        ->assertHasErrors('profile')
        ->assertSee(__('lookup.errors.unavailable'))
        ->assertDontSee('HTTP 503')
        ->assertNoRedirect();
});
