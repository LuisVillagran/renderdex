<?php

return [

    'title' => 'Library',
    'eyebrow' => 'Steam library',
    'heading' => 'Your library, by technology',
    'share' => 'Copy link',
    'copied' => 'Link copied',

    'demo' => 'Demo mode: this is a sample library with real games, because no Steam API key is configured. The compatibility data is real.',

    'stats' => [
        'label' => 'Library summary',
        'total' => 'Games',
        'with_data' => 'Render data for :count',
        'with_data_help' => 'The rest is not documented on the wiki yet.',
        'games_with' => ':count of :total games',
        'on_gpu' => 'Usable on your :gpu',
        'any_vendor' => 'Any vendor',
    ],

    'capability' => [
        'upscaling' => ['label' => 'Upscaling', 'hint' => 'DLSS, FSR or XeSS'],
        'frame_gen' => ['label' => 'Frame generation', 'hint' => 'DLSS FG, FSR FG or XeSS FG'],
        'ray_tracing' => ['label' => 'Ray tracing', 'hint' => 'Any vendor'],
    ],

    'tabs' => [
        'label' => 'What to show',
        'library' => 'Library',
        'wishlist' => 'Wishlist',
    ],

    'wishlist' => [
        'heading' => 'Your wishlist, by technology',
        'unknown_game' => 'Steam game :appid',
        'note' => 'The wishlist is only requested when you open this tab. Steam sends it without names: we take them from the wiki, so games that are not on it show up as "Steam game".',
        'private' => [
            'title' => 'We do not see any game in the wishlist',
            'body' => 'It is empty or private: Steam answers the same way in both cases. The wishlist follows the "Game details" privacy setting of your profile.',
        ],
    ],

    'gpu' => [
        'label' => 'My GPU',
        'help' => 'Highlights the columns of your vendor.',
        'column' => 'On your :gpu',
        'show_all' => 'Show all technologies',
        'show_less' => 'Show only your GPU',
        'nothing_usable' => 'Nothing usable on your GPU',
    ],

    'filters' => [
        'label' => 'Exact technologies',
        'capabilities' => 'I want to play with',
        'capabilities_on_gpu' => 'I want to play with, on my :gpu',
        'only_with_data' => 'Only games with data',
        'more' => 'More filters',
        'more_help' => 'Requires support for every checked technology, regardless of your GPU.',
        'clear' => 'Clear search and filters',
    ],

    'search' => [
        'label' => 'Search by name',
        'placeholder' => 'Search for a game…',
    ],

    'table' => [
        'caption' => 'Games in the library and the render technologies they support',
        'cover' => 'Cover',
        'name' => 'Name',
        'playtime' => 'Hours',
        'hours' => ':hours h',
        'sort_by' => 'Sort by :column',
        'technologies' => 'Technologies',
        'sort_technologies' => 'Most technologies first',
    ],

    'wiki_link' => 'View :name on PCGamingWiki',
    'matched_by_title' => 'Found by name',
    'matched_by_title_help' => 'The wiki does not have the Steam AppID of this game; we found it by its exact name.',
    'no_wiki_page' => 'No wiki page',
    'no_data' => 'No data on the wiki',
    'complete' => 'complete',
    'complete_label' => 'Complete :name on PCGamingWiki',

    'missing' => [
        'title' => 'Help complete it',
        'body' => 'Your most played games that the wiki does not document yet. PCGamingWiki is written by the community: if you know what they support, you can add it.',
        'create' => 'search the wiki',
    ],

    'no_results' => 'No game matches the search or the filters.',
    'empty' => 'This library has no games.',
    'legend' => [
        'label' => 'How to read the table',
        'unknown' => '"No data" means the wiki does not document it.',
    ],

    'errors' => [
        'private' => [
            'title' => 'We cannot see this library',
            'body' => 'The profile is private, or it is public but its "Game details" are private: Steam answers the same way in both cases. This also applies if you signed in with Steam.',
        ],
        'unavailable' => [
            'title' => 'Steam is not responding',
            'body' => 'We could not get the library right now. Please try again in a few minutes.',
            'retry' => 'Retry',
        ],
    ],

];
