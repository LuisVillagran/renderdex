<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config(['services.steam.key' => 'test-steam-key']);
});

/**
 * Parámetros que Steam agrega al volver al callback tras un login exitoso.
 *
 * @param  array<string, string>  $overrides
 */
function steamCallbackUrl(array $overrides = []): string
{
    $claimedId = 'https://steamcommunity.com/openid/id/'.TEST_STEAM_ID;

    return route('auth.steam.callback').'?'.http_build_query(array_merge([
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
        'openid.mode' => 'id_res',
        'openid.op_endpoint' => 'https://steamcommunity.com/openid/login',
        'openid.claimed_id' => $claimedId,
        'openid.identity' => $claimedId,
        'openid.return_to' => route('auth.steam.callback'),
        'openid.response_nonce' => '2026-10-22T23:00:00Zd9k3Lq8Xb2',
        'openid.assoc_handle' => '1234567890',
        'openid.signed' => 'signed,op_endpoint,claimed_id,identity,return_to,response_nonce,assoc_handle',
        'openid.sig' => 'W0u5DRbtHE1GG0ZKXjerUZDUGmc=',
    ], $overrides));
}

/**
 * Simula la verificación check_authentication que el paquete hace contra Steam con Guzzle.
 */
function fakeSteamOpenIdVerification(bool $isValid = true): ArrayObject
{
    $history = new ArrayObject;
    $body = "ns:http://specs.openid.net/auth/2.0\nis_valid:".($isValid ? 'true' : 'false')."\n";
    $stack = HandlerStack::create(new MockHandler([new GuzzleResponse(200, [], $body)]));
    $stack->push(Middleware::history($history));
    config(['services.steam.guzzle' => ['handler' => $stack]]);

    return $history;
}

function fakeSteamPlayerSummary(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/*' => Http::response(jsonFixture('steam/player-summaries')),
    ]);
}

it('redirects to the Steam OpenID login page', function () {
    $response = $this->get(route('auth.steam'));

    $location = $response->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect($location)->toStartWith('https://steamcommunity.com/openid/login?');
    expect($query)
        ->openid_mode->toBe('checkid_setup')
        ->openid_return_to->toBe(route('auth.steam.callback'));
});

it('signs the user in with the SteamID64, name and avatar and opens their library', function () {
    $verification = fakeSteamOpenIdVerification();
    fakeSteamPlayerSummary();

    $response = $this->get(steamCallbackUrl());

    $response->assertRedirect(route('library.show', TEST_STEAM_ID));
    $response->assertSessionHas('steam_user', [
        'steam_id' => TEST_STEAM_ID,
        'name' => 'Rabscuttle',
        'avatar' => 'https://avatars.steamstatic.com/fef49e7fa7e1997310d705b2a6158ff8dc1cdfeb_medium.jpg',
    ]);
    expect($verification)->toHaveCount(1);
    expect((string) $verification[0]['request']->getBody())->toContain('openid.mode=check_authentication');
    Http::assertSent(fn (Request $request) => $request['steamids'] === TEST_STEAM_ID);
});

it('regenerates the session id when signing in', function () {
    fakeSteamOpenIdVerification();
    fakeSteamPlayerSummary();
    $this->withSession(['visited' => true]);
    $sessionIdBefore = session()->getId();

    $this->get(steamCallbackUrl());

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

it('rejects a claimed id that is malformed or from another domain without asking Steam', function (string $claimedId) {
    $verification = fakeSteamOpenIdVerification();

    $response = $this->get(steamCallbackUrl(['openid.claimed_id' => $claimedId, 'openid.identity' => $claimedId]));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error', __('auth.steam.failed'));
    $response->assertSessionMissing('steam_user');
    expect($verification)->toHaveCount(0);
})->with([
    'otro dominio' => 'https://evil.example/openid/id/'.TEST_STEAM_ID,
    'dominio que imita a Steam' => 'https://steamcommunity.com.evil.example/openid/id/'.TEST_STEAM_ID,
    'sin HTTPS' => 'http://steamcommunity.com/openid/id/'.TEST_STEAM_ID,
    'ID no numérico' => 'https://steamcommunity.com/openid/id/gabelogannewell',
    'ID corto' => 'https://steamcommunity.com/openid/id/12345',
    'texto después del ID' => 'https://steamcommunity.com/openid/id/'.TEST_STEAM_ID.'/extra',
    'vacío' => '',
]);

it('rejects a response that does not come from the Steam OpenID endpoint', function () {
    $verification = fakeSteamOpenIdVerification();

    $response = $this->get(steamCallbackUrl(['openid.op_endpoint' => 'https://evil.example/openid/login']));

    $response->assertSessionHas('error', __('auth.steam.failed'));
    $response->assertSessionMissing('steam_user');
    expect($verification)->toHaveCount(0);
});

it('rejects a response whose claimed id is not signed', function () {
    $verification = fakeSteamOpenIdVerification();

    $response = $this->get(steamCallbackUrl(['openid.signed' => 'signed,op_endpoint,return_to,response_nonce,assoc_handle']));

    $response->assertSessionHas('error', __('auth.steam.failed'));
    $response->assertSessionMissing('steam_user');
    expect($verification)->toHaveCount(0);
});

it('rejects a response whose return address points to another domain', function () {
    $verification = fakeSteamOpenIdVerification();

    $response = $this->get(steamCallbackUrl(['openid.return_to' => 'https://evil.example/auth/steam/callback']));

    $response->assertSessionHas('error', __('auth.steam.failed'));
    $response->assertSessionMissing('steam_user');
    expect($verification)->toHaveCount(0);
});

it('rejects the login when Steam does not confirm the signature', function () {
    fakeSteamOpenIdVerification(isValid: false);

    $response = $this->get(steamCallbackUrl());

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error', __('auth.steam.failed'));
    $response->assertSessionMissing('steam_user');
});

it('returns home with a message when the user cancels on Steam', function () {
    $verification = fakeSteamOpenIdVerification();

    $response = $this->get(route('auth.steam.callback').'?'.http_build_query([
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
        'openid.mode' => 'cancel',
    ]));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error', __('auth.steam.cancelled'));
    $response->assertSessionMissing('steam_user');
    expect($verification)->toHaveCount(0);
});

it('signs the user in without name or avatar when Steam cannot return the profile', function () {
    fakeSteamOpenIdVerification();
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake(['api.steampowered.com/*' => Http::response('Service Unavailable', 503)]);

    $response = $this->get(steamCallbackUrl());

    $response->assertRedirect(route('library.show', TEST_STEAM_ID));
    $response->assertSessionHas('steam_user', [
        'steam_id' => TEST_STEAM_ID,
        'name' => null,
        'avatar' => null,
    ]);
});

it('signs the user out and destroys the session', function () {
    $this->withSession(['steam_user' => ['steam_id' => TEST_STEAM_ID, 'name' => 'Rabscuttle', 'avatar' => null]]);

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('home'));
    $response->assertSessionMissing('steam_user');
});

it('only signs out through a POST request', function () {
    $this->get('/logout')->assertMethodNotAllowed();
});
