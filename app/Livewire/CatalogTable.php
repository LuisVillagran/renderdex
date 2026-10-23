<?php

namespace App\Livewire;

use App\Enums\Technology;
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
        $technologies = $this->validFilters();

        return view('livewire.catalog-table', [
            'games' => $catalog->search($this->search, $technologies, $this->getPage()),
            'activeFilters' => array_map(fn (Technology $technology) => $technology->value, $technologies),
            'highlightedVendor' => $this->highlightedVendor(),
        ]);
    }
}
