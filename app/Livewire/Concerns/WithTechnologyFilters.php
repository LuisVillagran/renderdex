<?php

namespace App\Livewire\Concerns;

use App\Enums\Technology;
use App\Enums\Vendor;
use Livewire\Attributes\Url;

/**
 * Búsqueda por nombre, filtros por tecnología y selector "Mi GPU", compartidos por
 * la biblioteca y el catálogo. El componente debe usar WithPagination.
 */
trait WithTechnologyFilters
{
    /** Filtro que se preselecciona al elegir cada marca en "Mi GPU". */
    private const GPU_FILTERS = [
        'nvidia' => [Technology::Dlss],
        'amd' => [Technology::Fsr],
        'intel' => [Technology::Xess],
        'all' => [],
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var list<string> */
    #[Url(as: 'tech', except: [])]
    public array $filters = [];

    /** Se recuerda en localStorage desde el navegador; ver la vista. */
    public string $gpu = 'all';

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

    /**
     * Descarta valores desconocidos, p. ej. si alguien edita los filtros en la URL.
     *
     * @return list<Technology>
     */
    protected function validFilters(): array
    {
        return Technology::fromValues($this->filters);
    }

    protected function highlightedVendor(): ?Vendor
    {
        return $this->gpu === 'all' ? null : Vendor::tryFrom($this->gpu);
    }
}
