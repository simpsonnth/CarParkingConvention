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

];
