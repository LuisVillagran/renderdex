<?php

namespace App\Livewire;

use App\Enums\Technology;
use App\Livewire\Concerns\WithTechnologyFilters;
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
            // Lo más útil primero: nombres de la A a la Z, y los juegos más jugados.
            $this->direction = $column === 'playtime' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function render(SteamClient $steam, LibraryMatcher $matcher, LibraryBrowser $browser): View
    {
        $error = null;
        $entries = [];

        try {
            $entries = $matcher->match($steam->ownedGames($this->steamId));
        } catch (PrivateProfileException) {
            $error = 'private';
        } catch (SteamUnavailableException) {
            $error = 'unavailable';
        }

        $results = $browser->browse($entries, $this->search, $this->filters, $this->sort, $this->direction);
        $page = $this->getPage();
        $gpuProfile = $this->gpuProfile();

        return view('livewire.library-table', [
            'gpuProfile' => $gpuProfile,
            'gpuStats' => $gpuProfile ? $browser->gpuStats($entries, $gpuProfile) : null,
            'error' => $error,
            'isDemo' => $steam->isDemo(),
            'stats' => $browser->stats($entries),
            'games' => new LengthAwarePaginator(
                $results->forPage($page, self::PER_PAGE)->values(),
                $results->count(),
                self::PER_PAGE,
                $page,
            ),
            'activeFilters' => array_map(fn (Technology $technology) => $technology->value, $this->validFilters()),
            'highlightedVendor' => $this->highlightedVendor(),
        ]);
    }
}
