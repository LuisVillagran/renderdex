<?php

namespace App\Livewire\Concerns;

use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Technology;
use App\Enums\Vendor;
use App\Services\TechnologyFilter;
use Livewire\Attributes\Url;

/**
 * Búsqueda por nombre, filtros y selector "Mi GPU", compartidos por la biblioteca y el catálogo.
 * El componente debe usar WithPagination.
 *
 * Los filtros principales son por intención ({@see Capability}: "quiero reescalado"); con una
 * generación de GPU elegida, cuentan solo lo que esa GPU puede usar. Los filtros por tecnología
 * exacta quedan en "Más filtros".
 */
trait WithTechnologyFilters
{
    private const GPUS = ['nvidia', 'amd', 'intel', 'all'];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** @var list<string> */
    #[Url(as: 'cap', except: [])]
    public array $capabilities = [];

    /** @var list<string> */
    #[Url(as: 'tech', except: [])]
    public array $filters = [];

    #[Url(as: 'data', except: false)]
    public bool $onlyWithData = false;

    /** Se recuerda en localStorage desde el navegador; ver la vista. */
    public string $gpu = 'all';

    /** Generación dentro de la marca ({@see GpuProfile}); null si solo se eligió la marca. */
    public ?string $gpuModel = null;

    /** Cuadrícula de portadas o tabla. Se recuerda en localStorage; ver x-view-switcher. */
    public string $layout = 'grid';

    public function setLayout(string $layout): void
    {
        if (in_array($layout, ['grid', 'table'], true)) {
            $this->layout = $layout;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyWithData(): void
    {
        $this->resetPage();
    }

    public function toggleCapability(string $capability): void
    {
        if (Capability::tryFrom($capability) === null) {
            return;
        }

        $this->capabilities = $this->toggled($this->capabilities, $capability);
        $this->resetPage();
    }

    public function toggleFilter(string $technology): void
    {
        if (Technology::tryFrom($technology) === null) {
            return;
        }

        $this->filters = $this->toggled($this->filters, $technology);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'capabilities', 'filters', 'onlyWithData');
        $this->resetPage();
    }

    public function selectGpu(string $gpu): void
    {
        if (! in_array($gpu, self::GPUS, true)) {
            return;
        }

        $this->gpu = $gpu;

        // La generación elegida solo vale para su marca.
        if ($this->gpuProfile() === null) {
            $this->gpuModel = null;
        }

        $this->resetPage();
    }

    /**
     * @param  string  $model  Valor de {@see GpuProfile}, o vacío para "sin especificar".
     */
    public function selectGpuModel(string $model): void
    {
        $profile = GpuProfile::tryFrom($model);

        $this->gpuModel = $profile?->vendor()->value === $this->gpu ? $profile->value : null;
        $this->resetPage();
    }

    /**
     * Restaura lo guardado en localStorage en un solo viaje al servidor.
     */
    public function restoreGpu(string $gpu, string $model): void
    {
        $this->selectGpu($gpu);
        $this->selectGpuModel($model);
    }

    /**
     * Los filtros activos, ya validados (p. ej. si alguien edita la URL).
     */
    protected function technologyFilter(): TechnologyFilter
    {
        return new TechnologyFilter(
            technologies: Technology::fromValues($this->filters),
            capabilities: Capability::fromValues($this->capabilities),
            gpu: $this->gpuProfile(),
            onlyWithData: $this->onlyWithData,
        );
    }

    /**
     * Hay algún filtro que reduzca los resultados, para ofrecer "Limpiar".
     */
    protected function hasActiveFilters(): bool
    {
        $filter = $this->technologyFilter();

        return $this->search !== '' || $filter->technologies !== [] || $filter->capabilities !== [] || $filter->onlyWithData;
    }

    protected function gpuProfile(): ?GpuProfile
    {
        $profile = GpuProfile::tryFrom((string) $this->gpuModel);

        return $profile?->vendor()->value === $this->gpu ? $profile : null;
    }

    protected function highlightedVendor(): ?Vendor
    {
        return $this->gpu === 'all' ? null : Vendor::tryFrom($this->gpu);
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function toggled(array $values, string $value): array
    {
        return in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];
    }
}
