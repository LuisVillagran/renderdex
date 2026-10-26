<?php

use Illuminate\Support\Facades\Blade;

function renderCover(array $attributes): string
{
    return Blade::render('<x-game-cover :appid="$appid" :cover="$cover" :title="$title" :size="$size" />', [
        'appid' => null,
        'cover' => null,
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
