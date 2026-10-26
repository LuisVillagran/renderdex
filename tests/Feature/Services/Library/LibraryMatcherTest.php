<?php

use App\Enums\MatchSource;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Services\Library\LibraryMatcher;
use App\Services\Steam\OwnedGame;

function ownedGame(int $appid, string $name): OwnedGame
{
    return new OwnedGame(appid: $appid, name: $name, playtimeMinutes: 60);
}

it('matches library games to wiki pages by Steam app id', function () {
    $dota = Game::factory()->create(['title' => 'Dota 2']);
    GameSteamAppid::factory()->for($dota)->create(['steam_appid' => 570]);

    $entries = app(LibraryMatcher::class)->match([ownedGame(570, 'Dota 2')]);

    expect($entries[0])
        ->game->id->toBe($dota->id)
        ->matchedBy->toBe(MatchSource::AppId);
});

it('falls back to the exact title for wiki pages without app ids', function () {
    $euroTruck = Game::factory()->create(['title' => 'Euro Truck Simulator 2']);

    $entries = app(LibraryMatcher::class)->match([ownedGame(227300, 'Euro Truck Simulator 2™')]);

    expect($entries[0])
        ->game->id->toBe($euroTruck->id)
        ->matchedBy->toBe(MatchSource::Title);
});

it('prefers the app id match over a title match', function () {
    $byAppid = Game::factory()->create(['title' => 'Portal (2007)']);
    GameSteamAppid::factory()->for($byAppid)->create(['steam_appid' => 400]);
    Game::factory()->create(['title' => 'Portal']);

    $entries = app(LibraryMatcher::class)->match([ownedGame(400, 'Portal')]);

    expect($entries[0])
        ->game->id->toBe($byAppid->id)
        ->matchedBy->toBe(MatchSource::AppId);
});

it('does not match by title a wiki page that already has other app ids', function () {
    $dota = Game::factory()->create(['title' => 'Dota 2']);
    GameSteamAppid::factory()->for($dota)->create(['steam_appid' => 570]);

    $entries = app(LibraryMatcher::class)->match([ownedGame(999999, 'Dota 2')]);

    expect($entries[0])
        ->game->toBeNull()
        ->matchedBy->toBeNull();
});

it('does not match by title when several wiki pages share it', function () {
    Game::factory()->create(['title' => 'Doom']);
    Game::factory()->create(['title' => 'DOOM']);

    $entries = app(LibraryMatcher::class)->match([ownedGame(379720, 'DOOM')]);

    expect($entries[0]->game)->toBeNull();
});

it('does not match titles that only partially coincide', function () {
    Game::factory()->create(['title' => 'Cyberpunk 2077']);

    $entries = app(LibraryMatcher::class)->match([ownedGame(1, 'Cyberpunk 2077: Ultimate Edition')]);

    expect($entries[0]->game)->toBeNull();
});

it('keeps unmatched games and the library order', function () {
    $dota = Game::factory()->create(['title' => 'Dota 2']);
    GameSteamAppid::factory()->for($dota)->create(['steam_appid' => 570]);

    $entries = app(LibraryMatcher::class)->match([
        ownedGame(413150, 'Stardew Valley'),
        ownedGame(570, 'Dota 2'),
    ]);

    expect(array_map(fn ($entry) => $entry->owned->appid, $entries))->toBe([413150, 570]);
    expect($entries[0]->game)->toBeNull();
    expect($entries[1]->game->id)->toBe($dota->id);
});

it('returns no entries for an empty library', function () {
    expect(app(LibraryMatcher::class)->match([]))->toBe([]);
});

it('remembers the match so filter clicks only load the matched games by id', function () {
    $dota = Game::factory()->create(['title' => 'Dota 2']);
    GameSteamAppid::factory()->for($dota)->create(['steam_appid' => 570]);
    $euroTruck = Game::factory()->create(['title' => 'Euro Truck Simulator 2']);
    $library = [ownedGame(570, 'Dota 2'), ownedGame(227300, 'Euro Truck Simulator 2'), ownedGame(10, 'Not In Wiki')];
    $matcher = app(LibraryMatcher::class);

    $first = $matcher->matchCached('76561197960287930', $library);

    DB::enableQueryLog();
    $second = $matcher->matchCached('76561197960287930', $library);
    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1)
        ->and($queries[0]['query'])->toContain('from "games" where "id" in')
        ->and(array_map(fn ($entry) => [$entry->game?->id, $entry->matchedBy], $second))
        ->toBe(array_map(fn ($entry) => [$entry->game?->id, $entry->matchedBy], $first))
        ->and($second[1]->game->id)->toBe($euroTruck->id)
        ->and($second[1]->matchedBy)->toBe(MatchSource::Title);
});

it('matches again when the library changes', function () {
    $dota = Game::factory()->create(['title' => 'Dota 2']);
    GameSteamAppid::factory()->for($dota)->create(['steam_appid' => 570]);
    $matcher = app(LibraryMatcher::class);

    $matcher->matchCached('76561197960287930', [ownedGame(10, 'Not In Wiki')]);
    $entries = $matcher->matchCached('76561197960287930', [ownedGame(10, 'Not In Wiki'), ownedGame(570, 'Dota 2')]);

    expect($entries[1]->game?->id)->toBe($dota->id);
});
