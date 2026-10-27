<?php

use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\SteamStoreClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Http::preventStrayRequests();
    Sleep::fake();
});

it('builds the store artwork urls, including the hashed paths of new games', function () {
    Http::fake(['api.steampowered.com/IStoreBrowseService/GetItems/v1/*' => Http::response(jsonFixture('steam/store-items'))]);

    $artwork = (new SteamStoreClient)->artwork([3059520, 242050, 4496490, 9999990]);

    expect($artwork[3059520])->toBe([
        'cover' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/660d69e1717e93fcabc23f85100d123892ace85c/library_capsule.jpg?t=1786646076',
        'cover_2x' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/660d69e1717e93fcabc23f85100d123892ace85c/library_capsule_2x.jpg?t=1786646076',
        'header' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/76dc8ac45f1bd6dec26e04434ca1c8814e7bb330/header.jpg?t=1786646076',
        'hero' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/1505fcf27524db87913a821864e270ce68ce4e62/library_hero.jpg?t=1786646076',
    ]);
    expect($artwork[242050]['cover'])->toBe('https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/242050/library_600x900.jpg?t=1777052740');
    expect($artwork[4496490])->toMatchArray(['cover' => null, 'header' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/4496490/aa11/header.jpg?t=1786000000']);
    expect($artwork)->not->toHaveKey(9999990);
});

it('asks for every app id in a single request without the api key', function () {
    config(['services.steam.key' => 'test-steam-key']);
    Http::fake(['api.steampowered.com/*' => Http::response(jsonFixture('steam/store-items'))]);

    (new SteamStoreClient)->artwork([3059520, 242050]);

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) {
        $input = json_decode($request['input_json'], associative: true);

        return ! isset($request['key'])
            && $input['ids'] === [['appid' => 3059520], ['appid' => 242050]]
            && $input['data_request'] === ['include_assets' => true];
    });
});

it('treats an unexpected response as the store being unavailable', function (mixed $response) {
    Http::fake(['api.steampowered.com/*' => $response]);

    (new SteamStoreClient)->artwork([3059520]);
})->with([
    'missing items' => fn () => Http::response(['response' => []]),
    'server error' => fn () => Http::response('', 503),
    'bad request' => fn () => Http::response('', 400),
])->throws(SteamUnavailableException::class);
