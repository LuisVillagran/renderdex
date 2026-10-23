<?php

use App\Services\Steam\DemoSteamClient;
use App\Services\Steam\ProfileInput;
use Illuminate\Support\Facades\Http;

it('returns the sample library without calling Steam', function () {
    Http::preventStrayRequests();
    Http::fake();

    $games = (new DemoSteamClient)->ownedGames(TEST_STEAM_ID);

    expect($games)->not->toBeEmpty();
    expect(collect($games)->pluck('name'))->toContain('Cyberpunk 2077', 'Euro Truck Simulator 2');
    Http::assertNothingSent();
});

it('resolves any custom URL to the sample profile', function () {
    $steamId = (new DemoSteamClient)->resolveSteamId(ProfileInput::vanity('anyone'));

    expect($steamId)->toBe(DemoSteamClient::STEAM_ID);
});

it('identifies itself as a demo', function () {
    expect((new DemoSteamClient)->isDemo())->toBeTrue();
});

it('replaces Steam only in local development without an API key', function (string $environment, ?string $key, bool $expected) {
    app()['env'] = $environment;
    config(['services.steam.key' => $key]);

    expect(DemoSteamClient::shouldReplaceSteam())->toBe($expected);
})->with([
    'local sin key' => ['local', null, true],
    'local con key' => ['local', 'real-key', false],
    'producción sin key' => ['production', null, false],
    'tests sin key' => ['testing', null, false],
]);
