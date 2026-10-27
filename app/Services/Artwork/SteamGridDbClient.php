<?php

namespace App\Services\Artwork;

use App\Services\TitleNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Portadas verticales de SteamGridDB (https://www.steamgriddb.com/api/v2), el respaldo para los
 * juegos que Steam no cubre. Exige API key; solo lo llama `covers:sync`, nunca una visita.
 */
class SteamGridDbClient
{
    /** Solo portadas estáticas 2:3, sin contenido para adultos, humor ni riesgo de epilepsia. */
    private const GRID_FILTERS = [
        'dimensions' => '600x900',
        'types' => 'static',
        'nsfw' => 'false',
        'humor' => 'false',
        'epilepsy' => 'false',
    ];

    public function __construct(private TitleNormalizer $normalizer) {}

    public function isConfigured(): bool
    {
        return filled(config('services.steamgriddb.key'));
    }

    /**
     * La portada mejor votada del juego con ese AppID, o null si no tiene.
     *
     * @return array{url: string, thumb: string}|null
     *
     * @throws SteamGridDbException
     */
    public function coverForSteamApp(int $appid): ?array
    {
        return $this->firstGrid("grids/steam/{$appid}");
    }

    /**
     * Busca el juego por nombre y devuelve su portada solo si exactamente un resultado tiene
     * el mismo título normalizado: el mismo criterio conservador del cruce de bibliotecas.
     *
     * @return array{url: string, thumb: string}|null
     *
     * @throws SteamGridDbException
     */
    public function coverForTitle(string $title): ?array
    {
        $normalized = $this->normalizer->normalize($title);
        $results = $this->get('search/autocomplete/'.rawurlencode($title));

        $matches = collect($results ?? [])
            ->filter(fn ($game) => is_array($game) && is_int($game['id'] ?? null) && is_string($game['name'] ?? null))
            ->filter(fn (array $game) => $this->normalizer->normalize($game['name']) === $normalized)
            ->values();

        return $matches->count() === 1 ? $this->firstGrid("grids/game/{$matches[0]['id']}") : null;
    }

    /**
     * @return array{url: string, thumb: string}|null
     *
     * @throws SteamGridDbException
     */
    private function firstGrid(string $endpoint): ?array
    {
        // La API las ordena por puntaje: la primera es la preferida por la comunidad.
        $grid = collect($this->get($endpoint, self::GRID_FILTERS) ?? [])
            ->first(fn ($grid) => is_array($grid) && $this->isImageUrl($grid['url'] ?? null) && $this->isImageUrl($grid['thumb'] ?? null));

        return $grid ? ['url' => $grid['url'], 'thumb' => $grid['thumb']] : null;
    }

    private function isImageUrl(mixed $url): bool
    {
        return is_string($url) && str_starts_with($url, 'https://');
    }

    /**
     * El campo "data" de la respuesta, o null si el juego no existe en SteamGridDB (404).
     *
     * @param  array<string, string>  $query
     * @return array<int, mixed>|null
     *
     * @throws SteamGridDbException
     */
    private function get(string $endpoint, array $query = []): ?array
    {
        try {
            $response = Http::baseUrl(config('services.steamgriddb.api_url'))
                ->withToken((string) config('services.steamgriddb.key'))
                ->withUserAgent(config('pcgw.user_agent'))
                ->connectTimeout(5)
                ->timeout(20)
                ->retry(2, 1000, fn (Throwable $exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
                ->get($endpoint, $query);
        } catch (ConnectionException) {
            throw new SteamGridDbException('Could not connect to SteamGridDB.');
        }

        if ($response->notFound()) {
            return null;
        }

        if ($response->failed()) {
            throw new SteamGridDbException("SteamGridDB responded with HTTP {$response->status()}.");
        }

        $data = $response->json('data');

        if ($response->json('success') !== true || ! is_array($data)) {
            throw new SteamGridDbException('SteamGridDB returned an unexpected response.');
        }

        return $data;
    }
}
