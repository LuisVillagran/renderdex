<?php

return [

    'model_label' => 'Generación',
    'model_unspecified' => 'Sin especificar',
    'model_help' => 'Elige la generación para ver qué puedes activar de verdad.',

    'profile' => [
        'rtx50' => 'RTX 50',
        'rtx40' => 'RTX 40',
        'rtx20-30' => 'RTX 20 / 30',
        'gtx' => 'GTX 10 / 16',
        'rx9000' => 'RX 9000',
        'rx7000' => 'RX 7000',
        'rx6000' => 'RX 6000',
        'rx5000' => 'RX 5000',
        'arc' => 'Arc',
    ],

    // Por qué tu GPU no puede usar una tecnología que el juego sí soporta.
    'requires' => [
        'dlss' => 'Requiere una NVIDIA RTX',
        'dlss_fg' => 'Requiere RTX 40 o superior',
        'dlss_mfg' => 'Requiere RTX 50',
        'fsr_fg' => 'Requiere RTX 20 o RX 5000 en adelante',
        'ray_tracing' => 'Requiere una GPU con ray tracing',
    ],

    'depends' => [
        'xess_fg' => 'Fuera de Intel Arc, solo si el juego usa XeSS 2.1 o posterior',
    ],

];
