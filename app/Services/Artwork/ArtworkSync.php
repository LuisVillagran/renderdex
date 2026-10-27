<?php

namespace App\Services\Artwork;

use App\Models\Game;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\SteamStoreClient;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Sleep;

/**
 * Guarda en `games` las portadas que no se pueden adivinar con una URL fija: el arte de la
 * tienda de Steam (rutas con hash en los juegos nuevos) y, para lo que Steam no cubre,
 * la portada de SteamGridDB.
 */
class ArtworkSync
{
    /** El arte de Steam cambia poco: se vuelve a pedir una vez por semana. */
    public const STEAM_REFRESH_DAYS = 7;

    /** Un juego sin portada en SteamGridDB se vuelve a buscar al mes. */
    public const GRID_REFRESH_DAYS = 30;

    private const GAMES_PER_CHUNK = 100;

    public function __construct(
        private SteamStoreClient $steam,
        private SteamGridDbClient $grids,
    ) {}

    /**
     * @return Builder<Game>
     */
    public function pendingSteam(bool $fresh = false): Builder
    {
        return Game::query()
            ->whereHas('steamAppids')
            ->unless($fresh, fn (Builder $games) => $games->where(fn (Builder $games) => $games
                ->whereNull('steam_artwork_synced_at')
                ->orWhere('steam_artwork_synced_at', '<', now()->subDays(self::STEAM_REFRESH_DAYS))));
    }

    /**
     * Pide el arte de todos los AppIDs de cada juego y se queda con el primero, en el orden de
     * la wiki, que tenga portada vertical (si ninguno la tiene, con el primero que tenga algo).
     *
     * @param  Closure(int): void  $advance  Recibe cuántos juegos se procesaron.
     * @return int Juegos procesados.
     *
     * @throws SteamUnavailableException
     */
    public function syncSteam(bool $fresh, ?int $limit, Closure $advance): int
    {
        $processed = 0;

        // Por lotes: cargar las decenas de miles de juegos de una vez agota la memoria.
        $this->pendingSteam($fresh)
            ->with(['steamAppids' => fn ($appids) => $appids->orderBy('id')])
            ->select('id')
            ->chunkById(self::GAMES_PER_CHUNK, function (Collection $games) use ($limit, $advance, &$processed) {
                if ($limit !== null) {
                    $games = $games->take($limit - $processed);
                }

                $artwork = $this->steamArtwork($games->flatMap(fn (Game $game) => $game->steamAppids->pluck('steam_appid'))->unique()->values()->all());

                foreach ($games as $game) {
                    $candidates = $game->steamAppids->map(fn ($appid) => $artwork[$appid->steam_appid] ?? null)->filter();

                    $game->update([
                        'steam_artwork' => $candidates->first(fn (array $urls) => $urls['cover'] !== null) ?? $candidates->first(),
                        'steam_artwork_synced_at' => now(),
                    ]);
                }

                $processed += $games->count();
                $advance($games->count());

                return $limit === null || $processed < $limit;
            });

        return $processed;
    }

    public function gridsConfigured(): bool
    {
        return $this->grids->isConfigured();
    }

    /**
     * Juegos sin portada vertical de Steam, primero los que tienen datos de render.
     *
     * @return Builder<Game>
     */
    public function pendingGrids(bool $fresh = false): Builder
    {
        return Game::query()
            ->whereRaw("steam_artwork->>'cover' is null")
            ->unless($fresh, fn (Builder $games) => $games->where(fn (Builder $games) => $games
                ->whereNull('grid_checked_at')
                ->orWhere('grid_checked_at', '<', now()->subDays(self::GRID_REFRESH_DAYS))));
    }

    /**
     * Busca en SteamGridDB por AppID o, si el juego no tiene, por nombre exacto.
     *
     * @param  Closure(int): void  $advance  Recibe cuántos juegos se procesaron.
     * @return int Juegos procesados.
     *
     * @throws SteamGridDbException
     */
    public function syncGrids(bool $fresh, int $limit, Closure $advance): int
    {
        $games = $this->pendingGrids($fresh)
            ->with(['steamAppids' => fn ($appids) => $appids->orderBy('id')])
            ->orderByDesc('has_data')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($games as $index => $game) {
            if ($index > 0) {
                Sleep::for(config('services.steamgriddb.pause_ms'))->milliseconds();
            }

            $appid = $game->primarySteamAppid();
            $cover = $appid !== null ? $this->grids->coverForSteamApp($appid) : $this->grids->coverForTitle($game->title);

            $game->update([
                'grid_cover_url' => $cover['url'] ?? null,
                'grid_cover_thumb_url' => $cover['thumb'] ?? null,
                'grid_checked_at' => now(),
            ]);

            $advance(1);
        }

        return $games->count();
    }

    /**
     * @param  list<int>  $appids
     * @return array<int, array{cover: ?string, cover_2x: ?string, header: ?string, hero: ?string}>
     *
     * @throws SteamUnavailableException
     */
    private function steamArtwork(array $appids): array
    {
        $artwork = [];

        foreach (array_chunk($appids, SteamStoreClient::MAX_APPIDS_PER_REQUEST) as $chunk) {
            $artwork += $this->steam->artwork($chunk);

            Sleep::for(config('services.steam.store_pause_ms'))->milliseconds();
        }

        return $artwork;
    }
}
