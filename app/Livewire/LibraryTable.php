<?php

namespace App\Livewire;

use App\Enums\Technology;
use App\Enums\Vendor;
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

    public const PER_PAGE = 25;

    /** Filtro que se preselecciona al elegir cada marca en "Mi GPU". */
    private const GPU_FILTERS = [
        'nvidia' => [Technology::Dlss],
        'amd' => [Technology::Fsr],
        'intel' => [Technology::Xess],
        'all' => [],
    ];

    #[Locked]
    public string $steamId;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var list<string> */
    #[Url(as: 'tech', except: [])]
    public array $filters = [];

    #[Url(except: 'name')]
    public string $sort = 'name';

    #[Url(as: 'dir', except: 'asc')]
    public string $direction = 'asc';

    /** Se recuerda en localStorage desde el navegador; ver la vista. */
    public string $gpu = 'all';

    public function mount(string $steamId): void
    {
        $this->steamId = $steamId;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleFilter(string $technology): void
    {
        if (Technology::tryFrom($technology) === null) {
            return;
        }

        $this->filters = in_array($technology, $this->filters, true)
            ? array_values(array_diff($this->filters, [$technology]))
            : [...$this->filters, $technology];

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'filters');
        $this->resetPage();
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

    /**
     * @param  bool  $preselectFilters  False al restaurar la elección guardada, para no pisar filtros de un link compartido.
     */
    public function selectGpu(string $gpu, bool $preselectFilters = true): void
    {
        if (! array_key_exists($gpu, self::GPU_FILTERS)) {
            return;
        }

        $this->gpu = $gpu;

        if ($preselectFilters) {
            $this->filters = array_map(fn (Technology $technology) => $technology->value, self::GPU_FILTERS[$gpu]);
            $this->resetPage();
        }
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

        return view('livewire.library-table', [
            'error' => $error,
            'isDemo' => $steam->isDemo(),
            'stats' => $browser->stats($entries),
            'games' => new LengthAwarePaginator(
                $results->forPage($page, self::PER_PAGE)->values(),
                $results->count(),
                self::PER_PAGE,
                $page,
            ),
            'activeFilters' => array_map(fn (Technology $technology) => $technology->value, $browser->validTechnologies($this->filters)),
            'highlightedVendor' => $this->gpu === 'all' ? null : Vendor::tryFrom($this->gpu),
        ]);
    }
}
