<?php

namespace App\Services;

use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Technology;

/**
 * Filtros de la biblioteca y del catálogo. Todos se combinan (y).
 */
readonly class TechnologyFilter
{
    /**
     * @param  list<Technology>  $technologies  Soporte "Yes" en cada una ("Más filtros").
     * @param  list<Capability>  $capabilities  Al menos una tecnología de cada capacidad; con GPU, que se pueda usar en ella.
     * @param  bool  $onlyWithData  Descarta los juegos que la wiki no documenta.
     */
    public function __construct(
        public array $technologies = [],
        public array $capabilities = [],
        public ?GpuProfile $gpu = null,
        public bool $onlyWithData = false,
    ) {}
}
