<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'steam' => [
        'api_url' => 'https://api.steampowered.com',
        'key' => env('STEAM_API_KEY'),

        // "Sign in through Steam" (OpenID 2.0) con socialiteproviders/steam.
        'client_id' => null,
        // Vacío a propósito: el proveedor pediría el perfil por http:// con la API key en la URL.
        // El nombre y el avatar se obtienen con App\Services\Steam\SteamClient, por HTTPS.
        'client_secret' => null,
        'redirect' => env('STEAM_REDIRECT_URI'),
        // Rechaza respuestas de Steam cuyo return_to apunte a otro dominio.
        'allowed_hosts' => [parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)],
        // Pausa entre llamadas a la tienda en covers:sync.
        'store_pause_ms' => 1000,
    ],

    // Portadas de respaldo para lo que Steam no cubre (covers:sync). La key se genera en
    // https://www.steamgriddb.com/profile/preferences; sin ella, ese paso se omite.
    'steamgriddb' => [
        'api_url' => 'https://www.steamgriddb.com/api/v2',
        'key' => env('STEAMGRIDDB_API_KEY'),
        // Juegos por ejecución: el resto sigue al día siguiente.
        'per_run' => 2000,
        'pause_ms' => 1000,
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
