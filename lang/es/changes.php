<?php

return [

    'title' => 'Novedades',
    'eyebrow' => 'Registro de cambios',
    'heading' => 'Lo nuevo en la wiki',
    'intro' => 'Cada día comparamos PCGamingWiki con lo que ya teníamos. Esto es lo que la wiki documentó de nuevo: no siempre coincide con la fecha del parche del juego.',
    'description' => 'Juegos que recibieron DLSS, FSR, XeSS, generación de frames o Ray Tracing, según lo que documentó PCGamingWiki cada día.',
    'filter_label' => 'Mostrar',
    'all' => 'Todo',
    'feed' => 'Feed Atom',
    'empty' => 'Todavía no hay cambios registrados. Aparecen a partir del segundo sync diario.',
    'detected_on' => 'Documentado el :date',

    'type' => [
        'added' => ':technology',
        'removed' => ':technology',
        'upgraded' => ':technology :from → :to',
    ],

    // Para lectores de pantalla y el feed: el signo visual (+ − ↑) no alcanza.
    'type_label' => [
        'added' => 'Agregado',
        'removed' => 'Quitado',
        'upgraded' => 'Actualizado',
    ],

    'feed_title' => 'Renderdex · Novedades',
    'feed_entry' => ':game: :changes',

    'badge' => 'Novedad',
    'badge_help' => 'La wiki documentó cambios en este juego en los últimos 7 días.',

];
