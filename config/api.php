<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the LPG Tamale API
    |
    */

    'prefix' => 'api',
    'version' => 'v1',
    'pagination_per_page' => 15,
    'max_results_per_page' => 100,

    /*
    |--------------------------------------------------------------------------
    | Station Service Configuration
    |--------------------------------------------------------------------------
    */

    'stations' => [
        'default_search_radius' => 5, // km
        'max_search_radius' => 100, // km
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Formatting
    |--------------------------------------------------------------------------
    */

    'responses' => [
        'include_timestamps' => true,
        'snake_case_output' => false,
    ],
];
