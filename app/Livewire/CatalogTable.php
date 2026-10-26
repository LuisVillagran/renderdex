<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithTechnologyFilters;
use App\Services\Catalog\CatalogSearch;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catálogo completo de la wiki para buscar cualquier juego, sin cuenta ni perfil de Steam.
 */
class CatalogTable extends Component
{
    use WithPagination;
    use WithTechnologyFilters;

    public function render(CatalogSearch $catalog): View
    {
        $filter = $this->technologyFilter();
        $results = $catalog->search($this->search, $filter, $this->getPage());

        return view('livewire.catalog-table', [
            'games' => $results->games,
            'approximate' => $results->approximate,
            'filter' => $filter,
            'hasActiveFilters' => $this->hasActiveFilters(),
            'gpuProfile' => $filter->gpu,
            'highlightedVendor' => $this->highlightedVendor(),
        ]);
    }
}
