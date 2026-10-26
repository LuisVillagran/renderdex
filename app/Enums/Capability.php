<?php

namespace App\Enums;

/**
 * Lo que una persona busca, sin importar la marca: "quiero reescalado", "quiero generación de frames".
 */
enum Capability: string
{
    case Upscaling = 'upscaling';
    case FrameGeneration = 'frame_gen';
    case RayTracing = 'ray_tracing';

    /**
     * Convierte valores sueltos (p. ej. de la URL) en capacidades, descartando los desconocidos.
     *
     * @param  array<mixed>  $values
     * @return list<self>
     */
    public static function fromValues(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => is_string($value) ? self::tryFrom($value) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<Technology>
     */
    public function technologies(): array
    {
        return match ($this) {
            self::Upscaling => [Technology::Dlss, Technology::Fsr, Technology::Xess],
            self::FrameGeneration => [Technology::DlssFg, Technology::DlssMfg, Technology::FsrFg, Technology::XessFg],
            self::RayTracing => [Technology::RayTracing],
        };
    }
}
