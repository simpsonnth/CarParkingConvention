<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Toolbox talk cover images
    |--------------------------------------------------------------------------
    |
    | Paths are relative to the public/ directory. Parks without an explicit
    | override receive a stable image from the pool (by car park id).
    |
    */

    'cover_default' => 'images/toolbox-covers/default.png',

    'cover_jha' => 'images/toolbox-covers/jha.png',

    'cover_pool' => [
        'images/toolbox-covers/default.png',
        'images/toolbox-covers/night-sky.png',
        'images/toolbox-covers/summit-sunrise.png',
    ],

    /*
    | Optional exact name matches (case-insensitive). Useful when several parks
    | should share a theme (e.g. both Rosebine sites).
    */
    'cover_by_name' => [
        // 'West' => 'images/toolbox-covers/summit-sunrise.png',
        // 'North' => 'images/toolbox-covers/night-sky.png',
        // 'Rosebine 1' => 'images/toolbox-covers/default.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | JHA decks (admin tabs + export order)
    |--------------------------------------------------------------------------
    |
    | Keys must match config/jha-templates.php. park_match filters Present mode
    | when a car park is selected (null = always include, e.g. All car parks).
    |
    */
    'jha_decks' => [
        [
            'key' => 'all-car-parks',
            'label' => 'All car parks',
            'park_match' => null,
        ],
        [
            'key' => 'north-cars-arrivals',
            'label' => 'North — car arrivals',
            'park_match' => ['north'],
        ],
        [
            'key' => 'north-coach-arrivals',
            'label' => 'North — coach arrivals',
            'park_match' => ['north'],
        ],
        [
            'key' => 'north-departure',
            'label' => 'North — cars & coach departure',
            'park_match' => ['north'],
        ],
        [
            'key' => 'rosebine-arrivals',
            'label' => 'Rosebine 1&2 — car arrivals',
            'park_match' => ['rosebine'],
        ],
        [
            'key' => 'rosebine-departure',
            'label' => 'Rosebine 1&2 — car departures',
            'park_match' => ['rosebine'],
        ],
        [
            'key' => 'west-arrivals',
            'label' => 'West — arrivals',
            'park_match' => ['west'],
        ],
        [
            'key' => 'west-departure',
            'label' => 'West — departure',
            'park_match' => ['west'],
        ],
    ],

];
