<?php

namespace App\Services\Steam;

use App\Services\Steam\Exceptions\SteamUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Datos públicos de la tienda de Steam. No usa la API key ni datos de usuarios, así que no lo
 * reemplaza {@see DemoSteamClient}. Solo lo llama `covers:sync`, nunca una visita.
 */
class SteamStoreClient
{
    /** GetItems responde 400 con 500 AppIDs; 250 todavía funciona. */
    public const MAX_APPIDS_PER_REQUEST = 200;

    private const ASSETS_URL = 'https://shared.akamai.steamstatic.com/store_item_assets/';

    /**
     * URLs del arte de la tienda de cada AppID. Los juegos nuevos lo tienen en rutas con hash
     * (`<hash>/library_capsule.jpg`), así que la URL fija `library_600x900.jpg` les da 404.
     *
     * IStoreBrowseService no está en la documentación oficial de la Steam Web API: cualquier
     * respuesta inesperada se trata como "Steam no disponible". Los AppIDs que Steam no
     * conoce, o sin ningún arte, no aparecen en el resultado.
     *
     * @param  list<int>  $appids
     * @return array<int, array{cover: ?string, cover_2x: ?string, header: ?string, hero: ?string}>
     *
     * @throws SteamUnavailableException
     */
    public function artwork(array $appids): array
    {
        $items = $this->get('IStoreBrowseService/GetItems/v1/', [
            'ids' => array_map(fn (int $appid) => ['appid' => $appid], array_values($appids)),
            'context' => ['language' => 'english', 'country_code' => 'US'],
            'data_request' => ['include_assets' => true],
        ])['response']['store_items'] ?? null;

        if (! is_array($items)) {
            throw new SteamUnavailableException('The Steam store returned an unexpected response.');
        }

        $artwork = [];

        foreach ($items as $item) {
            $assets = $item['assets'] ?? null;
            $format = $assets['asset_url_format'] ?? null;

            if (($item['success'] ?? null) !== 1 || ! is_int($item['appid'] ?? null) || ! is_string($format) || ! str_contains($format, '${FILENAME}')) {
                continue;
            }

            $url = fn (string $field) => is_string($assets[$field] ?? null) && $assets[$field] !== ''
                ? self::ASSETS_URL.str_replace('${FILENAME}', $assets[$field], $format)
                : null;

            $urls = [
                'cover' => $url('library_capsule'),
                'cover_2x' => $url('library_capsule_2x'),
                'header' => $url('header'),
                'hero' => $url('library_hero'),
            ];

            if (array_filter($urls) !== []) {
                $artwork[$item['appid']] = $urls;
            }
        }

        return $artwork;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws SteamUnavailableException
     */
    private function get(string $endpoint, array $input): array
    {
        try {
            $data = Http::baseUrl(config('services.steam.api_url'))
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 1000, fn (Throwable $exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()))
                ->get($endpoint, ['input_json' => json_encode($input)])
                ->throw()
                ->json();
        } catch (ConnectionException) {
            throw new SteamUnavailableException('Could not connect to the Steam store.');
        } catch (RequestException $exception) {
            throw new SteamUnavailableException("The Steam store responded with HTTP {$exception->response->status()}.");
        }

        if (! is_array($data)) {
            throw new SteamUnavailableException('The Steam store returned a response that is not JSON.');
        }

        return $data;
    }
}
