<?php

use App\Services\Pcgw\PcgwCover;

it('normalizes the cover the wiki stores', function (?string $value, ?string $expected) {
    expect(PcgwCover::normalize($value))->toBe($expected);
})->with([
    'URL de la wiki' => ['https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg', 'https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg'],
    'pasa a https' => ['http://images.pcgamingwiki.com/3/3d/Hades_cover.jpg', 'https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg'],
    'nombre de archivo' => ['File:Hades cover.jpg', 'https://www.pcgamingwiki.com/wiki/Special:FilePath/Hades_cover.jpg'],
    'nombre sin prefijo' => ['Hades cover.jpg', 'https://www.pcgamingwiki.com/wiki/Special:FilePath/Hades_cover.jpg'],
    'otro dominio' => ['https://example.com/cover.jpg', null],
    'vacío' => ['  ', null],
    'null' => [null, null],
]);

it('asks for a thumbnail instead of the original image', function () {
    expect(PcgwCover::thumbnail('https://images.pcgamingwiki.com/3/3d/Hades_cover.jpg', 120))
        ->toBe('https://images.pcgamingwiki.com/thumb/3/3d/Hades_cover.jpg/120px-Hades_cover.jpg')
        ->and(PcgwCover::thumbnail('https://www.pcgamingwiki.com/wiki/Special:FilePath/Hades_cover.jpg', 120))
        ->toBe('https://www.pcgamingwiki.com/wiki/Special:FilePath/Hades_cover.jpg?width=120')
        ->and(PcgwCover::thumbnail('https://images.pcgamingwiki.com/other/path.jpg', 120))
        ->toBe('https://images.pcgamingwiki.com/other/path.jpg');
});
