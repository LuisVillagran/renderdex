<?php

namespace App\Services\Library;

use App\Enums\Support;
use App\Enums\Technology;
use Illuminate\Support\Collection;

/**
 * Búsqueda, filtros, orden y estadísticas de una biblioteca ya cruzada con la wiki.
 * Todo se aplica en el servidor, también para bibliotecas de miles de juegos.
 */
class LibraryBrowser
{
    public const SORTS = ['name', 'playtime'];

    /**
     * @param  list<LibraryEntry>  $entries
     * @return array{total: int, with_wiki_page: int, technologies: array<string, int>}
     */
    public function stats(array $entries): array
    {
        $entries = collect($entries);

        return [
            'total' => $entries->count(),
            'with_wiki_page' => $entries->filter(fn (LibraryEntry $entry) => $entry->game !== null)->count(),
            'technologies' => collect(Technology::cases())
                ->mapWithKeys(fn (Technology $technology) => [
                    $technology->value => $entries->filter(fn (LibraryEntry $entry) => $this->supports($entry, $technology))->count(),
                ])
                ->all(),
        ];
    }

    /**
     * @param  list<LibraryEntry>  $entries
     * @param  list<string>  $technologies  Valores de {@see Technology}; se exige soporte "Yes" en todas.
     * @return Collection<int, LibraryEntry>
     */
    public function browse(array $entries, string $search, array $technologies, string $sort, string $direction): Collection
    {
        $search = mb_strtolower(trim($search));
        $technologies = $this->validTechnologies($technologies);

        $results = collect($entries)
            ->when($search !== '', fn (Collection $entries) => $entries->filter(
                fn (LibraryEntry $entry) => str_contains(mb_strtolower($entry->owned->name), $search),
            ))
            ->filter(fn (LibraryEntry $entry) => collect($technologies)->every(
                fn (Technology $technology) => $this->supports($entry, $technology),
            ));

        $descending = $direction === 'desc';

        return match (in_array($sort, self::SORTS, true) ? $sort : 'name') {
            'playtime' => $results->sortBy([
                fn (LibraryEntry $a, LibraryEntry $b) => $descending
                    ? $b->owned->playtimeMinutes <=> $a->owned->playtimeMinutes
                    : $a->owned->playtimeMinutes <=> $b->owned->playtimeMinutes,
                fn (LibraryEntry $a, LibraryEntry $b) => strnatcasecmp($a->owned->name, $b->owned->name),
            ])->values(),
            default => $results->sort(fn (LibraryEntry $a, LibraryEntry $b) => $descending
                ? strnatcasecmp($b->owned->name, $a->owned->name)
                : strnatcasecmp($a->owned->name, $b->owned->name))->values(),
        };
    }

    /**
     * Descarta valores desconocidos, p. ej. si alguien edita los filtros en la URL.
     *
     * @param  list<string>  $technologies
     * @return list<Technology>
     */
    public function validTechnologies(array $technologies): array
    {
        return Technology::fromValues($technologies);
    }

    private function supports(LibraryEntry $entry, Technology $technology): bool
    {
        return $entry->game?->{$technology->value} === Support::Yes;
    }
}
