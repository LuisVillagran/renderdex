<?php

namespace App\Services\Catalog;

use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Services\TitleNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Busca en todo el catálogo de la wiki, sin biblioteca ni cuenta de Steam.
 * Solo lee la base local: nunca consulta Steam ni PCGamingWiki.
 */
class CatalogSearch
{
    public const PER_PAGE = 25;

    public function __construct(private TitleNormalizer $normalizer) {}

    /**
     * Con texto, primero el título exacto, luego los que empiezan igual y después el resto.
     * Sin texto, los juegos con más tecnologías soportadas, para poder explorar.
     *
     * @param  list<Technology>  $technologies  Se exige soporte "Yes" en todas.
     * @return LengthAwarePaginator<int, Game>
     */
    public function search(string $query, array $technologies, int $page = 1): LengthAwarePaginator
    {
        $query = $this->normalizer->normalize($query);

        $games = Game::query()->with(['steamAppids' => fn ($appids) => $appids->orderBy('id')]);

        foreach ($technologies as $technology) {
            $games->where($technology->value, Support::Yes->value);
        }

        if ($query === '') {
            $games->orderByRaw($this->supportedCountSql().' desc');
        } else {
            $this->matchTitle($games, $query);
        }

        return $games
            ->orderBy('title')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, page: $page);
    }

    /**
     * @param  Builder<Game>  $games
     */
    private function matchTitle(Builder $games, string $query): void
    {
        // Los comodines que escriba el usuario se buscan como texto, no como patrón.
        $escaped = addcslashes($query, '\\%_');

        $games
            ->whereRaw("normalized_title like ? escape '\\'", ['%'.$escaped.'%'])
            ->orderByRaw(
                "case when normalized_title = ? then 0 when normalized_title like ? escape '\\' then 1 else 2 end",
                [$query, $escaped.'%'],
            );
    }

    /**
     * Cuántas tecnologías tiene en "Yes" cada juego, calculado en SQL.
     */
    private function supportedCountSql(): string
    {
        return '('.collect(Technology::cases())
            ->map(fn (Technology $technology) => "case when {$technology->value} = '".Support::Yes->value."' then 1 else 0 end")
            ->implode(' + ').')';
    }
}
