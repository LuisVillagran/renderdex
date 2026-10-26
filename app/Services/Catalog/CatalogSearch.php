<?php

namespace App\Services\Catalog;

use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;
use App\Services\GpuCapabilities;
use App\Services\SearchKey;
use App\Services\TechnologyFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Busca en todo el catálogo de la wiki, sin biblioteca ni cuenta de Steam.
 * Solo lee la base local: nunca consulta Steam ni PCGamingWiki.
 *
 * La búsqueda usa `games.search_title` ({@see SearchKey}): todas las palabras deben aparecer,
 * en cualquier orden. Si nada coincide, se buscan títulos parecidos con pg_trgm.
 */
class CatalogSearch
{
    public const PER_PAGE = 25;

    public const SUGGESTIONS = 6;

    public function __construct(
        private SearchKey $searchKey,
        private GpuCapabilities $gpus,
    ) {}

    /**
     * Con texto, primero el título exacto, luego los que empiezan igual, los que contienen la frase
     * y después el resto. Sin texto, los juegos con más tecnologías soportadas, para poder explorar.
     */
    public function search(string $query, TechnologyFilter $filter = new TechnologyFilter, int $page = 1): CatalogResults
    {
        $key = $this->searchKey->make($query);

        if ($key === '') {
            $games = $this->filtered($filter)->orderByRaw($this->supportedCountSql().' desc');

            return new CatalogResults($this->paginate($games, $page));
        }

        $results = $this->paginate($this->matching($this->filtered($filter), $key), $page);

        if ($results->total() > 0) {
            return new CatalogResults($results);
        }

        return new CatalogResults($this->paginate($this->similar($this->filtered($filter), $key), $page), approximate: true);
    }

    /**
     * Los mejores resultados para la vista previa del buscador, con el mismo orden que {@see search()}.
     *
     * @return Collection<int, Game>
     */
    public function suggest(string $query, int $limit = self::SUGGESTIONS): Collection
    {
        $key = $this->searchKey->make($query);

        if ($key === '') {
            return new Collection;
        }

        $games = $this->matching($this->filtered(new TechnologyFilter), $key)->limit($limit)->get();

        return $games->isNotEmpty()
            ? $games
            : $this->similar($this->filtered(new TechnologyFilter), $key)->limit($limit)->get();
    }

    /**
     * @return Builder<Game>
     */
    private function filtered(TechnologyFilter $filter): Builder
    {
        $games = Game::query()->with(['steamAppids' => fn ($appids) => $appids->orderBy('id')]);

        foreach ($filter->technologies as $technology) {
            $games->where($technology->value, Support::Yes->value);
        }

        // Cada capacidad: al menos una de sus tecnologías y, con GPU, solo las que puede usar.
        foreach ($filter->capabilities as $capability) {
            $technologies = $this->gpus->usableTechnologies($capability, $filter->gpu);

            $games->where(function (Builder $games) use ($technologies) {
                $games->whereRaw('false');

                foreach ($technologies as $technology) {
                    $games->orWhere($technology->value, Support::Yes->value);
                }
            });
        }

        if ($filter->onlyWithData) {
            $games->where('has_data', true);
        }

        return $games;
    }

    /**
     * @param  Builder<Game>  $games
     * @return Builder<Game>
     */
    private function matching(Builder $games, string $key): Builder
    {
        foreach ($this->searchKey->words($key) as $word) {
            $games->whereRaw("search_title like ? escape '\\'", ['%'.$this->escapeLike($word).'%']);
        }

        $escaped = $this->escapeLike($key);

        return $this->preferDocumented($games->orderByRaw(
            "case when search_title = ? then 0 when search_title like ? escape '\\' then 1 when search_title like ? escape '\\' then 2 else 3 end",
            [$key, $escaped.'%', '%'.$escaped.'%'],
        ));
    }

    /**
     * Títulos parecidos por trigramas, para tolerar errores de tipeo ("cyberpnk").
     * `<%` usa `pg_trgm.word_similarity_threshold` (0,6 por defecto) y aprovecha el índice GIN.
     *
     * @param  Builder<Game>  $games
     * @return Builder<Game>
     */
    private function similar(Builder $games, string $key): Builder
    {
        return $this->preferDocumented($games
            ->whereRaw('? <% search_title', [$key])
            ->orderByRaw('word_similarity(?, search_title) desc', [$key]));
    }

    /**
     * Desempate: primero los juegos con datos y con AppID, que suelen ser los que se buscan.
     *
     * @param  Builder<Game>  $games
     * @return Builder<Game>
     */
    private function preferDocumented(Builder $games): Builder
    {
        return $games
            ->orderByDesc('has_data')
            ->orderByRaw('exists (select 1 from game_steam_appids where game_steam_appids.game_id = games.id) desc');
    }

    /**
     * @param  Builder<Game>  $games
     * @return LengthAwarePaginator<int, Game>
     */
    private function paginate(Builder $games, int $page): LengthAwarePaginator
    {
        return $games->orderBy('title')->orderBy('id')->paginate(self::PER_PAGE, page: $page);
    }

    /**
     * Los comodines que escriba el usuario se buscan como texto, no como patrón.
     */
    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
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
