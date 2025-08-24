<?php

return [
    'name' => env('APP_NAME', 'DCCPHub'),
    'short_name' => env('APP_NAME', 'DCCPHub'),
    'start_url' => '/',
    'background_color' => '#ffffff',
    'theme_color' => '#000000',
    'display' => 'standalone',
    'orientation' => 'portrait',
    'status_bar' => 'black',
    'scope' => '/',
    'lang' => 'en',
    'dir' => 'ltr',
    'categories' => ['education', 'productivity'],
    'manifest' => [
        'name' => env('APP_NAME', 'DCCPHub'),
        'short_name' => env('APP_NAME', 'DCCPHub'),
        'start_url' => '/',
        'background_color' => '#ffffff',
        'theme_color' => '#000000',
        'display' => 'standalone',
    ],
    'icons' => [
        '48x48' => [
            'path' => '/images/android/android-launchericon-48-48.png',
            'purpose' => 'any'
        ],
        '72x72' => [
            'path' => '/images/android/android-launchericon-72-72.png',
            'purpose' => 'any'
        ],
        '96x96' => [
            'path' => '/images/android/android-launchericon-96-96.png',
            'purpose' => 'any'
        ],
        '144x144' => [
            'path' => '/images/android/android-launchericon-144-144.png',
            'purpose' => 'any'
        ],
        '192x192' => [
            'path' => '/images/android/android-launchericon-192-192.png',
            'purpose' => 'any maskable'
        ],
        '512x512' => [
            'path' => '/images/android/android-launchericon-512-512.png',
            'purpose' => 'any maskable'
        ],
    ],
    'splash' => [
        '640x1136' => '/images/splash/splash-640x1136.png',
        '750x1334' => '/images/splash/splash-750x1334.png',
        '828x1792' => '/images/splash/splash-828x1792.png',
        '1125x2436' => '/images/splash/splash-1125x2436.png',
        '1242x2208' => '/images/splash/splash-1242x2208.png',
        '1242x2688' => '/images/splash/splash-1242x2688.png',
        '1536x2048' => '/images/splash/splash-1536x2048.png',
        '1668x2224' => '/images/splash/splash-1668x2224.png',
        '1668x2388' => '/images/splash/splash-1668x2388.png',
        '2048x2732' => '/images/splash/splash-2048x2732.png',
    ],
    'shortcuts' => [
        [
            'name' => 'Dashboard',
            'description' => 'Go to dashboard',
            'url' => '/dashboard',
            'icons' => [
                "src" => "/android-chrome-192x192.png",
                "purpose" => "any"
            ]
        ],
    ],
    'custom' => []
];
