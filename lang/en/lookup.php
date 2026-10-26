<?php

return [

    'label' => 'Your Steam profile URL or SteamID',
    'placeholder' => 'steamcommunity.com/id/your-name',
    'help' => 'Accepts steamcommunity.com/profiles/…, steamcommunity.com/id/…, your SteamID64 (17 digits) or just your custom URL.',
    'submit' => 'View library',
    'loading' => 'Searching…',

    'errors' => [
        'required' => 'Paste your profile URL or your SteamID.',
        'invalid' => 'That does not look like a Steam profile. Check the accepted formats.',
        'not_found' => 'We could not find any Steam profile with that custom URL.',
        'unavailable' => 'Steam is not responding right now. Please try again in a few minutes.',
        'too_many' => 'You searched many times in a row. Wait :seconds seconds and try again.',
    ],

];
