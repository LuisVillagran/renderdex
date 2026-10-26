<?php

namespace App\Enums;

/**
 * Generaciones de GPU con capacidades distintas para las tecnologías de render.
 * RTX 20 y 30 van juntas porque soportan exactamente lo mismo; en AMD, cada
 * generación cambia algo (RT desde RX 6000, FSR 4 desde RX 7000).
 */
enum GpuProfile: string
{
    case Rtx50 = 'rtx50';
    case Rtx40 = 'rtx40';
    case Rtx20To30 = 'rtx20-30';
    case Gtx = 'gtx';
    case Rx9000 = 'rx9000';
    case Rx7000 = 'rx7000';
    case Rx6000 = 'rx6000';
    case Rx5000 = 'rx5000';
    case Arc = 'arc';

    public function vendor(): Vendor
    {
        return match ($this) {
            self::Rtx50, self::Rtx40, self::Rtx20To30, self::Gtx => Vendor::Nvidia,
            self::Rx9000, self::Rx7000, self::Rx6000, self::Rx5000 => Vendor::Amd,
            self::Arc => Vendor::Intel,
        };
    }

    public function label(): string
    {
        return __('gpu.profile.'.$this->value);
    }

    /**
     * @return list<self> En el orden en que se ofrecen, de la más nueva a la más vieja.
     */
    public static function forVendor(Vendor $vendor): array
    {
        return array_values(array_filter(self::cases(), fn (self $profile) => $profile->vendor() === $vendor));
    }
}
