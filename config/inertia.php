<?php

return [

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | The package default here is `resource_path('js/pages')` (lowercase),
    | which only matched this project's real `resources/js/Pages` directory
    | by accident on case-insensitive filesystems (Windows/macOS). On a
    | case-sensitive filesystem (Linux CI), the default silently fails to
    | find any page component, so `assertInertia()->component(...)` reports
    | every real page as "does not exist". Pinning the actual casing here
    | fixes that for every environment, not just this one dev machine.
    |
    */

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
        ],

    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],

];
