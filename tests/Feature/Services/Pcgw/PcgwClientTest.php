<?php

use App\Services\Pcgw\PcgwClient;
use App\Services\Pcgw\PcgwException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

beforeEach(function () {
    config([
        'pcgw.bot_username' => 'RenderdexTest@Renderdex',
        'pcgw.bot_password' => 'test-bot-password',
    ]);
});

it('logs in with the bot password and returns parsed games', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/games-page-1')),
    ]);

    $games = (new PcgwClient)->games(offset: 0, limit: 2);

    expect($games)->toHaveCount(2);
    expect($games[0])
        ->pageId->toBe(45)
        ->title->toBe("Mirror's Edge")
        ->steamAppids->toBe([17410])
        ->upscaling->toBe('DLSS 4.5,NIS,TAAU,XeSS 2.1')
        ->frameGen->toBe('DLSS Frame Generation,DLSS Multi-Frame Generation')
        ->rayTracing->toBe('hackable');
    expect($games[1])
        ->title->toBe('Titan Quest')
        ->steamAppids->toBe([4540, 4550])
        ->upscaling->toBeNull()
        ->frameGen->toBeNull();
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request['action'] === 'login'
        && $request['lgname'] === 'RenderdexTest@Renderdex'
        && $request['lgtoken'] === jsonFixture('pcgw/login-token')['query']['tokens']['logintoken']);
    Http::assertSent(fn (Request $request) => $request['action'] === 'cargoquery'
        && $request['tables'] === 'Game,Video'
        && $request['offset'] === 0
        && $request['limit'] === 2
        && str_contains($request['fields'], 'Video.Frame_gen=FrameGen'));
});

it('identifies itself with a descriptive user agent on every request', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/games-page-1')),
    ]);

    (new PcgwClient)->games(offset: 0, limit: 2);

    Http::assertSentCount(4);
    Http::assertNotSent(fn (Request $request) => ! $request->hasHeader(
        'User-Agent',
        'Renderdex/1.0 (https://github.com/LuisVillagran/renderdex; LuisVillagran) Laravel/13',
    ));
});

it('parses app ids with spaces and skips pages without app ids', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/games-page-2'))
            ->push(jsonFixture('pcgw/games-page-3')),
    ]);
    $client = new PcgwClient;

    $dota = $client->games(offset: 2, limit: 2)[0];
    $worldOfWarcraft = $client->games(offset: 4, limit: 2)[0];

    expect($dota->steamAppids)->toBe([570, 1241930, 652720]);
    expect($worldOfWarcraft->steamAppids)->toBe([]);
});

it('pauses before every request to stay under the rate limit', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/games-page-1')),
    ]);

    (new PcgwClient)->games(offset: 0, limit: 2);

    Sleep::assertSequence(array_fill(0, 4, Sleep::for(1100)->milliseconds()));
});

it('logs in again once when the session expires', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/permission-denied'))
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/games-page-1')),
    ]);

    $games = (new PcgwClient)->games(offset: 0, limit: 2);

    expect($games)->toHaveCount(2);
    Http::assertSentCount(7);
});

it('throws when permission is still denied after logging in again', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/permission-denied'))
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/permission-denied')),
    ]);

    expect(fn () => (new PcgwClient)->games(offset: 0, limit: 2))
        ->toThrow(PcgwException::class, 'permissiondenied');
});

it('throws without leaking the password when the login fails', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-failed')),
    ]);

    try {
        (new PcgwClient)->games(offset: 0, limit: 2);
        $this->fail('Expected a PcgwException.');
    } catch (PcgwException $exception) {
        expect($exception->getMessage())
            ->toContain('Incorrect username or password')
            ->not->toContain('test-bot-password');
    }
});

it('throws before making any request when credentials are missing', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake();
    config(['pcgw.bot_password' => null]);

    expect(fn () => (new PcgwClient)->games(offset: 0, limit: 2))
        ->toThrow(PcgwException::class, 'credentials are not configured');
    Http::assertNothingSent();
});

it('does not retry when Cloudflare blocks the request', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::response('<title>Just a moment...</title>', 403, ['cf-mitigated' => 'challenge']),
    ]);

    expect(fn () => (new PcgwClient)->games(offset: 0, limit: 2))
        ->toThrow(PcgwException::class, 'HTTP 403 (blocked by Cloudflare');
    Http::assertSentCount(1);
});

it('waits longer than the 60 second block before retrying a rate-limited request', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->pushStatus(429)
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(jsonFixture('pcgw/cargofields-video'))
            ->push(jsonFixture('pcgw/games-page-1')),
    ]);

    $games = (new PcgwClient)->games(offset: 0, limit: 2);

    expect($games)->toHaveCount(2);
    Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds === 61_000.0, times: 1);
});

it('gives up with a clear error when PCGamingWiki keeps failing', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::response('Bad Gateway', 502),
    ]);

    expect(fn () => (new PcgwClient)->games(offset: 0, limit: 2))
        ->toThrow(PcgwException::class, 'HTTP 502');
    Http::assertSentCount(3);
});

it('uses the field names reported by cargofields', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    $renamed = jsonFixture('pcgw/cargofields-video');
    $renamed['cargofields']['Frame_generation'] = $renamed['cargofields']['Frame_gen'];
    unset($renamed['cargofields']['Frame_gen']);
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push($renamed),
    ]);

    $fields = (new PcgwClient)->videoFields();

    expect($fields)->toBe([
        'upscaling' => 'Upscaling',
        'frame_gen' => 'Frame_generation',
        'ray_tracing' => 'Ray_tracing',
    ]);
});

it('falls back to the configured field names when cargofields fails', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'www.pcgamingwiki.com/w/api.php*' => Http::sequence()
            ->push(jsonFixture('pcgw/login-token'))
            ->push(jsonFixture('pcgw/login-success'))
            ->push(['error' => ['code' => 'internal_api_error_MWException', 'info' => 'Exception caught']]),
    ]);

    $fields = (new PcgwClient)->videoFields();

    expect($fields)->toBe([
        'upscaling' => 'Upscaling',
        'frame_gen' => 'Frame_gen',
        'ray_tracing' => 'Ray_tracing',
    ]);
});
