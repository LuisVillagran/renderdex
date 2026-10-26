<?php

use App\Services\SearchKey;
use App\Services\TitleNormalizer;

function searchKey(): SearchKey
{
    return new SearchKey(new TitleNormalizer);
}

it('builds a lenient search key from a title', function (string $title, string $expected) {
    expect(searchKey()->make($title))->toBe($expected);
})->with([
    'dos puntos y mayúsculas' => ['The Witcher 3: Wild Hunt', 'the witcher 3 wild hunt'],
    'guiones' => ['Half-Life 2', 'half life 2'],
    'apóstrofes' => ['Assassin’s Creed', 'assassins creed'],
    'números romanos' => ['DOOM II', 'doom 2'],
    'romanos con marca registrada' => ['Diablo® IV', 'diablo 4'],
    'ampersand' => ['Ratchet & Clank', 'ratchet and clank'],
    'paréntesis' => ['Prey (2017)', 'prey 2017'],
    'espacios repetidos' => ['  Hollow   Knight ', 'hollow knight'],
    'conserva porcentajes' => ['100% Orange Juice', '100% orange juice'],
    'conserva guion bajo' => ['a_b', 'a_b'],
    'caracteres no latinos' => ['ÖKOLOGIE Ñandú', 'ökologie ñandú'],
]);

it('only converts standalone roman numerals', function () {
    expect(searchKey()->make('Civilization VI'))->toBe('civilization 6')
        ->and(searchKey()->make('Vivid Knight'))->toBe('vivid knight')
        ->and(searchKey()->make('Mixology'))->toBe('mixology');
});

it('splits a key into words', function () {
    expect(searchKey()->words('the witcher 3'))->toBe(['the', 'witcher', '3'])
        ->and(searchKey()->words(''))->toBe([]);
});
