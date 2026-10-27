<?php

use App\Services\Artwork\SteamGridDbClient;
use App\Services\Artwork\SteamGridDbException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config(['services.steamgriddb.key' => 'test-grid-key']);
    Http::preventStrayRequests();
    Sleep::fake();
});

it('returns the best rated static 2:3 cover for a steam app', function () {
    Http::fake(['www.steamgriddb.com/api/v2/grids/steam/3059520*' => Http::response(jsonFixture('steamgriddb/grids'))]);

    expect(app(SteamGridDbClient::class)->coverForSteamApp(3059520))->toBe([
        'url' => 'https://cdn2.steamgriddb.com/grid/aaaa.png',
        'thumb' => 'https://cdn2.steamgriddb.com/thumb/aaaa.jpg',
    ]);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-grid-key')
        && $request['dimensions'] === '600x900'
        && $request['types'] === 'static'
        && $request['nsfw'] === 'false'
        && $request['humor'] === 'false'
        && $request['epilepsy'] === 'false');
});

it('returns null when the game has no covers or is unknown', function (string $fixture, int $status) {
    Http::fake(['www.steamgriddb.com/*' => Http::response(jsonFixture($fixture), $status)]);

    expect(app(SteamGridDbClient::class)->coverForSteamApp(1))->toBeNull();
})->with([
    'no covers' => ['steamgriddb/grids-empty', 200],
    'unknown game' => ['steamgriddb/not-found', 404],
]);

it('finds games without an app id only by an exact, unique title', function () {
    Http::fake([
        'www.steamgriddb.com/api/v2/search/autocomplete/*' => Http::response(jsonFixture('steamgriddb/search')),
        'www.steamgriddb.com/api/v2/grids/game/5001*' => Http::response(jsonFixture('steamgriddb/grids')),
    ]);

    expect(app(SteamGridDbClient::class)->coverForTitle('Euro Truck Simulator 2'))->toMatchArray(['url' => 'https://cdn2.steamgriddb.com/grid/aaaa.png']);
});

it('ignores partial title matches', function () {
    Http::fake(['www.steamgriddb.com/api/v2/search/autocomplete/*' => Http::response(jsonFixture('steamgriddb/search'))]);

    expect(app(SteamGridDbClient::class)->coverForTitle('Euro Truck Simulator'))->toBeNull();
    Http::assertSentCount(1);
});

it('fails clearly when the key is rejected or the service is down', function (int $status) {
    Http::fake(['www.steamgriddb.com/*' => Http::response(['success' => false], $status)]);

    app(SteamGridDbClient::class)->coverForSteamApp(3059520);
})->with([401, 429, 503])->throws(SteamGridDbException::class);
