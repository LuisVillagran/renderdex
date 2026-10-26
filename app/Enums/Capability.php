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
