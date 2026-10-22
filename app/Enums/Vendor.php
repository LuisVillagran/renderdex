<?php

namespace App\Enums;

enum Vendor: string
{
    case Nvidia = 'nvidia';
    case Amd = 'amd';
    case Intel = 'intel';

    /** Tecnologías que funcionan en GPUs de cualquier marca, como Ray Tracing. */
    case All = 'all';
}
