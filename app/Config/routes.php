<?php

return [
    // Configuration générale du routage
    'cache_enabled' => false,
    'cache_file' => __DIR__ . '/../../storage/cache/routes.php',
    
    // Middleware globaux
    'global_middleware' => [
        'csrf' => \App\Middleware\CsrfMiddleware::class,
    ],
    
    // Groupes de middleware
    'middleware_groups' => [
        'auth' => [
            \App\Middleware\AuthMiddleware::class,
        ],
        'admin' => [
            \App\Middleware\AuthMiddleware::class,
            \App\Middleware\AdminMiddleware::class,
        ],
    ],
    
    // Préfixes pour les groupes de routes
    'route_groups' => [
        'api' => [
            'prefix' => '/api',
            'middleware' => ['auth'],
            'namespace' => 'App\\Controllers\\Api',
        ],
        'admin' => [
            'prefix' => '',
            'middleware' => ['auth', 'admin'],
            'namespace' => 'App\\Controllers',
        ],
        'webhooks' => [
            'prefix' => '/webhooks',
            'middleware' => [],
            'namespace' => 'App\\Controllers\\Webhooks',
        ],
    ],
    
    // Routes nommées pour faciliter la génération d'URLs
    'named_routes' => [
        'home' => '/',
        'login' => '/login',
        'logout' => '/logout',
        'dashboard' => '/dashboard',
        'clients.index' => '/clients',
        'clients.create' => '/clients/create',
        'clients.show' => '/clients/{id}',
        'clients.edit' => '/clients/{id}/edit',
        'users.index' => '/users',
        'categories.index' => '/categories',
        'materials.index' => '/materials',
        'subscriptions.index' => '/subscriptions',
        'invoices.index' => '/invoices',
    ],
    
    // Configuration des paramètres de route
    'route_parameters' => [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9\-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ],
    
    // Pages par défaut pour les erreurs
    'error_pages' => [
        403 => 'errors/403',
        404 => 'errors/404',
        405 => 'errors/405',
        500 => 'errors/500',
        503 => 'errors/503',
    ],
    
    // Configuration CORS pour l'API
    'cors' => [
        'enabled' => true,
        'allow_origins' => ['*'], // À restreindre en production
        'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allow_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'allow_credentials' => true,
        'max_age' => 86400,
    ],
    
    // Configuration de la limitation de taux (rate limiting)
    'rate_limiting' => [
        'enabled' => true,
        'default_limits' => [
            'api' => [
                'requests' => 100,
                'window' => 3600, // secondes
            ],
            'auth' => [
                'requests' => 10,
                'window' => 900, // 15 minutes
            ],
            'webhooks' => [
                'requests' => 1000,
                'window' => 3600,
            ],
        ],
    ],
    
    // Configuration SSL/HTTPS
    'ssl' => [
        'force_https' => false, // true en production
        'redirect_http' => false,
        'strict_transport_security' => false,
    ],
    
    // Configuration du cache des routes
    'cache' => [
        'enabled' => false, // true en production
        'lifetime' => 3600,
        'key_prefix' => 'routes_',
    ],
    
    // Configuration de la journalisation des routes
    'logging' => [
        'enabled' => true,
        'log_file' => 'routes.log',
        'log_level' => 'info',
        'log_slow_requests' => true,
        'slow_request_threshold' => 2000, // millisecondes
    ],
];
?>