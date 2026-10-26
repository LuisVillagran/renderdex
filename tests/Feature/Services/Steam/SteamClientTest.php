<?php

use App\Services\Steam\Exceptions\PrivateProfileException;
use App\Services\Steam\Exceptions\ProfileNotFoundException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\ProfileInput;
use App\Services\Steam\SteamClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config(['services.steam.key' => 'test-steam-key']);
});

it('returns the owned games with their name and playtime', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(jsonFixture('steam/owned-games')),
    ]);

    $games = (new SteamClient)->ownedGames(TEST_STEAM_ID);

    expect($games)->toHaveCount(4);
    expect($games[0])
        ->appid->toBe(570)
        ->name->toBe('Dota 2')
        ->playtimeMinutes->toBe(125430);
    Http::assertSent(fn (Request $request) => $request['key'] === 'test-steam-key'
        && $request['steamid'] === TEST_STEAM_ID
        && $request['include_appinfo'] === 1
        && $request['include_played_free_games'] === 1);
});

it('caches the library for an hour per profile', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(jsonFixture('steam/owned-games')),
    ]);
    $client = new SteamClient;

    $client->ownedGames(TEST_STEAM_ID);
    $this->travel(59)->minutes();
    $cached = $client->ownedGames(TEST_STEAM_ID);

    expect($cached)->toHaveCount(4);
    expect($cached[1]->name)->toBe('Cyberpunk 2077');
    Http::assertSentCount(1);

    $this->travel(2)->minutes();
    $client->ownedGames(TEST_STEAM_ID);

    Http::assertSentCount(2);
});

it('does not store the SteamID in plain text in the cache key', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(jsonFixture('steam/owned-games')),
    ]);

    (new SteamClient)->ownedGames(TEST_STEAM_ID);

    expect(cache()->has('steam:owned-games:'.hash('sha256', TEST_STEAM_ID)))->toBeTrue();
    expect(cache()->has('steam:owned-games:'.TEST_STEAM_ID))->toBeFalse();
});

it('reports a private profile when Steam returns no game data', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(jsonFixture('steam/owned-games-private')),
    ]);

    (new SteamClient)->ownedGames(TEST_STEAM_ID);
})->throws(PrivateProfileException::class);

it('does not cache a private profile so it works once the user makes it public', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::sequence()
            ->push(jsonFixture('steam/owned-games-private'))
            ->push(jsonFixture('steam/owned-games')),
    ]);
    $client = new SteamClient;

    expect(fn () => $client->ownedGames(TEST_STEAM_ID))->toThrow(PrivateProfileException::class);
    $games = $client->ownedGames(TEST_STEAM_ID);

    expect($games)->toHaveCount(4);
});

