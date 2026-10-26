<?php

namespace App\Services;

use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;

/**
 * Resumen corto de lo que soporta un juego, p. ej. "DLSS 4.5 · DLSS MFG · XeSS 2.1 · Ray Tracing",
 * para descripciones, metadatos y la vista previa del buscador.
 */
class SupportSummary
{
    public function __construct(private FeatureClassifier $classifier) {}

    /**
     * @return list<string> Solo las tecnologías en "Yes", en el orden de {@see Technology}.
     */
    public function labels(?Game $game): array
    {
        if ($game === null) {
            return [];
        }

        return collect(Technology::cases())
            ->filter(fn (Technology $technology) => $game->{$technology->value} === Support::Yes)
            // MFG incluye FG: nombrar las dos sería redundante.
            ->reject(fn (Technology $technology) => $technology === Technology::DlssFg && $game->dlss_mfg === Support::Yes)
            ->map(fn (Technology $technology) => trim(__('technologies.label.'.$technology->value).' '.$this->classifier->latestVersion($game->upscaling_tech, $technology)))
            ->values()
            ->all();
    }
}
