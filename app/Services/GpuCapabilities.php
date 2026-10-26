<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\Capability;
use App\Enums\GpuProfile;
use App\Enums\Support;
use App\Enums\Technology;
use App\Enums\Vendor;
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
     * Las tecnologías de una capacidad que tu GPU puede usar (todas, sin GPU elegida).
     * "Depende" no cuenta: no se puede prometer.
     *
     * @return list<Technology>
     */
    public function usableTechnologies(Capability $capability, ?GpuProfile $gpu): array
    {
        return array_values(array_filter(
            $capability->technologies(),
            fn (Technology $technology) => $gpu === null || $this->hardware($gpu, $technology) === Availability::Usable,
        ));
    }

    /**
     * Lo que conviene mostrar en la columna "En tu GPU": la mejor tecnología usable de cada
     * capacidad (primero la de tu marca) y lo que el juego trae pero tu GPU no puede usar.
     *
     * @return array{usable: list<array{technology: Technology, version: ?string}>, depends: list<Technology>, blocked: list<Technology>}
     */
    public function summary(?Game $game, GpuProfile $gpu): array
    {
        $usable = [];

        foreach (Capability::cases() as $capability) {
            $best = collect($this->byPreference($capability, $gpu))
                ->first(fn (Technology $technology) => $this->availability($game, $gpu, $technology) === Availability::Usable);

            if ($best !== null) {
                $usable[] = ['technology' => $best, 'version' => $this->usableVersion($game, $gpu, $best)];
            }
        }

        $withAvailability = fn (Availability $availability) => array_values(array_filter(
            Technology::cases(),
            fn (Technology $technology) => $this->availability($game, $gpu, $technology) === $availability,
        ));

        return [
            'usable' => $usable,
            'depends' => $withAvailability(Availability::Depends),
            // Solo lo de tu marca: "DLSS requiere RTX" no le sirve a quien tiene una Radeon.
            'blocked' => array_values(array_filter(
                $withAvailability(Availability::NeedsOtherGpu),
                fn (Technology $technology) => in_array($technology->vendor(), [$gpu->vendor(), Vendor::All], true),
            )),
        ];
    }

    /**
     * Las tecnologías de la capacidad, primero las de tu marca. En generación de frames, MFG antes que FG.
     *
     * @return list<Technology>
     */
    private function byPreference(Capability $capability, GpuProfile $gpu): array
    {
        return collect($capability->technologies())
            ->sortBy(fn (Technology $technology) => [
                $technology->vendor() === $gpu->vendor() ? 0 : 1,
                $technology === Technology::DlssMfg ? 0 : 1,
            ])
            ->values()
            ->all();
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
