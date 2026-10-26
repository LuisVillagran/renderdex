<?php

namespace App\Services\Library;

use App\Enums\MatchSource;
use App\Models\Game;
use App\Models\GameSteamAppid;
use App\Services\Steam\OwnedGame;
use App\Services\TitleNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cruza la biblioteca de Steam con los juegos sincronizados desde PCGamingWiki.
 */
class LibraryMatcher
{
    /** Margen bajo el límite de parámetros por consulta de PostgreSQL (65.535). */
    private const CHUNK_SIZE = 5000;

    /** Igual que la caché de la biblioteca en SteamClient. */
    public const CACHE_TTL_SECONDS = 3600;

    public function __construct(private TitleNormalizer $normalizer) {}

    /**
     * Como {@see match()}, pero recuerda qué juego de la wiki corresponde a cada AppID para no
     * repetir el cruce en cada clic de filtro: después solo hace falta cargar esos juegos por ID.
     * La clave incluye la lista de AppIDs, así que si la biblioteca cambia se vuelve a cruzar.
     *
     * @param  list<OwnedGame>  $ownedGames
     * @return list<LibraryEntry> En el mismo orden que la biblioteca.
     */
    public function matchCached(string $steamId, array $ownedGames): array
    {
        $appids = implode(',', array_map(fn (OwnedGame $owned) => $owned->appid, $ownedGames));
        $key = 'library-match:'.hash('sha256', $steamId.'|'.$appids);

        $fresh = null;
        $links = Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($ownedGames, &$fresh) {
            $fresh = $this->match($ownedGames);

            return collect($fresh)
                ->filter(fn (LibraryEntry $entry) => $entry->game !== null)
                ->mapWithKeys(fn (LibraryEntry $entry) => [$entry->owned->appid => [$entry->game->id, $entry->matchedBy->value]])
                ->all();
        });

        if ($fresh !== null) {
            return $fresh;
        }

        $games = collect($links)
            ->pluck(0)
            ->unique()
            ->chunk(self::CHUNK_SIZE)
            ->flatMap(fn (Collection $ids) => Game::whereIn('id', $ids->values())->get())
            ->keyBy('id');

        return array_map(function (OwnedGame $owned) use ($links, $games) {
            [$gameId, $source] = $links[$owned->appid] ?? [null, null];
            $game = $gameId !== null ? $games->get($gameId) : null;

            // Si el juego desapareció de la base (p. ej. un sync --fresh), queda sin página.
            return new LibraryEntry($owned, $game, $game ? MatchSource::from($source) : null);
        }, $ownedGames);
    }

    /**
     * @param  list<OwnedGame>  $ownedGames
     * @return list<LibraryEntry> En el mismo orden que la biblioteca.
     */
    public function match(array $ownedGames): array
    {
        $byAppid = $this->gamesByAppid($ownedGames);
        $unmatched = array_filter($ownedGames, fn (OwnedGame $owned) => ! $byAppid->has($owned->appid));
        $byTitle = $this->gamesByTitle($unmatched);

        return array_map(function (OwnedGame $owned) use ($byAppid, $byTitle) {
            if ($game = $byAppid->get($owned->appid)) {
                return new LibraryEntry($owned, $game, MatchSource::AppId);
            }

            if ($game = $byTitle->get($this->normalizer->normalize($owned->name))) {
                return new LibraryEntry($owned, $game, MatchSource::Title);
            }

            return new LibraryEntry($owned, null, null);
        }, $ownedGames);
    }

    /**
     * @param  list<OwnedGame>  $ownedGames
     * @return Collection<int, Game> Indexado por Steam AppID.
     */
    private function gamesByAppid(array $ownedGames): Collection
    {
        return collect($ownedGames)
            ->pluck('appid')
            ->chunk(self::CHUNK_SIZE)
            ->flatMap(fn (Collection $appids) => GameSteamAppid::with('game')
                ->whereIn('steam_appid', $appids->values())
                ->get())
            ->mapWithKeys(fn (GameSteamAppid $link) => [$link->steam_appid => $link->game]);
    }

    /**
     * Solo considera juegos sin ningún AppID en la wiki y descarta títulos ambiguos.
     *
     * @param  array<int, OwnedGame>  $ownedGames
     * @return Collection<string, Game> Indexado por título normalizado.
     */
    private function gamesByTitle(array $ownedGames): Collection
    {
        return collect($ownedGames)
            ->map(fn (OwnedGame $owned) => $this->normalizer->normalize($owned->name))
            ->filter()
            ->unique()
            ->chunk(self::CHUNK_SIZE)
            ->flatMap(fn (Collection $titles) => Game::whereIn('normalized_title', $titles->values())
                ->whereDoesntHave('steamAppids')
                ->get())
            ->groupBy('normalized_title')
            ->filter(fn (Collection $candidates) => $candidates->count() === 1)
            ->map(fn (Collection $candidates) => $candidates->first());
    }
}
