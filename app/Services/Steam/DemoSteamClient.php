<?php

namespace App\Services\Steam;

/**
 * Reemplaza a SteamClient en desarrollo local cuando no hay STEAM_API_KEY.
 *
 * Devuelve una biblioteca de ejemplo con juegos reales (resources/demo/steam-library.json)
 * sin llamar a Steam, para poder ver la página de resultados con los datos de la wiki.
 * Nunca se usa en producción ni cuando hay una key configurada.
 */
class DemoSteamClient extends SteamClient
{
    /** Cualquier URL personalizada se resuelve a este perfil de ejemplo. */
    public const STEAM_ID = '76561197960287930';

    public static function shouldReplaceSteam(): bool
    {
        return app()->isLocal() && blank(config('services.steam.key'));
    }

    public function isDemo(): bool
    {
        return true;
    }

    public function resolveVanity(string $vanity): string
    {
        return self::STEAM_ID;
    }

    public function playerSummary(string $steamId): ?PlayerSummary
    {
        return null;
    }

    /**
     * @return list<OwnedGame>
     */
    public function ownedGames(string $steamId): array
    {
        $games = json_decode(file_get_contents(resource_path('demo/steam-library.json')), associative: true, flags: JSON_THROW_ON_ERROR);

        return array_map(fn (array $game) => OwnedGame::fromApi($game), $games);
    }

    /**
     * @return list<int>
     */
    public function wishlist(string $steamId): array
    {
        return json_decode(file_get_contents(resource_path('demo/steam-wishlist.json')), associative: true, flags: JSON_THROW_ON_ERROR);
    }
}
