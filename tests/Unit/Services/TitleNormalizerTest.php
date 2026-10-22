<?php

use App\Services\TitleNormalizer;

it('removes typographic differences between Steam and wiki titles', function (string $title, string $expected) {
    expect((new TitleNormalizer)->normalize($title))->toBe($expected);
})->with([
    'mayúsculas' => ['Euro Truck Simulator 2', 'euro truck simulator 2'],
    'marca registrada' => ['DOOM™ Eternal®', 'doom eternal'],
    'copyright' => ['Tetris© Effect', 'tetris effect'],
    'apóstrofe tipográfico' => ['Baldur’s Gate 3', "baldur's gate 3"],
    'comillas tipográficas' => ['“Glitch” Hunter', '"glitch" hunter'],
    'guion largo' => ['Halo — Reach', 'halo - reach'],
    'espacios repetidos y alrededor' => ['  Hollow   Knight ', 'hollow knight'],
    'caracteres no latinos' => ['ÖKOLOGIE Ñandú', 'ökologie ñandú'],
]);

it('keeps words that tell editions apart', function () {
    $normalizer = new TitleNormalizer;

    expect($normalizer->normalize('Cyberpunk 2077: Ultimate Edition'))
        ->not->toBe($normalizer->normalize('Cyberpunk 2077'));
});