it('returns an empty list for a public library without games', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IPlayerService/GetOwnedGames/v1/*' => Http::response(jsonFixture('steam/owned-games-empty')),
    ]);

    $games = (new SteamClient)->ownedGames(TEST_STEAM_ID);

    expect($games)->toBe([]);
});

it('reports Steam as unavailable after retrying a timeout, without leaking the key or the SteamID', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'api.steampowered.com/*' => Http::failedConnection('cURL error 28: Operation timed out for https://api.steampowered.com/?key=test-steam-key&steamid='.TEST_STEAM_ID),
    ]);

    try {
        (new SteamClient)->ownedGames(TEST_STEAM_ID);
        $this->fail('Expected a SteamUnavailableException.');
    } catch (SteamUnavailableException $exception) {
        expect($exception->getMessage())
            ->not->toContain('test-steam-key')
            ->not->toContain(TEST_STEAM_ID);
        expect($exception->getPrevious())->toBeNull();
    }

    Http::assertSentCount(2);
});

it('reports Steam as unavailable when it responds with a server error', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'api.steampowered.com/*' => Http::response('Service Unavailable', 503),
    ]);

    expect(fn () => (new SteamClient)->ownedGames(TEST_STEAM_ID))
        ->toThrow(SteamUnavailableException::class, 'HTTP 503');
});

it('does not retry when Steam rejects the request', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/*' => Http::response('Forbidden', 403),
    ]);

    expect(fn () => (new SteamClient)->ownedGames(TEST_STEAM_ID))
        ->toThrow(SteamUnavailableException::class, 'HTTP 403');
    Http::assertSentCount(1);
});

it('reports Steam as unavailable without calling it when the API key is missing', function () {
    Http::preventStrayRequests();
    Http::fake();
    config(['services.steam.key' => null]);

    expect(fn () => (new SteamClient)->ownedGames(TEST_STEAM_ID))
        ->toThrow(SteamUnavailableException::class, 'not configured');
    Http::assertNothingSent();
});

it('resolves a custom URL to its SteamID64', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/ResolveVanityURL/v1/*' => Http::response(jsonFixture('steam/resolve-vanity-success')),
    ]);

    $steamId = (new SteamClient)->resolveSteamId(ProfileInput::vanity('gabelogannewell'));

    expect($steamId)->toBe(TEST_STEAM_ID);
    Http::assertSent(fn (Request $request) => $request['vanityurl'] === 'gabelogannewell');
});

it('reports a profile as not found when the custom URL does not exist', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/ResolveVanityURL/v1/*' => Http::response(jsonFixture('steam/resolve-vanity-not-found')),
    ]);

    (new SteamClient)->resolveSteamId(ProfileInput::vanity('this-profile-does-not-exist'));
})->throws(ProfileNotFoundException::class);

it('returns the display name and avatar of a profile', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/*' => Http::response(jsonFixture('steam/player-summaries')),
    ]);

    $summary = (new SteamClient)->playerSummary(TEST_STEAM_ID);

    expect($summary)
        ->steamId->toBe(TEST_STEAM_ID)
        ->name->toBe('Rabscuttle')
        ->avatarUrl->toBe('https://avatars.steamstatic.com/fef49e7fa7e1997310d705b2a6158ff8dc1cdfeb_medium.jpg');
    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://')
        && $request['steamids'] === TEST_STEAM_ID);
});

it('returns no summary when Steam does not know the profile', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/*' => Http::response(jsonFixture('steam/player-summaries-empty')),
    ]);

    expect((new SteamClient)->playerSummary(TEST_STEAM_ID))->toBeNull();
});

it('uses a SteamID64 as is without calling Steam', function () {
    Http::preventStrayRequests();
    Http::fake();

    $steamId = (new SteamClient)->resolveSteamId(ProfileInput::steamId(TEST_STEAM_ID));

    expect($steamId)->toBe(TEST_STEAM_ID);
    Http::assertNothingSent();
});

it('returns the wishlist app ids and caches them for an hour', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/IWishlistService/GetWishlist/v1/*' => Http::response(jsonFixture('steam/wishlist')),
    ]);

    expect((new SteamClient)->wishlist(TEST_STEAM_ID))->toBe([1091500, 1145360, 999999])
        ->and((new SteamClient)->wishlist(TEST_STEAM_ID))->toBe([1091500, 1145360, 999999]);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request['steamid'] === TEST_STEAM_ID);
});

it('reports a private or empty wishlist, without caching it', function () {
    Http::fake([
        'api.steampowered.com/IWishlistService/GetWishlist/v1/*' => Http::sequence()
            ->push(jsonFixture('steam/wishlist-private'))
            ->push(jsonFixture('steam/wishlist')),
    ]);

    expect(fn () => (new SteamClient)->wishlist(TEST_STEAM_ID))->toThrow(PrivateProfileException::class);
    expect((new SteamClient)->wishlist(TEST_STEAM_ID))->toHaveCount(3);
});

it('reports Steam as unavailable when the undocumented wishlist endpoint fails', function () {
    Http::fake([
        'api.steampowered.com/IWishlistService/GetWishlist/v1/*' => Http::response('Not Found', 404),
    ]);

    expect(fn () => (new SteamClient)->wishlist(TEST_STEAM_ID))->toThrow(SteamUnavailableException::class);
});
