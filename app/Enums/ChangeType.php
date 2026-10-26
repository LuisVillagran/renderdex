<?php

namespace App\Enums;

/**
 * Qué cambió en la wiki para una tecnología de un juego entre dos syncs.
 */
enum ChangeType: string
{
    /** Pasó a "Yes". */
    case Added = 'added';

    /** Dejó de estar en "Yes". */
    case Removed = 'removed';

    /** Sigue en "Yes", con una versión más nueva (solo reescaladores). */
    case Upgraded = 'upgraded';
}
