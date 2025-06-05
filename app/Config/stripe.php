<?php

return [
    // Environnement (sandbox ou live)
    'environment' => 'sandbox', // sandbox, live
    
    // Clés API pour sandbox/test
    'sandbox' => [
        'publishable_key' => 'pk_test_your_publishable_key_here',
        'secret_key' => 'sk_test_your_secret_key_here',
        'webhook_secret' => 'whsec_your_webhook_secret_here',
    ],
    
    // Clés API pour production
    'live' => [
        'publishable_key' => 'pk_live_your_live_publishable_key_here',
        'secret_key' => 'sk_live_your_live_secret_key_here',
        'webhook_secret' => 'whsec_your_live_webhook_secret_here',
    ],
    
    // Configuration générale
    'currency' => 'eur',
    'locale' => 'fr',
    
    // Configuration des webhooks
    'webhooks' => [
        'tolerance' => 300, // secondes
        'endpoint' => '/webhooks/stripe',
        'events' => [
            'invoice.payment_succeeded',
            'invoice.payment_failed',
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.created',
            'customer.updated',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
        ]
    ],
    
    // Configuration des abonnements
    'subscriptions' => [
        'trial_period_days' => 0,
        'proration_behavior' => 'create_prorations',
        'collection_method' => 'charge_automatically',
        'billing_cycle_anchor' => null,
    ],
    
    // Configuration des factures
    'invoices' => [
        'auto_advance' => true,
        'collection_method' => 'charge_automatically',
        'days_until_due' => null,
        'default_payment_method' => null,
    ],
    
    // Configuration des paiements
    'payments' => [
        'confirmation_method' => 'automatic',
        'capture_method' => 'automatic',
        'payment_method_types' => ['card', 'sepa_debit'],
        'setup_future_usage' => 'off_session',
    ],
    
    // URLs de redirection
    'urls' => [
        'success' => '/payment/success',
        'cancel' => '/payment/cancel',
        'account_portal' => 'https://account.cover-ar.com',
    ],
    
    // Configuration des produits
    'products' => [
        'metadata_keys' => [
            'client_id',
            'subscription_type',
            'material_id',
            'category_access',
        ]
    ],
    
    // Configuration du checkout
    'checkout' => [
        'mode' => 'subscription', // payment, setup, subscription
        'allow_promotion_codes' => false,
        'billing_address_collection' => 'required',
        'customer_creation' => 'always',
        'payment_method_collection' => 'always',
        'submit_type' => null,
    ],
    
    // Configuration du portail client
    'customer_portal' => [
        'enabled' => true,
        'features' => [
            'invoice_history' => ['enabled' => true],
            'payment_method_update' => ['enabled' => true],
            'subscription_cancel' => ['enabled' => false],
            'subscription_pause' => ['enabled' => false],
            'subscription_update' => [
                'enabled' => true,
                'default_allowed_updates' => ['price', 'quantity'],
                'proration_behavior' => 'create_prorations',
            ],
        ],
        'business_profile' => [
            'headline' => 'Gérez votre abonnement Cover AR',
            'privacy_policy_url' => 'https://cover-ar.com/privacy',
            'terms_of_service_url' => 'https://cover-ar.com/terms',
        ],
    ],
    
    // Configuration des logs
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'stripe.log',
        'log_requests' => true,
        'log_responses' => false, // Attention aux données sensibles
    ],
    
    // Configuration du cache
    'cache' => [
        'products' => 3600, // Cache des produits en secondes
        'prices' => 3600,   // Cache des prix en secondes
        'customers' => 1800, // Cache des clients en secondes
    ],
    
    // Configuration des taxes
    'tax' => [
        'automatic_tax' => [
            'enabled' => true,
        ],
        'tax_id_collection' => [
            'enabled' => true,
        ],
    ],
    
    // Messages personnalisés
    'messages' => [
        'payment_success' => 'Votre paiement a été traité avec succès.',
        'payment_failed' => 'Votre paiement a échoué. Veuillez réessayer.',
        'subscription_created' => 'Votre abonnement a été créé avec succès.',
        'subscription_updated' => 'Votre abonnement a été mis à jour.',
        'subscription_cancelled' => 'Votre abonnement a été annulé.',
    ],
];
?>