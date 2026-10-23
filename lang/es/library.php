<?php

return [

    'title' => 'Biblioteca',
    'heading' => 'Biblioteca de Steam',
    'share' => 'Copiar enlace',
    'copied' => 'Enlace copiado',

    'demo' => 'Modo demostración: esta es una biblioteca de ejemplo con juegos reales, porque no hay una API key de Steam configurada. Los datos de compatibilidad sí son reales.',

    'stats' => [
        'label' => 'Resumen de la biblioteca',
        'total' => 'Juegos',
        'with_wiki_page' => 'Con página en la wiki',
    ],

    'gpu' => [
        'label' => 'Mi GPU',
        'help' => 'Resalta las columnas de tu marca y preselecciona su filtro.',
    ],

    'filters' => [
        'label' => 'Mostrar solo juegos con',
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
        'versions' => 'Versiones',
        'sort_by' => 'Ordenar por :column',
    ],

    'wiki_link' => 'Ver :name en PCGamingWiki',
    'matched_by_title' => 'Encontrado por nombre',
    'matched_by_title_help' => 'La wiki no tiene el Steam AppID de este juego; lo encontramos por su nombre exacto.',
    'no_wiki_page' => 'Sin página en la wiki',

    'no_results' => 'Ningún juego coincide con la búsqueda o los filtros.',
    'empty' => 'Esta biblioteca no tiene juegos.',
    'legend' => 'Sí, No o Sin datos (la wiki no lo documenta).',

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
