<?php

use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Services\Catalog\CatalogSearch;
use App\Services\TechnologyFilter;

function catalogTitles(string $query, array $technologies = [], int $page = 1): array
{
    return app(CatalogSearch::class)->search($query, new TechnologyFilter(technologies: $technologies), $page)->games->pluck('title')->all();
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

it('ignores punctuation and accepts the words in any order', function () {
    Game::factory()->create(['title' => 'The Witcher 3: Wild Hunt']);
    Game::factory()->create(['title' => 'The Witcher 2: Assassins of Kings']);

    expect(catalogTitles('witcher 3 wild hunt'))->toBe(['The Witcher 3: Wild Hunt'])
        ->and(catalogTitles('wild hunt witcher'))->toBe(['The Witcher 3: Wild Hunt']);
});

it('matches roman numerals and digits both ways', function () {
    Game::factory()->create(['title' => 'Grand Theft Auto V']);
    Game::factory()->create(['title' => 'DOOM 2']);

    expect(catalogTitles('grand theft auto 5'))->toBe(['Grand Theft Auto V'])
        ->and(catalogTitles('doom ii'))->toBe(['DOOM 2']);
});

it('ranks games with wiki data and a Steam app id first among equal matches', function () {
    Game::factory()->create(['title' => 'Doom A']);
    $withAppid = Game::factory()->create(['title' => 'Doom B']);
    GameSteamAppid::factory()->for($withAppid)->create();
    Game::factory()->create(['title' => 'Doom C', 'dlss' => Support::Yes]);

    expect(catalogTitles('doom'))->toBe(['Doom C', 'Doom B', 'Doom A']);
});

it('falls back to similar titles when nothing matches, for typos', function () {
    Game::factory()->create(['title' => 'Cyberpunk 2077']);
    Game::factory()->create(['title' => 'Celeste']);

    $results = app(CatalogSearch::class)->search('cyberpnk');

    expect($results->approximate)->toBeTrue()
        ->and($results->games->pluck('title')->all())->toBe(['Cyberpunk 2077']);
});

it('does not mark exact matches as approximate', function () {
    Game::factory()->create(['title' => 'Cyberpunk 2077']);

    expect(app(CatalogSearch::class)->search('cyberpunk')->approximate)->toBeFalse();
});

it('suggests the best matches for the search preview', function () {
    Game::factory()->create(['title' => 'Hades II']);
    Game::factory()->create(['title' => 'Hades']);
    Game::factory()->create(['title' => 'Celeste']);

    $search = app(CatalogSearch::class);

    expect($search->suggest('hades')->pluck('title')->all())->toBe(['Hades', 'Hades II'])
        ->and($search->suggest('hades', limit: 1)->pluck('title')->all())->toBe(['Hades'])
        ->and($search->suggest('hadez')->pluck('title')->all())->toContain('Hades')
        ->and($search->suggest('  '))->toBeEmpty();
});

it('filters by capability: any technology of it, and only the ones the GPU can use', function () {
    Game::factory()->create(['title' => 'DLSS FG', 'dlss_fg' => Support::Yes]);
    Game::factory()->create(['title' => 'FSR FG', 'fsr_fg' => Support::Yes]);
    Game::factory()->create(['title' => 'No FG', 'dlss' => Support::Yes]);

    $search = app(CatalogSearch::class);
    $titles = fn (TechnologyFilter $filter) => $search->search('', $filter)->games->pluck('title')->sort()->values()->all();

    expect($titles(new TechnologyFilter(capabilities: [Capability::FrameGeneration])))->toBe(['DLSS FG', 'FSR FG'])
        // DLSS FG requiere RTX 40: en una RTX 30 solo sirve FSR FG.
        ->and($titles(new TechnologyFilter(capabilities: [Capability::FrameGeneration], gpu: GpuProfile::Rtx20To30)))->toBe(['FSR FG'])
        // En una GTX ninguna generación de frames es usable.
        ->and($titles(new TechnologyFilter(capabilities: [Capability::FrameGeneration], gpu: GpuProfile::Gtx)))->toBe([]);
});

it('can leave out the games without wiki data', function () {
    Game::factory()->create(['title' => 'Documented', 'ray_tracing' => Support::No]);
    Game::factory()->create(['title' => 'Empty']);

    expect(app(CatalogSearch::class)->search('', new TechnologyFilter(onlyWithData: true))->games->pluck('title')->all())->toBe(['Documented']);
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

    $secondPage = app(CatalogSearch::class)->search('', page: 2)->games;

    expect($secondPage->total())->toBe(CatalogSearch::PER_PAGE + 3)
        ->and($secondPage->count())->toBe(3);
});

it('loads the app ids in the order the wiki lists them', function () {
    $game = Game::factory()->create(['title' => 'Call of Duty: Modern Warfare 3']);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => 115300]);
    GameSteamAppid::factory()->for($game)->create(['steam_appid' => 42680]);

    $result = app(CatalogSearch::class)->search('modern warfare')->games->first();

    expect($result->steamAppids->first()->steam_appid)->toBe(115300);
});
