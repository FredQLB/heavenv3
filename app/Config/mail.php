<?php

return [
    // Driver de mail par défaut
    'default' => 'smtp',
    
    // Configuration des drivers
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => 'cover-ar.com',
            'port' => 465,
            'encryption' => 'tls', // tls, ssl, null
            'username' => 'no-reply@cover-ar.com', // Votre email Gmail
            'password' => 'TestEmail2025', // À remplacer par votre mot de passe d'application
            'timeout' => 30,
            'auth_mode' => null,
        ],
        
        'sendmail' => [
            'transport' => 'sendmail',
            'path' => '/usr/sbin/sendmail -bs',
        ],
        
        'log' => [
            'transport' => 'log',
            'channel' => 'mail',
        ],
        
        // Configuration pour la production
        'production' => [
            'transport' => 'smtp',
            'host' => 'cover-ar.com',
            'port' => 465,
            'encryption' => 'tls',
            'username' => 'no-reply@cover-ar.com',
            'password' => 'TestEmail2025',
            'timeout' => 30,
        ]
    ],
    
    // Configuration globale
    'from' => [
        'address' => 'no-reply@cover-ar.com',
        'name' => 'Cover AR',
    ],
    
    // Templates d'email
    'templates' => [
        'welcome' => [
            'subject' => 'Bienvenue sur Cover AR',
            'template' => 'emails/welcome',
        ],
        'password_reset' => [
            'subject' => 'Réinitialisation de votre mot de passe',
            'template' => 'emails/password_reset',
        ],
        'new_user' => [
            'subject' => 'Vos identifiants Cover AR',
            'template' => 'emails/new_user',
        ],
        'subscription_expiring' => [
            'subject' => 'Votre abonnement expire bientôt',
            'template' => 'emails/subscription_expiring',
        ],
        'invoice' => [
            'subject' => 'Nouvelle facture Cover AR',
            'template' => 'emails/invoice',
        ]
    ],
    
    // Configuration du markdown pour les emails
    'markdown' => [
        'theme' => 'default',
        'paths' => [
            __DIR__ . '/../Views/emails',
        ],
    ],
    
    // Configuration des pièces jointes
    'attachments' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['pdf', 'doc', 'docx', 'jpg', 'png'],
    ],
    
    // Configuration des logs
    'log_channel' => 'mail',
];