<?php

use App\Models\Game;
use Illuminate\Support\Facades\Blade;

function renderCover(array $attributes): string
{
    return Blade::render('<x-game-cover :appid="$appid" :cover="$cover" :steam-artwork="$steamArtwork" :grid-cover="$gridCover" :grid-thumb="$gridThumb" :title="$title" :size="$size" />', [
        'appid' => null,
        'cover' => null,
        'steamArtwork' => null,
        'gridCover' => null,
        'gridThumb' => null,
        'title' => 'Hades',
        'size' => 'sm',
        ...$attributes,
    ]);
}

it('tries the Steam library cover, then the wiki cover, then the Steam header', function () {
    $html = renderCover(['appid' => 1145360, 'cover' => 'https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg']);

    expect($html)
        ->toContain('src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1145360/library_600x900.jpg"')
        ->toContain(e(json_encode([
            'https://images.pcgamingwiki.com/thumb/3/3d/Hades_cover.jpg/120px-Hades_cover.jpg',
            'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/1145360/header.jpg',
        ], JSON_UNESCAPED_SLASHES)))
        ->toContain('loading="lazy"');
});

it('uses the wiki cover for games without a Steam app id', function () {
    expect(renderCover(['cover' => 'https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg', 'size' => 'lg']))
        ->toContain('src="https://images.pcgamingwiki.com/thumb/3/3d/Hades_cover.jpg/480px-Hades_cover.jpg"');
});

it('shows the initials when there is no image at all', function () {
    $html = renderCover(['title' => 'The Witcher 3: Wild Hunt']);

    expect($html)->toContain('>TW</span>')->not->toContain('<img');
});

it('uses the saved steam artwork first and skips the fixed url that would fail', function () {
    $html = renderCover([
        'appid' => 3059520,
        'size' => 'card',
        'steamArtwork' => [
            'cover' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/abc/library_capsule.jpg',
            'cover_2x' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/abc/library_capsule_2x.jpg',
            'header' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/def/header.jpg',
            'hero' => null,
        ],
    ]);

    expect($html)
        ->toContain('src="https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/abc/library_capsule_2x.jpg"')
        ->toContain(e(json_encode(['https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/3059520/def/header.jpg'], JSON_UNESCAPED_SLASHES)))
        ->not->toContain('library_600x900.jpg');
});

it('tries the steamgriddb cover before the wiki cover', function () {
    $html = renderCover([
        'cover' => 'https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg',
        'gridCover' => 'https://cdn2.steamgriddb.com/grid/aaaa.png',
        'gridThumb' => 'https://cdn2.steamgriddb.com/thumb/aaaa.jpg',
    ]);

    expect($html)
        ->toContain('src="https://cdn2.steamgriddb.com/thumb/aaaa.jpg"')
        ->toContain(e(json_encode(['https://images.pcgamingwiki.com/thumb/3/3d/Hades_cover.jpg/120px-Hades_cover.jpg'], JSON_UNESCAPED_SLASHES)));
});

it('takes the saved covers from the game', function () {
    $game = Game::factory()->make([
        'grid_cover_url' => 'https://cdn2.steamgriddb.com/grid/aaaa.png',
        'grid_cover_thumb_url' => 'https://cdn2.steamgriddb.com/thumb/aaaa.jpg',
    ]);

    expect(Blade::render('<x-game-cover :game="$game" title="Hades" size="lg" />', ['game' => $game]))
        ->toContain('src="https://cdn2.steamgriddb.com/grid/aaaa.png"');
});
