<?php

return [

    'model_label' => 'Generation',
    'model_unspecified' => 'Not specified',
    'model_help' => 'Pick the generation to see what you can really turn on.',

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
        'dlss' => 'Needs an NVIDIA RTX',
        'dlss_fg' => 'Needs an RTX 40 or newer',
        'dlss_mfg' => 'Needs an RTX 50',
        'fsr_fg' => 'Needs an RTX 20 or RX 5000 or newer',
        'ray_tracing' => 'Needs a GPU with ray tracing',
    ],

    'depends' => [
        'xess_fg' => 'Outside Intel Arc, only if the game uses XeSS 2.1 or newer',
    ],

];
