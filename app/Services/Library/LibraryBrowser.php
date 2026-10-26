<?php

namespace App\Services\Library;

use App\Enums\Availability;
use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Services\GpuCapabilities;
use App\Services\SearchKey;
use App\Services\TechnologyFilter;
use Illuminate\Support\Collection;

/**
 * Búsqueda, filtros, orden y estadísticas de una biblioteca ya cruzada con la wiki.
 * Todo se aplica en el servidor, también para bibliotecas de miles de juegos.
 */
class LibraryBrowser
{
    public const SORTS = ['name', 'playtime', 'technologies'];

    /** Cuántos juegos sin datos se sugieren para completar en la wiki. */
    public const MISSING_DATA_SUGGESTIONS = 5;

    public function __construct(
        private SearchKey $searchKey,
        private GpuCapabilities $gpus,
    ) {}

    /**
     * @param  list<LibraryEntry>  $entries
     * @param  GpuProfile|null  $gpu  Con GPU, las capacidades cuentan solo lo que se puede usar en ella.
     * @return array{total: int, with_wiki_page: int, with_data: int, technologies: array<string, int>, capabilities: array<string, int>}
     */
    public function stats(array $entries, ?GpuProfile $gpu = null): array
    {
        $entries = collect($entries);

        return [
            'total' => $entries->count(),
            'with_wiki_page' => $entries->filter(fn (LibraryEntry $entry) => $entry->game !== null)->count(),
            'with_data' => $entries->filter(fn (LibraryEntry $entry) => $this->hasData($entry))->count(),
            'technologies' => collect(Technology::cases())
                ->mapWithKeys(fn (Technology $technology) => [
                    $technology->value => $entries->filter(fn (LibraryEntry $entry) => $this->supports($entry, $technology))->count(),
                ])
                ->all(),
            'capabilities' => collect(Capability::cases())
                ->mapWithKeys(fn (Capability $capability) => [
                    $capability->value => $entries->filter(fn (LibraryEntry $entry) => $this->hasCapability($entry, $capability, $gpu))->count(),
                ])
                ->all(),
        ];
    }

    /**
     * Primero los juegos con datos; dentro de cada grupo, el orden elegido.
     *
     * @param  list<LibraryEntry>  $entries
     * @return Collection<int, LibraryEntry>
     */
    public function browse(array $entries, string $search, TechnologyFilter $filter, string $sort, string $direction): Collection
    {
        // Igual que el catálogo: todas las palabras, en cualquier orden y sin puntuación.
        $words = $this->searchKey->words($this->searchKey->make($search));

        $results = collect($entries)
            ->when($words !== [], fn (Collection $entries) => $entries->filter(function (LibraryEntry $entry) use ($words) {
                $name = $this->searchKey->make($entry->owned->name);

                return collect($words)->every(fn (string $word) => str_contains($name, $word));
            }))
            ->when($filter->onlyWithData, fn (Collection $entries) => $entries->filter(fn (LibraryEntry $entry) => $this->hasData($entry)))
            ->filter(fn (LibraryEntry $entry) => collect($filter->technologies)->every(
                fn (Technology $technology) => $this->supports($entry, $technology),
            ))
            ->filter(fn (LibraryEntry $entry) => collect($filter->capabilities)->every(
                fn (Capability $capability) => $this->hasCapability($entry, $capability, $filter->gpu),
            ));

        $descending = $direction === 'desc';
        $byName = fn (LibraryEntry $a, LibraryEntry $b) => strnatcasecmp($a->owned->name, $b->owned->name);
        $byNumber = fn (callable $number) => fn (LibraryEntry $a, LibraryEntry $b) => $descending
            ? $number($b) <=> $number($a)
            : $number($a) <=> $number($b);

        return $results->sortBy([
            fn (LibraryEntry $a, LibraryEntry $b) => $this->hasData($b) <=> $this->hasData($a),
            ...match (in_array($sort, self::SORTS, true) ? $sort : 'name') {
                'playtime' => [$byNumber(fn (LibraryEntry $entry) => $entry->owned->playtimeMinutes), $byName],
                // Con GPU, cuenta solo lo que se puede usar en ella.
                'technologies' => [$byNumber(fn (LibraryEntry $entry) => $this->technologyCount($entry, $filter->gpu)), $byName],
                default => [fn (LibraryEntry $a, LibraryEntry $b) => $descending ? $byName($b, $a) : $byName($a, $b)],
            },
        ])->values();
    }

    /**
     * Los juegos más jugados que la wiki no documenta: los que más vale la pena completar.
     *
     * @param  list<LibraryEntry>  $entries
     * @return Collection<int, LibraryEntry>
     */
    public function mostPlayedWithoutData(array $entries, int $limit = self::MISSING_DATA_SUGGESTIONS): Collection
    {
        return collect($entries)
            ->reject(fn (LibraryEntry $entry) => $this->hasData($entry))
            ->filter(fn (LibraryEntry $entry) => $entry->owned->playtimeMinutes > 0)
            ->sortByDesc(fn (LibraryEntry $entry) => $entry->owned->playtimeMinutes)
            ->take($limit)
            ->values();
    }

    public function hasData(LibraryEntry $entry): bool
    {
        return (bool) $entry->game?->has_data;
    }

    private function technologyCount(LibraryEntry $entry, ?GpuProfile $gpu): int
    {
        return collect(Technology::cases())
            ->filter(fn (Technology $technology) => $gpu === null
                ? $this->supports($entry, $technology)
                : $this->gpus->availability($entry->game, $gpu, $technology) === Availability::Usable)
            ->count();
    }

    private function hasCapability(LibraryEntry $entry, Capability $capability, ?GpuProfile $gpu): bool
    {
        return collect($this->gpus->usableTechnologies($capability, $gpu))
            ->contains(fn (Technology $technology) => $this->supports($entry, $technology));
    }

    private function supports(LibraryEntry $entry, Technology $technology): bool
    {
        return $entry->game?->{$technology->value} === Support::Yes;
    }
}
