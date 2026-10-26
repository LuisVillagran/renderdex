<?php

return [

    'title' => 'Biblioteca',
    'eyebrow' => 'Biblioteca de Steam',
    'heading' => 'Tu biblioteca, por tecnología',
    'share' => 'Copiar enlace',
    'copied' => 'Enlace copiado',

    'demo' => 'Modo demostración: esta es una biblioteca de ejemplo con juegos reales, porque no hay una API key de Steam configurada. Los datos de compatibilidad sí son reales.',

    'stats' => [
        'label' => 'Resumen de la biblioteca',
        'total' => 'Juegos',
        'with_data' => 'Datos de render para :count',
        'with_data_help' => 'El resto no está documentado en la wiki todavía.',
        'games_with' => ':count de :total juegos',
        'on_gpu' => 'Usable en tu :gpu',
        'any_vendor' => 'De cualquier marca',
    ],

    'capability' => [
        'upscaling' => ['label' => 'Reescalado', 'hint' => 'DLSS, FSR o XeSS'],
        'frame_gen' => ['label' => 'Generación de frames', 'hint' => 'DLSS FG, FSR FG o XeSS FG'],
        'ray_tracing' => ['label' => 'Ray tracing', 'hint' => 'Cualquier marca'],
    ],

    'gpu' => [
        'label' => 'Mi GPU',
        'help' => 'Resalta las columnas de tu marca.',
        'column' => 'En tu :gpu',
        'show_all' => 'Ver todas las tecnologías',
        'show_less' => 'Ver solo tu GPU',
        'nothing_usable' => 'Nada usable en tu GPU',
    ],

    'filters' => [
        'label' => 'Tecnologías exactas',
        'capabilities' => 'Quiero jugar con',
        'capabilities_on_gpu' => 'Quiero jugar con, en mi :gpu',
        'only_with_data' => 'Solo juegos con datos',
        'more' => 'Más filtros',
        'more_help' => 'Exige soporte en cada tecnología marcada, sin importar tu GPU.',
        'clear' => 'Quitar búsqueda y filtros',
    ],

    'search' => [
        'label' => 'Buscar por nombre',
        'placeholder' => 'Buscar un juego…',
    ],

    'table' => [
        'caption' => 'Juegos de la biblioteca y las tecnologías de render que soportan',
        'cover' => 'Portada',
        'name' => 'Nombre',
        'playtime' => 'Horas',
        'hours' => ':hours h',
        'sort_by' => 'Ordenar por :column',
        'technologies' => 'Tecnologías',
        'sort_technologies' => 'Más tecnologías primero',
    ],

    'wiki_link' => 'Ver :name en PCGamingWiki',
    'matched_by_title' => 'Encontrado por nombre',
    'matched_by_title_help' => 'La wiki no tiene el Steam AppID de este juego; lo encontramos por su nombre exacto.',
    'no_wiki_page' => 'Sin página en la wiki',
    'no_data' => 'Sin datos en la wiki',
    'complete' => 'completar',
    'complete_label' => 'Completar :name en PCGamingWiki',

    'missing' => [
        'title' => 'Ayuda a completarlo',
        'body' => 'Tus juegos más jugados que la wiki todavía no documenta. PCGamingWiki la escribe la comunidad: si sabes qué soportan, puedes agregarlo.',
        'create' => 'buscar en la wiki',
    ],

    'no_results' => 'Ningún juego coincide con la búsqueda o los filtros.',
    'empty' => 'Esta biblioteca no tiene juegos.',
    'legend' => [
        'label' => 'Cómo leer la tabla',
        'unknown' => '"Sin datos" significa que la wiki no lo documenta.',
    ],

    'errors' => [
        'private' => [
            'title' => 'No podemos ver esta biblioteca',
            'body' => 'El perfil es privado, o es público pero tiene los "Detalles del juego" privados: Steam responde igual en ambos casos. Esto también aplica si iniciaste sesión con Steam.',
        ],
        'unavailable' => [
            'title' => 'Steam no responde',
            'body' => 'No pudimos obtener la biblioteca en este momento. Inténtalo de nuevo en unos minutos.',
            'retry' => 'Reintentar',
        ],
    ],

];
