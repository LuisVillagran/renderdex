<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithTechnologyFilters;
use App\Models\GameChange;
use App\Services\Library\LibraryBrowser;
use App\Services\Library\LibraryMatcher;
use App\Services\Steam\Exceptions\PrivateProfileException;
use App\Services\Steam\Exceptions\SteamUnavailableException;
use App\Services\Steam\SteamClient;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tabla de la biblioteca: búsqueda, filtros, orden, paginación y selector "Mi GPU".
 */
class LibraryTable extends Component
{
    use WithPagination;
    use WithTechnologyFilters;

    public const PER_PAGE = 25;

    #[Locked]
    public string $steamId;

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(as: 'dir', except: 'asc')]
    public string $direction = 'asc';

    public function mount(string $steamId): void
    {
        $this->steamId = $steamId;
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, LibraryBrowser::SORTS, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            // Lo más útil primero: nombres de la A a la Z, los más jugados y los que soportan más.
            $this->direction = $column === 'name' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function render(SteamClient $steam, LibraryMatcher $matcher, LibraryBrowser $browser): View
    {
        $error = null;
        $entries = [];

        try {
            $entries = $matcher->matchCached($this->steamId, $steam->ownedGames($this->steamId));
        } catch (PrivateProfileException) {
            $error = 'private';
        } catch (SteamUnavailableException) {
            $error = 'unavailable';
        }

        $filter = $this->technologyFilter();
        $results = $browser->browse($entries, $this->search, $filter, $this->sort, $this->direction);
        $page = $this->getPage();
        $pageEntries = $results->forPage($page, self::PER_PAGE)->values();

        return view('livewire.library-table', [
            // Juegos de esta página con cambios en la wiki esta semana, para marcarlos como novedad.
            'recentlyChanged' => GameChange::recent()
                ->whereIn('game_id', $pageEntries->map(fn ($entry) => $entry->game?->id)->filter()->values())
                ->distinct()
                ->pluck('game_id')
                ->flip(),
            'error' => $error,
            'isDemo' => $steam->isDemo(),
            'stats' => $browser->stats($entries, $filter->gpu),
            'missingData' => $browser->mostPlayedWithoutData($entries),
            'games' => new LengthAwarePaginator(
                $pageEntries,
                $results->count(),
                self::PER_PAGE,
                $page,
            ),
            'filter' => $filter,
            'hasActiveFilters' => $this->hasActiveFilters(),
            'gpuProfile' => $filter->gpu,
            'highlightedVendor' => $this->highlightedVendor(),
        ]);
    }
}
