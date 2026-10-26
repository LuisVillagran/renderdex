<?php

return [

    'eyebrow' => ':count games documented on PCGamingWiki',
    'title' => 'Which games in your library support :technologies?',
    'title_technologies' => 'DLSS, FSR, XeSS and Ray Tracing',
    'intro' => 'Renderdex matches your Steam library with PCGamingWiki data and shows which render technologies each game supports, without you having to install them.',

    'steam_login' => [
        'alt' => 'Sign in through Steam',
        'note' => 'Signing in only identifies you without copying your URL. Renderdex never sees your password, and it cannot read a private library: if your game details are private, you will still have to make them public.',
        'open_library' => 'View my library',
    ],

    'access' => [
        'label' => 'Check your whole library',
        'paste_profile' => 'Rather not sign in? Paste your profile',
    ],

    'catalog' => [
        'title' => 'About to buy a game?',
        'body' => 'Look it up in the full catalog, no account or Steam profile needed.',
    ],

    'preview' => [
        'caption' => 'Real wiki data, just as you will see it in your library.',
    ],

    'how' => [
        'title' => 'How it works',
        'steps' => [
            [
                'title' => 'Connect your library',
                'body' => 'Sign in with Steam or paste your profile URL. Nothing to install.',
            ],
            [
                'title' => 'We match it with the wiki',
                'body' => 'Every game is compared with PCGamingWiki data, which is synced daily.',
            ],
            [
                'title' => 'Filter by your GPU',
                'body' => 'Pick your GPU vendor and generation, and see what you can really turn on in each game.',
            ],
        ],
    ],

    'faq_title' => 'Frequently asked questions',

    'privacy_help' => [
        'title' => 'How do I make my library public?',
        'steps' => 'On Steam, open your profile → Edit Profile → Privacy Settings, and set "My profile" and "Game details" to Public.',
        'family_sharing_title' => 'What about Family Sharing?',
        'family_sharing' => 'Games you play through Family Sharing do not show up: Steam only reports the games in your own library.',
    ],

];
