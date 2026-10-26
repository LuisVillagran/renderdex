<?php

return [

    'eyebrow' => ':count juegos documentados en PCGamingWiki',
    'title' => '¿Qué juegos de tu biblioteca soportan :technologies?',
    'title_technologies' => 'DLSS, FSR, XeSS y Ray Tracing',
    'intro' => 'Renderdex cruza tu biblioteca de Steam con los datos de PCGamingWiki y te muestra qué tecnologías de render soporta cada juego, sin que tengas que instalarlos.',

    'steam_login' => [
        'alt' => 'Iniciar sesión con Steam',
        'note' => 'Iniciar sesión solo sirve para identificarte sin copiar tu URL. Renderdex nunca ve tu contraseña, y no puede leer una biblioteca privada: si los detalles de tus juegos son privados, igual tendrás que hacerlos públicos.',
        'open_library' => 'Ver mi biblioteca',
    ],

    'access' => [
        'label' => 'Conectar biblioteca',
    ],

    'or' => 'o',

    'catalog' => [
        'title' => '¿Vas a comprar un juego?',
        'body' => 'Búscalo en el catálogo completo, sin cuenta ni perfil de Steam.',
    ],

    'preview' => [
        'label' => 'Vista previa',
        'command' => 'renderdex scan --preview',
        'rows' => ':count fila|:count filas',
        'caption' => 'Datos reales de la wiki, tal como los verás en tu biblioteca.',
    ],

    'how' => [
        'title' => 'Cómo funciona',
        'steps' => [
            [
                'title' => 'Conecta tu biblioteca',
                'body' => 'Inicia sesión con Steam o pega la URL de tu perfil. No hace falta instalar nada.',
            ],
            [
                'title' => 'La cruzamos con la wiki',
                'body' => 'Cada juego se compara con los datos de PCGamingWiki, que se sincronizan a diario.',
            ],
            [
                'title' => 'Filtra por tu GPU',
                'body' => 'Elige NVIDIA, AMD o Intel y ve de un vistazo qué juegos aprovechan tu tarjeta.',
            ],
        ],
    ],

    'faq_title' => 'Preguntas frecuentes',

    'privacy_help' => [
        'title' => '¿Cómo hago pública mi biblioteca?',
        'steps' => 'En Steam, abre tu perfil → Editar perfil → Configuración de privacidad, y deja "Mi perfil" y "Detalles del juego" en Público.',
        'family_sharing_title' => '¿Y el Préstamo familiar?',
        'family_sharing' => 'Los juegos que usas mediante Préstamo familiar (Family Sharing) no aparecen: Steam solo informa los juegos de tu propia biblioteca.',
    ],

];
