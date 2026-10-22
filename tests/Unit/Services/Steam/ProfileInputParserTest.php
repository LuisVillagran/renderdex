<?php

use App\Services\Steam\InvalidProfileInputException;
use App\Services\Steam\ProfileInputParser;

it('extracts the SteamID64 from profile URLs', function (string $input) {
    $profile = (new ProfileInputParser)->parse($input);

    expect($profile)
        ->steamId->toBe('76561197960287930')
        ->vanity->toBeNull()
        ->needsResolving()->toBeFalse();
})->with([
    'https' => 'https://steamcommunity.com/profiles/76561197960287930',
    'http' => 'http://steamcommunity.com/profiles/76561197960287930',
    'sin esquema' => 'steamcommunity.com/profiles/76561197960287930',
    'con www' => 'https://www.steamcommunity.com/profiles/76561197960287930',
    'barra final' => 'https://steamcommunity.com/profiles/76561197960287930/',
    'subpágina de juegos' => 'https://steamcommunity.com/profiles/76561197960287930/games/?tab=all',
    'con query y fragmento' => 'https://steamcommunity.com/profiles/76561197960287930?l=spanish#top',
    'host en mayúsculas' => 'HTTPS://SteamCommunity.com/profiles/76561197960287930',
]);

it('accepts a bare SteamID64', function () {
    $profile = (new ProfileInputParser)->parse('76561197960287930');

    expect($profile->steamId)->toBe('76561197960287930');
});

it('extracts the custom URL from vanity profile URLs', function (string $input) {
    $profile = (new ProfileInputParser)->parse($input);

    expect($profile)
        ->steamId->toBeNull()
        ->vanity->toBe('gabelogannewell')
        ->needsResolving()->toBeTrue();
})->with([
    'https' => 'https://steamcommunity.com/id/gabelogannewell',
    'sin esquema' => 'steamcommunity.com/id/gabelogannewell',
    'barra final' => 'https://steamcommunity.com/id/gabelogannewell/',
    'subpágina' => 'https://steamcommunity.com/id/gabelogannewell/games',
]);

it('treats a bare word as a custom URL', function (string $input, string $vanity) {
    $profile = (new ProfileInputParser)->parse($input);

    expect($profile->vanity)->toBe($vanity);
})->with([
    'letras' => ['gabelogannewell', 'gabelogannewell'],
    'guiones y guion bajo' => ['luis_villa-06', 'luis_villa-06'],
    'espacios alrededor' => ['  gabelogannewell  ', 'gabelogannewell'],
    'solo números que no son un SteamID64' => ['123456', '123456'],
]);

it('rejects input that is not a Steam profile', function (string $input) {
    (new ProfileInputParser)->parse($input);
})->throws(InvalidProfileInputException::class)->with([
    'vacío' => '',
    'solo espacios' => '   ',
    'texto con espacios' => 'gabe newell',
    'caracteres no permitidos' => 'gabe@newell',
    'vanity demasiado larga' => str_repeat('a', 33),
    'vanity de un carácter' => 'a',
    'otro dominio' => 'https://example.com/profiles/76561197960287930',
    'dominio que imita a Steam' => 'https://steamcommunity.com.evil.example/profiles/76561197960287930',
    'subdominio de otro sitio' => 'https://steamcommunity.evil.example/id/gabelogannewell',
    'tienda de Steam' => 'https://store.steampowered.com/app/570',
    'profiles con texto' => 'https://steamcommunity.com/profiles/gabelogannewell',
    'profiles con ID corto' => 'https://steamcommunity.com/profiles/12345',
    'id vacío' => 'https://steamcommunity.com/id/',
    'solo el dominio' => 'https://steamcommunity.com',
    'ruta desconocida' => 'https://steamcommunity.com/groups/valve',
    'SteamID64 bajo el rango individual' => 'https://steamcommunity.com/profiles/76561197960265728',
]);
