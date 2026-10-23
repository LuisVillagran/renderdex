<?php

return [

    'label' => 'URL de tu perfil de Steam o tu SteamID',
    'placeholder' => 'steamcommunity.com/id/tu-nombre',
    'help' => 'Acepta steamcommunity.com/profiles/…, steamcommunity.com/id/…, tu SteamID64 (17 dígitos) o solo tu URL personalizada.',
    'submit' => 'Ver biblioteca',
    'loading' => 'Buscando…',

    'errors' => [
        'required' => 'Pega la URL de tu perfil o tu SteamID.',
        'invalid' => 'Eso no parece un perfil de Steam. Revisa los formatos aceptados.',
        'not_found' => 'No encontramos ningún perfil de Steam con esa URL personalizada.',
        'unavailable' => 'Steam no responde en este momento. Inténtalo de nuevo en unos minutos.',
        'too_many' => 'Hiciste muchas búsquedas seguidas. Espera :seconds segundos e inténtalo de nuevo.',
    ],

];
