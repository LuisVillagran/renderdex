<?php

namespace App\Enums;

/**
 * Si una tecnología se puede usar en un juego con una GPU concreta: cruza el soporte
 * del juego ({@see Support}) con lo que permite el hardware.
 */
enum Availability: string
{
    /** El juego la soporta y tu GPU puede usarla. */
    case Usable = 'usable';

    /** El juego la soporta, pero en tu GPU depende de la versión que integró el juego. */
    case Depends = 'depends';

    /** El juego la soporta, pero tu GPU no puede usarla. */
    case NeedsOtherGpu = 'needs_other_gpu';

    /** El juego no la soporta. */
    case NotSupported = 'not_supported';

    /** La wiki no lo documenta. */
    case Unknown = 'unknown';
}
