<?php

namespace App\Services\Steam;

use App\Services\Steam\Exceptions\PrivateProfileException;
use App\Services\Steam\Exceptions\ProfileNotFoundException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class SteamClient
{
    private const LIBRARY_TTL_SECONDS = 3600;

    private const VANITY_NOT_FOUND = 42;

    /**
     * La UI muestra un aviso cuando la biblioteca es de ejemplo ({@see DemoSteamClient}).
     */
    public function isDemo(): bool
    {
        return false;
    }

    /**
     * @throws ProfileNotFoundException
     * @throws SteamUnavailableException
     */
    public function resolveSteamId(ProfileInput $profile): string
    {
        return $profile->needsResolving()
            ? $this->resolveVanity((string) $profile->vanity)
            : (string) $profile->steamId;
    }

    /**
     * @throws ProfileNotFoundException
     * @throws SteamUnavailableException
     */
    public function resolveVanity(string $vanity): string
    {
        $response = $this->get('ISteamUser/ResolveVanityURL/v1/', ['vanityurl' => $vanity])['response'] ?? [];

        if (($response['success'] ?? null) === self::VANITY_NOT_FOUND || blank($response['steamid'] ?? null)) {
            throw new ProfileNotFoundException('The custom URL does not match any Steam profile.');
        }

        return (string) $response['steamid'];
    }

    /**
     * Nombre visible y avatar del perfil, o null si Steam no lo encuentra.
     *
     * @throws SteamUnavailableException
     */
    public function playerSummary(string $steamId): ?PlayerSummary
    {
        $player = $this->get('ISteamUser/GetPlayerSummaries/v2/', ['steamids' => $steamId])['response']['players'][0] ?? null;

        if (! is_array($player) || blank($player['personaname'] ?? null)) {
            return null;
        }

        return new PlayerSummary(
            steamId: (string) $player['steamid'],
            name: (string) $player['personaname'],
            avatarUrl: $player['avatarmedium'] ?? null,
        );
    }

    /**
     * Biblioteca del usuario, en caché durante una hora. Los errores no se guardan en caché.
     *
     * @return list<OwnedGame>
     *
     * @throws PrivateProfileException
     * @throws SteamUnavailableException
     */
    public function ownedGames(string $steamId): array
    {
        // El SteamID se guarda como hash para que no quede en texto plano en la tabla de caché.
        $games = Cache::remember('steam:owned-games:'.hash('sha256', $steamId), self::LIBRARY_TTL_SECONDS, function () use ($steamId) {
            $response = $this->get('IPlayerService/GetOwnedGames/v1/', [
                'steamid' => $steamId,
                'include_appinfo' => 1,
                'include_played_free_games' => 1,
            ])['response'] ?? [];

            // Un perfil privado devuelve "response" vacío; una biblioteca vacía trae game_count = 0.
            if (! array_key_exists('games', $response) && ! array_key_exists('game_count', $response)) {
                throw new PrivateProfileException('The profile or its game details are private.');
            }

            return array_map(
                fn (array $game) => OwnedGame::fromApi($game)->toArray(),
                $response['games'] ?? [],
            );
        });

        return array_map(fn (array $game) => OwnedGame::fromApi($game), $games);
    }

    /**
     * AppIDs de la lista de deseados, en caché durante una hora.
     *
     * IWishlistService no está en la documentación oficial de la Steam Web API, así que cualquier
     * respuesta inesperada se trata como "Steam no disponible" y no afecta a la biblioteca.
     * Una lista privada y una vacía responden igual (sin "items"), así que no se distinguen.
     *
     * @return list<int>
     *
     * @throws PrivateProfileException
     * @throws SteamUnavailableException
     */
    public function wishlist(string $steamId): array
    {
        return Cache::remember('steam:wishlist:'.hash('sha256', $steamId), self::LIBRARY_TTL_SECONDS, function () use ($steamId) {
            $items = $this->get('IWishlistService/GetWishlist/v1/', ['steamid' => $steamId])['response']['items'] ?? null;

            if (! is_array($items) || $items === []) {
                throw new PrivateProfileException('The wishlist is private or empty.');
            }

            return collect($items)
                ->pluck('appid')
                ->filter(fn ($appid) => is_int($appid) && $appid > 0)
                ->unique()
                ->values()
                ->all();
        });
    }

    /**
     * @param  array<string, string|int>  $query
     * @return array<string, mixed>
     *
     * @throws SteamUnavailableException
     */
    private function get(string $endpoint, array $query): array
    {
        $key = config('services.steam.key');

        if (blank($key)) {
            throw new SteamUnavailableException('The Steam Web API key is not configured (STEAM_API_KEY).');
        }

        // Las excepciones originales no se encadenan: sus mensajes incluyen la URL completa,
        // con la API key y el SteamID, y no deben llegar a los logs.
        try {
            $data = Http::baseUrl(config('services.steam.api_url'))
                ->connectTimeout(3)
                ->timeout(8)
                ->retry(2, 300, fn (Throwable $exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()))
                ->get($endpoint, ['key' => $key, ...$query])
                ->throw()
                ->json();
        } catch (ConnectionException) {
            throw new SteamUnavailableException('Could not connect to the Steam Web API.');
        } catch (RequestException $exception) {
            throw new SteamUnavailableException("The Steam Web API responded with HTTP {$exception->response->status()}.");
        }

        if (! is_array($data)) {
            throw new SteamUnavailableException('The Steam Web API returned a response that is not JSON.');
        }

        return $data;
    }
}
