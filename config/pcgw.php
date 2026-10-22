<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PCGamingWiki API
    |--------------------------------------------------------------------------
    |
    | Desde agosto de 2026, cargoquery exige iniciar sesión con un bot password
    | creado en Special:BotPasswords con el permiso de Cargo. La wiki pide un
    | User-Agent con datos de contacto y permite 60 requests por minuto: si se
    | excede, responde 429 y bloquea la IP durante 60 segundos.
    |
    */

    'api_url' => env('PCGW_API_URL', 'https://www.pcgamingwiki.com/w/api.php'),

    'user_agent' => 'Renderdex/1.0 (https://github.com/LuisVillagran/renderdex; LuisVillagran) Laravel/13',

    'bot_username' => env('PCGW_BOT_USERNAME'),

    'bot_password' => env('PCGW_BOT_PASSWORD'),

    'page_size' => 500,

    // Pausa antes de cada request: ~55 por minuto, bajo el límite de 60.
    'pause_ms' => 1100,

    'connect_timeout' => 10,

    'timeout' => 60,

    'retries' => 3,

    // Un 429 bloquea la IP 60 s, así que hay que esperar un poco más antes de reintentar.
    'rate_limited_delay_ms' => 61_000,

    'tables' => [
        'game' => 'Game',
        'video' => 'Video',
    ],

    /*
    |--------------------------------------------------------------------------
    | Campos de la tabla Video
    |--------------------------------------------------------------------------
    |
    | Nombres posibles de cada campo, en orden de preferencia. El cliente usa el
    | primero que aparezca en action=cargofields; si no puede consultarlo, usa
    | el primero de cada lista.
    |
    */

    'video_fields' => [
        'upscaling' => ['Upscaling'],
        'frame_gen' => ['Frame_gen', 'Frame_generation'],
        'ray_tracing' => ['Ray_tracing'],
    ],

];
