<?php

/**
 * Navbar Configuration
 *
 * Define your navbar links here with their permissions.
 *
 * Structure:
 * [
 *     'label' => 'Link Text',
 *     'url' => '/path',
 *     'auth' => true|false,      // Requires authentication (optional, default: false)
 *     'guest' => true|false,     // Show only to guests (optional, default: false)
 *     'admin' => true|false,     // Requires admin role (optional, default: false)
 *     'icon' => 'bi-icon-name',  // Bootstrap icon (optional)
 *     'active' => 'page-name',   // Page identifier for active state (optional)
 * ]
 */

return [
    'main' => [
        [
            'label' => 'Home',
            'url' => '/',
            'icon' => 'bi-house-door',
            'active' => 'home'
        ],
        [
            'label' => 'About',
            'url' => '/about',
            'icon' => 'bi-info-circle',
            'active' => 'about'
        ],
        [
            'label' => 'Contact',
            'url' => '/contact',
            'icon' => 'bi-envelope',
            'active' => 'contact'
        ],
    ],

    'user' => [
        [
            'label' => 'Profile',
            'url' => '/profile',
            'icon' => 'bi-person',
            'active' => 'profile'
        ],
        [
            'label' => 'Administration',
            'url' => '/admin',
            'admin' => true,
            'icon' => 'bi-gear',
            'active' => 'admin'
        ],
    ],

    'guest' => [
        [
            'label' => 'Login',
            'url' => '/login',
            'guest' => true,
            'icon' => 'bi-box-arrow-in-right'
        ],
        [
            'label' => 'Register',
            'url' => '/register',
            'guest' => true,
            'show_if' => 'registration_enabled', // Show only if registration is enabled
            'icon' => 'bi-person-plus'
        ],
    ],
];
