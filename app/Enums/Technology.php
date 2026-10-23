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

    /**
     * Convierte valores sueltos (p. ej. de la URL) en tecnologías, descartando los desconocidos.
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
     * Las tecnologías agrupadas por marca, en el orden en que se muestran.
     *
     * @return array<string, list<self>> Indexado por el valor de {@see Vendor}.
     */
    public static function byVendor(): array
    {
        $groups = [];

        foreach (self::cases() as $technology) {
            $groups[$technology->vendor()->value][] = $technology;
        }

        return $groups;
    }
}
