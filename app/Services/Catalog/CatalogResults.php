<?php

namespace App\Services\Catalog;

use App\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Una página de resultados del catálogo.
 */
readonly class CatalogResults
{
    /**
     * @param  LengthAwarePaginator<int, Game>  $games
     * @param  bool  $approximate  True si nada coincidía y se muestran títulos parecidos (p. ej. por un error de tipeo).
     */
    public function __construct(
        public LengthAwarePaginator $games,
        public bool $approximate = false,
    ) {}
}
