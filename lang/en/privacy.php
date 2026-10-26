<?php

return [

    'title' => 'Privacy',
    'heading' => 'Privacy policy',
    'updated' => 'Last updated: October 26, 2026.',
    'intro' => 'Renderdex is a personal project, with no ads or analytics. This page explains which data it requests, which data it keeps and for how long.',
    'contents' => 'Contents',

    'sections' => [
        [
            'id' => 'what-we-request',
            'title' => 'What we request',
            'items' => [
                'We only request a profile when you ask for it: when you paste your URL or SteamID, or when you sign in with Steam.',
                'We ask the Steam Web API for the SteamID64 behind a custom URL and for the list of games in that library, with the hours played.',
                'Only if you open the "Wishlist" tab, we also ask for the games on that list (just their AppIDs).',
                'If you sign in, we also ask for your display name and your avatar, to show them in the header.',
                'Steam only provides public libraries: signing in does not give us access to private data.',
            ],
        ],
        [
            'id' => 'what-we-keep',
            'title' => 'What we keep and for how long',
            'items' => [
                'The library and the wishlist are not stored in our database. They stay in the server cache for 1 hour, identified by a hash of your SteamID64, so we do not ask Steam again. After that they are deleted.',
                'There are no accounts and no users table. We do not keep SteamIDs in logs or analytics.',
                'To respect the Steam API limit, we count how many requests come from each IP. That counter uses a hash of the IP and lasts one minute.',
            ],
        ],
        [
            'id' => 'session',
            'title' => 'Signing in with Steam',
            'items' => [
                'We use "Sign in through Steam" (OpenID). You type your password on Steam: Renderdex never sees or stores it.',
                'Steam sends us your SteamID64. Together with your display name and your avatar, it only lives in the session while it is open.',
                'The "Sign out" button destroys the session and that data.',
            ],
        ],
        [
            'id' => 'cookies',
            'title' => 'Cookies and local storage',
            'items' => [
                'Like any site built with Laravel, every visit has a session cookie and a CSRF protection cookie. They are needed for the site to work.',
                'The session is stored on the server along with the IP and the browser it was opened from, and it is deleted after 2 hours of inactivity. If you pick a language, it is remembered in the session.',
                'Your "My GPU" choice (vendor and generation), the color theme and whether you prefer to see all technologies are stored in your browser local storage (localStorage). They are only used to show you the page that way and are not stored on the server.',
                'We do not use third-party cookies, analytics or ads.',
            ],
        ],
        [
            'id' => 'third-parties',
            'title' => 'Third-party services',
            'items' => [
                'Game covers and the sign-in button are loaded from Steam (Valve) servers, which receive your IP as in any visit to their site.',
                'Compatibility data comes from PCGamingWiki and is synced once a day from our server. Your visit never triggers requests to the wiki.',
                'Fonts are served from our own server.',
            ],
        ],
        [
            'id' => 'contact',
            'title' => 'Contact',
            'body' => 'If you have questions or want to report a problem, open an issue in the :repository.',
            'repository' => 'project repository',
        ],
    ],

];
