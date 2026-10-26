<?php

return [

    'title' => 'Updates',
    'eyebrow' => 'Change log',
    'heading' => 'New on the wiki',
    'intro' => 'Every day we compare PCGamingWiki with what we already had. This is what the wiki newly documented: it does not always match the date of the game patch.',
    'description' => 'Games that got DLSS, FSR, XeSS, frame generation or Ray Tracing, according to what PCGamingWiki documented each day.',
    'filter_label' => 'Show',
    'all' => 'All',
    'feed' => 'Atom feed',
    'empty' => 'No changes recorded yet. They show up from the second daily sync.',
    'detected_on' => 'Documented on :date',

    'type' => [
        'added' => ':technology',
        'removed' => ':technology',
        'upgraded' => ':technology :from → :to',
    ],

    // Para lectores de pantalla y el feed: el signo visual (+ − ↑) no alcanza.
    'type_label' => [
        'added' => 'Added',
        'removed' => 'Removed',
        'upgraded' => 'Updated',
    ],

    'feed_title' => 'Renderdex · Updates',
    'feed_entry' => ':game: :changes',
    'feed_rights' => 'Data from PCGamingWiki, CC BY-NC-SA 3.0',

    'badge' => 'New',
    'badge_help' => 'The wiki documented changes for this game in the last 7 days.',

];
