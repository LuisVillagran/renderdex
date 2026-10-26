<?php

namespace App\Services\Library;

use App\Enums\Availability;
use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Services\GpuCapabilities;
use App\Services\SearchKey;
use Illuminate\Support\Collection;

/**
 * Búsqueda, filtros, orden y estadísticas de una biblioteca ya cruzada con la wiki.
 * Todo se aplica en el servidor, también para bibliotecas de miles de juegos.
 */
class LibraryBrowser
{
    public const SORTS = ['name', 'playtime'];

    public function __construct(
        private SearchKey $searchKey,
        private GpuCapabilities $gpus,
    ) {}

    /**
     * Cuántos juegos tienen cada capacidad que tu GPU puede usar de verdad.
     *
     * @param  list<LibraryEntry>  $entries
     * @return array<string, int> Indexado por el valor de {@see Capability}.
     */
    public function gpuStats(array $entries, GpuProfile $gpu): array
    {
        return collect(Capability::cases())
            ->mapWithKeys(fn (Capability $capability) => [
                $capability->value => collect($entries)->filter(fn (LibraryEntry $entry) => collect($capability->technologies())->contains(
                    fn (Technology $technology) => $this->gpus->availability($entry->game, $gpu, $technology) === Availability::Usable,
                ))->count(),
            ])
            ->all();
    }

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
        // Igual que el catálogo: todas las palabras, en cualquier orden y sin puntuación.
        $words = $this->searchKey->words($this->searchKey->make($search));
        $technologies = $this->validTechnologies($technologies);

        $results = collect($entries)
            ->when($words !== [], fn (Collection $entries) => $entries->filter(function (LibraryEntry $entry) use ($words) {
                $name = $this->searchKey->make($entry->owned->name);

                return collect($words)->every(fn (string $word) => str_contains($name, $word));
            }))
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
