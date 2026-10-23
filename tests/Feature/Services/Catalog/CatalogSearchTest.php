<?php

use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Services\Catalog\CatalogSearch;

function catalogTitles(string $query, array $technologies = [], int $page = 1): array
{
    return app(CatalogSearch::class)->search($query, $technologies, $page)->pluck('title')->all();
}

it('ranks the exact title first, then titles that start with the query, then the rest', function () {
    Game::factory()->create(['title' => 'The Hades Chronicles']);
    Game::factory()->create(['title' => 'Hades II']);
    Game::factory()->create(['title' => 'Hades']);
    Game::factory()->create(['title' => 'Celeste']);

    expect(catalogTitles('hades'))->toBe(['Hades', 'Hades II', 'The Hades Chronicles']);
});

it('ignores case, extra spaces and trademark symbols in the query', function () {
    Game::factory()->create(['title' => 'Diablo® IV']);

    expect(catalogTitles('  DIABLO   iv '))->toBe(['Diablo® IV'])
        ->and(catalogTitles('Diablo® IV'))->toBe(['Diablo® IV']);
});

it('searches percent signs and underscores as plain text', function () {
    Game::factory()->create(['title' => '100% Orange Juice']);
    Game::factory()->create(['title' => '1000 Tiny Claws']);
    Game::factory()->create(['title' => 'a_b']);
    Game::factory()->create(['title' => 'axb']);

    expect(catalogTitles('100%'))->toBe(['100% Orange Juice'])
        ->and(catalogTitles('a_b'))->toBe(['a_b']);
});

it('only returns games that support every selected technology', function () {
    Game::factory()->create(['title' => 'Both', 'dlss' => Support::Yes, 'ray_tracing' => Support::Yes]);
    Game::factory()->create(['title' => 'Only DLSS', 'dlss' => Support::Yes, 'ray_tracing' => Support::No]);
    Game::factory()->create(['title' => 'Unknown']);

    expect(catalogTitles('', [Technology::Dlss]))->toBe(['Both', 'Only DLSS'])
        ->and(catalogTitles('', [Technology::Dlss, Technology::RayTracing]))->toBe(['Both']);
});

it('lists the games with the most supported technologies first when there is no query', function () {
    Game::factory()->create(['title' => 'Nothing']);
    Game::factory()->create(['title' => 'Two', 'fsr' => Support::Yes, 'xess' => Support::Yes]);
    Game::factory()->create(['title' => 'Three', 'dlss' => Support::Yes, 'dlss_fg' => Support::Yes, 'ray_tracing' => Support::Yes]);
    Game::factory()->create(['title' => 'Also two', 'dlss' => Support::Yes, 'ray_tracing' => Support::Yes]);

    expect(catalogTitles(''))->toBe(['Three', 'Also two', 'Two', 'Nothing']);
});

it('paginates the results', function () {
    Game::factory()->count(CatalogSearch::PER_PAGE + 3)->create();

    $secondPage = app(CatalogSearch::class)->search('', [], 2);

    expect($secondPage->total())->toBe(CatalogSearch::PER_PAGE + 3)
        ->and($secondPage->count())->toBe(3);
});

it('loads the app ids in the order the wiki lists them', function () {
    $game = Game::factory()->create(['title' => 'Call of Duty: Modern Warfare 3']);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => 115300]);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => 42680]);

    $result = app(CatalogSearch::class)->search('modern warfare', [])->first();

    expect($result->steamAppids->first()->steam_appid)->toBe(115300);
});
