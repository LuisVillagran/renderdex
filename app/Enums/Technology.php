<?php

namespace App\Enums;

/**
 * Cada valor coincide con el nombre de su columna en la tabla `games`.
 */
enum Technology: string
{
    case Dlss = 'dlss';
    case DlssFg = 'dlss_fg';
    case DlssMfg = 'dlss_mfg';
    case Fsr = 'fsr';
    case FsrFg = 'fsr_fg';
    case Xess = 'xess';
    case XessFg = 'xess_fg';
    case RayTracing = 'ray_tracing';

    public function vendor(): Vendor
    {
        return match ($this) {
            self::Dlss, self::DlssFg, self::DlssMfg => Vendor::Nvidia,
            self::Fsr, self::FsrFg => Vendor::Amd,
            self::Xess, self::XessFg => Vendor::Intel,
            self::RayTracing => Vendor::All,
        };
    }

    public function isFrameGeneration(): bool
    {
        return in_array($this, [self::DlssFg, self::DlssMfg, self::FsrFg, self::XessFg], true);
    }
}
