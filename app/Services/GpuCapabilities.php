<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Models\Game;

/**
 * Qué tecnologías puede usar cada generación de GPU. Clase pura, sin HTTP ni base de datos.
 *
 * Fuentes (octubre de 2026):
 * - DLSS FG requiere RTX 40 y DLSS MFG, RTX 50 (NVIDIA).
 * - FSR 3/3.1 FG: RX 5000 y RTX 20 en adelante; las GTX solo tienen el reescalado (AMD GPUOpen).
 *   En Arc funciona aunque AMD no lo liste: es un efecto de cómputo sin requisitos de marca.
 * - FSR 4 necesita RX 9000 (FP8); AMD lo llevó a RX 7000 con FSR 4.1 (INT8) desde julio de 2026.
 *   Las demás GPUs usan el respaldo FSR 3.1 que traen los juegos con FSR 4.
 * - XeSS corre en cualquier GPU con Shader Model 6.4 (GTX 10 y RX 5000 en adelante) por DP4a.
 *   XeSS FG llegó a NVIDIA y AMD con el SDK 2.1, pero solo si el juego integró esa versión.
 * - Ray tracing por hardware: RTX, RX 6000 en adelante y Arc.
 */
class GpuCapabilities
{
    /** Primera versión de FSR que necesita hardware específico. */
    private const FSR_ML_VERSION = '4';

    public function __construct(private FeatureClassifier $classifier) {}

    public function availability(?Game $game, GpuProfile $gpu, Technology $technology): Availability
    {
        return match ($game?->{$technology->value} ?? Support::Unknown) {
            Support::Unknown => Availability::Unknown,
            Support::No => Availability::NotSupported,
            Support::Yes => $this->hardware($gpu, $technology),
        };
    }

    /**
     * Lo que permite el hardware, sin importar el juego.
     */
    public function hardware(GpuProfile $gpu, Technology $technology): Availability
    {
        $isRtx = in_array($gpu, [GpuProfile::Rtx50, GpuProfile::Rtx40, GpuProfile::Rtx20To30], true);

        $usable = match ($technology) {
            Technology::Dlss => $isRtx,
            Technology::DlssFg => in_array($gpu, [GpuProfile::Rtx50, GpuProfile::Rtx40], true),
            Technology::DlssMfg => $gpu === GpuProfile::Rtx50,
            Technology::Fsr, Technology::Xess => true,
            Technology::FsrFg => $gpu !== GpuProfile::Gtx,
            Technology::XessFg => $gpu === GpuProfile::Arc ? true : null,
            Technology::RayTracing => ! in_array($gpu, [GpuProfile::Gtx, GpuProfile::Rx5000], true),
        };

        return match ($usable) {
            true => Availability::Usable,
            null => Availability::Depends,
            false => Availability::NeedsOtherGpu,
        };
    }

    /**
     * La versión que vas a usar en tu GPU, p. ej. "3.1" para FSR en una RX 6000 si el juego
     * lista "FSR 3.1,FSR 4". Null si no hay versión o si solo hay versiones que tu GPU no corre.
     */
    public function usableVersion(?Game $game, GpuProfile $gpu, Technology $technology): ?string
    {
        if ($game === null || $this->availability($game, $gpu, $technology) !== Availability::Usable) {
            return null;
        }

        if ($technology !== Technology::Fsr || $this->runsMachineLearningFsr($gpu)) {
            return $this->classifier->latestVersion($game->upscaling_tech, $technology);
        }

        return $this->classifier->latestVersion($game->upscaling_tech, $technology, below: self::FSR_ML_VERSION);
    }

    private function runsMachineLearningFsr(GpuProfile $gpu): bool
    {
        return in_array($gpu, [GpuProfile::Rx9000, GpuProfile::Rx7000], true);
    }
}
