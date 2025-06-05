<?php

return [
    // Configuration de l'application
    'name' => 'Cover AR Admin',
    'env' => 'local', // local, production, staging
    'debug' => true,
    'url' => 'https://heaven.cover-ar.com',
    'url_client' => 'https://account.cover-ar.com',
    'timezone' => 'Europe/Paris',
    
    // Sécurité
    'key' => 'your-32-character-secret-key-here', // Générer une clé unique
    
    // Sessions
    'session' => [
        'lifetime' => 120, // minutes
        'encrypt' => false,
        'driver' => 'file', // file, database
        'path' => '/tmp',
        'cookie_name' => 'cover_ar_session',
        'cookie_secure' => false, // true en HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ],
    
    // Logs
    'log' => [
        'channel' => 'stack',
        'level' => 'debug', // emergency, alert, critical, error, warning, notice, info, debug
        'path' => __DIR__ . '/../../storage/logs/',
        'max_files' => 30
    ],
    
    // Pagination
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100
    ],
    
    // Upload de fichiers
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'path' => __DIR__ . '/../../public/uploads/'
    ],
    
    // Localization
    'locale' => 'fr',
    'fallback_locale' => 'en',
    
    // Cache
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../../storage/cache/',
        'lifetime' => 3600 // secondes
    ]
];
?>