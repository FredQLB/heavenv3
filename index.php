<?php
require_once 'vendor/autoload.php';
require_once 'app/Helpers/Database.php';
require_once 'app/Helpers/Session.php';
require_once 'app/Helpers/Router.php';

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Router;

// Démarrer la session
Session::start();

// Initialiser la base de données
Database::init();

// Charger la configuration de l'application
$appConfig = require_once 'app/Config/app.php';

// Définir le timezone
date_default_timezone_set($appConfig['timezone']);

// Configuration des erreurs selon l'environnement
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Créer le routeur
$router = new Router();

// Routes d'authentification
$router->get('/', 'AuthController@showLogin');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@showForgotPassword');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password', 'AuthController@resetPassword');
$router->post('/reset-password', 'AuthController@resetPassword');

// Routes du tableau de bord (nécessite authentification)
$router->get('/dashboard', 'DashboardController@index', ['auth']);

// Routes des formules d'abonnement
$router->get('/subscription-plans', 'SubscriptionPlanController@index', ['auth']);
$router->get('/subscription-plans/create', 'SubscriptionPlanController@create', ['auth']);
$router->post('/subscription-plans', 'SubscriptionPlanController@store', ['auth']);
$router->get('/subscription-plans/{id}/edit', 'SubscriptionPlanController@edit', ['auth']);
$router->post('/subscription-plans/{id}/update', 'SubscriptionPlanController@update', ['auth']);
$router->get('/subscription-plans/{id}/toggle', 'SubscriptionPlanController@toggleStatus', ['auth']);
$router->get('/subscription-plans/{id}/delete', 'SubscriptionPlanController@delete', ['auth']);

// Routes des clients
$router->get('/clients', 'ClientController@index', ['auth']);
$router->get('/clients/create', 'ClientController@create', ['auth']);
$router->post('/clients', 'ClientController@store', ['auth']);
$router->get('/clients/{id}', 'ClientController@show', ['auth']);
$router->get('/clients/{id}/edit', 'ClientController@edit', ['auth']);
$router->post('/clients/{id}/update', 'ClientController@update', ['auth']);
$router->get('/clients/{id}/toggle', 'ClientController@toggleStatus', ['auth']);
$router->get('/clients/{id}/delete', 'ClientController@delete', ['auth']);

// Routes des utilisateurs
$router->get('/users', 'UserController@index', ['auth']);
$router->get('/users/create', 'UserController@create', ['auth']);
$router->post('/users', 'UserController@store', ['auth']);
$router->get('/users/{id}', 'UserController@show', ['auth']);
$router->get('/users/{id}/edit', 'UserController@edit', ['auth']);
$router->post('/users/{id}/update', 'UserController@update', ['auth']);
$router->get('/users/{id}/toggle', 'UserController@toggleStatus', ['auth']);
$router->get('/users/{id}/delete', 'UserController@delete', ['auth']);

// Routes des catégories
$router->get('/categories', 'CategoryController@index', ['auth']);
$router->get('/categories/create', 'CategoryController@create', ['auth']);
$router->post('/categories', 'CategoryController@store', ['auth']);
$router->get('/categories/{id}/edit', 'CategoryController@edit', ['auth']);
$router->post('/categories/{id}/update', 'CategoryController@update', ['auth']);
$router->get('/categories/{id}/delete', 'CategoryController@delete', ['auth']);

// Routes principales du matériel
$router->get('/materials', 'MaterialController@index', ['auth']);
$router->get('/materials/create', 'MaterialController@create', ['auth']);
$router->post('/materials', 'MaterialController@store', ['auth']);
$router->get('/materials/{id}', 'MaterialController@show', ['auth']);
$router->get('/materials/{id}/edit', 'MaterialController@edit', ['auth']);
$router->post('/materials/{id}/update', 'MaterialController@update', ['auth']);
$router->get('/materials/{id}/toggle', 'MaterialController@toggleStatus', ['auth']);
$router->get('/materials/{id}/delete', 'MaterialController@delete', ['auth']);

// Routes des locations de matériel
$router->get('/materials/rentals', 'MaterialController@rentals', ['auth']);
$router->get('/materials/rentals/create', 'MaterialController@createRental', ['auth']);
$router->post('/materials/rentals', 'MaterialController@storeRental', ['auth']);
$router->post('/materials/rentals/{id}/update', 'MaterialController@updateRental', ['auth']);

// Routes API pour le matériel
$router->get('/api/materials/available', 'ApiController@getAvailableMaterials', ['auth']);
$router->get('/api/materials/{id}/stats', 'ApiController@getMaterialStats', ['auth']);
$router->get('/api/materials/search', 'ApiController@searchMaterials', ['auth']);

// Routes des abonnements clients
$router->get('/subscriptions', 'SubscriptionController@index', ['auth']);
$router->get('/subscriptions/create', 'SubscriptionController@create', ['auth']);
$router->post('/subscriptions', 'SubscriptionController@store', ['auth']);
$router->get('/subscriptions/{id}', 'SubscriptionController@show', ['auth']);
$router->get('/subscriptions/{id}/edit', 'SubscriptionController@edit', ['auth']);
$router->post('/subscriptions/{id}/update', 'SubscriptionController@update', ['auth']);
$router->get('/subscriptions/{id}/cancel', 'SubscriptionController@cancel', ['auth']);
$router->get('/subscriptions/{id}/suspend', 'SubscriptionController@suspend', ['auth']);
$router->get('/subscriptions/{id}/resume', 'SubscriptionController@resume', ['auth']);

// Routes des factures
$router->get('/invoices', 'InvoiceController@index', ['auth']);
$router->get('/invoices/{id}', 'InvoiceController@show', ['auth']);
$router->get('/invoices/{id}/download', 'InvoiceController@download', ['auth']);
$router->post('/invoices/sync', 'InvoiceController@syncStripe', ['auth']);

// Routes API
$router->get('/api/clients/search', 'ApiController@searchClients', ['auth']);
$router->get('/api/users/search', 'ApiController@searchUsers', ['auth']);
$router->get('/api/materials/available', 'ApiController@getAvailableMaterials', ['auth']);
$router->get('/api/categories/tree', 'ApiController@getCategoriesTree', ['auth']);
$router->get('/api/stats/dashboard', 'ApiController@getDashboardStats', ['auth']);
$router->post('/api/subscription-plans/price-suggestion', 'SubscriptionPlanController@getPricingSuggestion', ['auth']);

// Routes des webhooks Stripe
$router->post('/webhooks/stripe', 'WebhookController@stripe');

// Routes d'administration système
$router->get('/admin/logs', 'AdminController@logs', ['auth']);
$router->get('/admin/logs/{file}', 'AdminController@viewLog', ['auth']);
$router->post('/admin/logs/clear', 'AdminController@clearLogs', ['auth']);
$router->get('/admin/cache/clear', 'AdminController@clearCache', ['auth']);
$router->get('/admin/backup', 'AdminController@backup', ['auth']);
$router->post('/admin/backup/create', 'AdminController@createBackup', ['auth']);

// Routes de profil utilisateur
$router->get('/profile', 'ProfileController@show', ['auth']);
$router->get('/profile/edit', 'ProfileController@edit', ['auth']);
$router->post('/profile/update', 'ProfileController@update', ['auth']);
$router->post('/profile/password', 'ProfileController@updatePassword', ['auth']);

// Routes des paramètres
$router->get('/settings', 'SettingsController@index', ['auth']);
$router->post('/settings/update', 'SettingsController@update', ['auth']);
$router->get('/settings/stripe', 'SettingsController@stripe', ['auth']);
$router->post('/settings/stripe/update', 'SettingsController@updateStripe', ['auth']);
$router->get('/settings/email', 'SettingsController@email', ['auth']);
$router->post('/settings/email/update', 'SettingsController@updateEmail', ['auth']);
$router->post('/settings/email/test', 'SettingsController@testEmail', ['auth']);

// Routes d'import/export
$router->get('/import', 'ImportController@index', ['auth']);
$router->post('/import/clients', 'ImportController@clients', ['auth']);
$router->post('/import/users', 'ImportController@users', ['auth']);
$router->get('/export/clients', 'ExportController@clients', ['auth']);
$router->get('/export/users', 'ExportController@users', ['auth']);
$router->get('/export/subscriptions', 'ExportController@subscriptions', ['auth']);
$router->get('/export/invoices', 'ExportController@invoices', ['auth']);

// Test d'envoi d'email pour un client
$router->get('/clients/{id}/test-email', 'ClientController@testEmail', ['auth']);

// Test de configuration email général
$router->get('/admin/test-email', function() {
    try {
        \App\Services\EmailService::testEmailConfiguration();
        \App\Helpers\Session::setFlash('success', 'Test email envoyé avec succès');
    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur test email', ['error' => $e->getMessage()]);
        \App\Helpers\Session::setFlash('error', 'Erreur lors du test email : ' . $e->getMessage());
    }
    header('Location: /settings');
    exit;
}, ['auth']);

// Envoi manuel des notifications d'expiration
$router->post('/admin/send-expiration-notifications', function() {
    try {
        $clientService = new \App\Services\ClientService();
        $result = $clientService->sendSubscriptionExpirationNotifications();
        
        \App\Helpers\Session::setFlash('success', 
            "Notifications envoyées : {$result['emails_sent']} sur {$result['total_subscriptions']} abonnements");
    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur notifications expiration', ['error' => $e->getMessage()]);
        \App\Helpers\Session::setFlash('error', 'Erreur : ' . $e->getMessage());
    }
    header('Location: /subscriptions');
    exit;
}, ['auth']);

// Envoi manuel des notifications de factures
$router->post('/admin/send-invoice-notifications', function() {
    try {
        $clientService = new \App\Services\ClientService();
        $result = $clientService->sendInvoiceNotifications();
        
        \App\Helpers\Session::setFlash('success', 
            "Notifications envoyées : {$result['emails_sent']} sur {$result['total_invoices']} factures");
    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur notifications factures', ['error' => $e->getMessage()]);
        \App\Helpers\Session::setFlash('error', 'Erreur : ' . $e->getMessage());
    }
    header('Location: /invoices');
    exit;
}, ['auth']);


// Retour de succès Stripe Checkout
$router->get('/subscriptions/checkout-success', 'SubscriptionController@checkoutSuccess');

// Retour d'annulation Stripe Checkout  
$router->get('/subscriptions/checkout-cancel', 'SubscriptionController@checkoutCancel');

// API pour obtenir les informations d'une formule (avec dépôts)
$router->get('/api/subscription-plans/{id}/pricing', function($id) {
    header('Content-Type: application/json');
    
    try {
        $formule = Database::fetch("
            SELECT fa.*, mm.nom as materiel_nom, mm.prix_mensuel, mm.depot_garantie
            FROM formules_abonnement fa
            LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            WHERE fa.id = ? AND fa.actif = 1
        ", [$id]);

        if (!$formule) {
            echo json_encode(['error' => 'Formule non trouvée']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'formule' => $formule,
            'pricing' => [
                'base_price' => $formule['prix_base'],
                'extra_user_cost' => $formule['cout_utilisateur_supplementaire'] ?? 0,
                'deposit_amount' => $formule['depot_garantie'] ?? 0,
                'material_name' => $formule['materiel_nom'],
                'duration' => $formule['duree']
            ]
        ]);

    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur API pricing formule', [
            'formule_id' => $id,
            'error' => $e->getMessage()
        ]);
        echo json_encode(['error' => 'Erreur serveur']);
    }
    exit;
}, ['auth']);

// API pour calculer le prix total avec dépôt
$router->post('/api/subscriptions/calculate-total', function() {
    header('Content-Type: application/json');
    
    try {
        $formuleId = (int)($_POST['formule_id'] ?? 0);
        $nombreUtilisateurs = (int)($_POST['nombre_utilisateurs'] ?? 1);

        if (!$formuleId) {
            echo json_encode(['error' => 'ID de formule requis']);
            exit;
        }

        $formule = Database::fetch("
            SELECT fa.*, mm.depot_garantie
            FROM formules_abonnement fa
            LEFT JOIN modeles_materiel mm ON fa.modele_materiel_id = mm.id
            WHERE fa.id = ? AND fa.actif = 1
        ", [$formuleId]);

        if (!$formule) {
            echo json_encode(['error' => 'Formule non trouvée']);
            exit;
        }

        // Calculer le prix de l'abonnement
        $utilisateursInclus = $formule['nombre_utilisateurs_inclus'];
        $utilisateursSupplementaires = max(0, $nombreUtilisateurs - $utilisateursInclus);
        $coutUtilisateurSupp = $formule['cout_utilisateur_supplementaire'] ?? 0;

        $prixBase = $formule['prix_base'];
        $prixUtilisateursSupp = $utilisateursSupplementaires * $coutUtilisateurSupp;
        $prixAbonnement = $prixBase + $prixUtilisateursSupp;

        // Dépôt de garantie
        $depotGarantie = 0;
        if (in_array($formule['type_abonnement'], ['application_materiel', 'materiel_seul'])) {
            $depotGarantie = $formule['depot_garantie'] ?? 0;
        }

        // Premier paiement = abonnement + dépôt
        $premierPaiement = $prixAbonnement + $depotGarantie;

        echo json_encode([
            'success' => true,
            'pricing' => [
                'base_price' => $prixBase,
                'extra_users_cost' => $prixUtilisateursSupp,
                'subscription_total' => $prixAbonnement,
                'deposit_amount' => $depotGarantie,
                'first_payment' => $premierPaiement,
                'monthly_recurring' => $prixAbonnement,
                'annual_total' => $prixAbonnement * 12,
                'users_included' => $utilisateursInclus,
                'extra_users' => $utilisateursSupplementaires,
                'requires_payment_setup' => $premierPaiement > 0
            ]
        ]);

    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur API calcul total', [
            'error' => $e->getMessage(),
            'post_data' => $_POST
        ]);
        echo json_encode(['error' => 'Erreur serveur']);
    }
    exit;
}, ['auth']);

// Route pour vérifier le statut de paiement d'un client
$router->get('/api/clients/{id}/payment-status', function($id) {
    header('Content-Type: application/json');
    
    try {
        $client = Database::fetch("SELECT * FROM clients WHERE id = ?", [$id]);
        
        if (!$client) {
            echo json_encode(['error' => 'Client non trouvé']);
            exit;
        }

        $paymentStatus = [
            'has_stripe_customer' => !empty($client['stripe_customer_id']),
            'needs_payment_setup' => true,
            'can_auto_charge' => false
        ];

        if (!empty($client['stripe_customer_id'])) {
            try {
                $hasValidPM = \App\Services\StripeService::hasValidPaymentMethod($client['stripe_customer_id']);
                $paymentStatus['needs_payment_setup'] = !$hasValidPM;
                $paymentStatus['can_auto_charge'] = $hasValidPM;
            } catch (\Exception $e) {
                // En cas d'erreur Stripe, assume qu'un setup est nécessaire
                \App\Helpers\Logger::warning('Erreur vérification Stripe pour client', [
                    'client_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'payment_status' => $paymentStatus
        ]);

    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur API statut paiement client', [
            'client_id' => $id,
            'error' => $e->getMessage()
        ]);
        echo json_encode(['error' => 'Erreur serveur']);
    }
    exit;
}, ['auth']);

// Route pour forcer la synchronisation avec Stripe
$router->post('/api/subscriptions/{id}/sync-stripe', function($id) {
    header('Content-Type: application/json');
    
    try {
        $subscription = Database::fetch("SELECT * FROM abonnements_clients WHERE id = ?", [$id]);
        
        if (!$subscription) {
            echo json_encode(['error' => 'Abonnement non trouvé']);
            exit;
        }

        // Tenter de synchroniser avec Stripe
        if (!empty($subscription['stripe_subscription_id'])) {
            $stripeSubscription = \App\Services\StripeService::retrieveSubscription($subscription['stripe_subscription_id']);
            
            // Mettre à jour le statut local selon Stripe
            $newStatus = match($stripeSubscription->status) {
                'active' => 'actif',
                'canceled' => 'annule',
                'paused' => 'suspendu',
                'incomplete' => 'en_attente',
                default => $subscription['statut']
            };

            if ($newStatus !== $subscription['statut']) {
                Database::update('abonnements_clients', [
                    'statut' => $newStatus,
                    'date_modification' => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);

                echo json_encode([
                    'success' => true,
                    'updated' => true,
                    'old_status' => $subscription['statut'],
                    'new_status' => $newStatus
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'updated' => false,
                    'message' => 'Abonnement déjà synchronisé'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Aucun abonnement Stripe associé'
            ]);
        }

    } catch (\Exception $e) {
        \App\Helpers\Logger::error('Erreur synchronisation Stripe', [
            'subscription_id' => $id,
            'error' => $e->getMessage()
        ]);
        echo json_encode(['error' => 'Erreur lors de la synchronisation']);
    }
    exit;
}, ['auth']);



// Routes d'erreur
$router->get('/error/403', function() {
    http_response_code(403);
    require_once 'app/Views/errors/403.php';
});

$router->get('/error/404', function() {
    http_response_code(404);
    require_once 'app/Views/errors/404.php';
});

$router->get('/error/500', function() {
    http_response_code(500);
    require_once 'app/Views/errors/500.php';
});

// Route de debug formules (développement uniquement)
if ($appConfig['env'] === 'local' && $appConfig['debug']) {
    $router->get('/debug-plans', function() {
        require_once 'debug_plans.php';
    });
}

// Route de debug SQL (développement uniquement)
if ($appConfig['env'] === 'local' && $appConfig['debug']) {
    $router->get('/debug-sql', function() {
        require_once 'debug_sql.php';
    });
}

// Route de test Stripe (développement uniquement)
if ($appConfig['env'] === 'local' && $appConfig['debug']) {
    $router->get('/stripe-test', function() {
        $pageTitle = 'Test Stripe';
        require_once 'app/Views/admin/stripe-test.php';
    }, ['auth']);
}

// Route de test (développement uniquement)
if ($appConfig['env'] === 'local' && $appConfig['debug']) {
    $router->get('/test', function() {
        echo '<h1>Test Route</h1>';
        echo '<p>Environment: ' . $appConfig['env'] . '</p>';
        echo '<p>Debug: ' . ($appConfig['debug'] ? 'Enabled' : 'Disabled') . '</p>';
        echo '<p>Database: Connected</p>';
        echo '<p>Session: ' . (Session::isLoggedIn() ? 'Logged in as ' . Session::getUserName() : 'Not logged in') . '</p>';
        echo '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';
        
        // Test de la base de données
        try {
            $users = Database::fetchAll("SELECT COUNT(*) as count FROM utilisateurs");
            echo '<p>Users in database: ' . $users[0]['count'] . '</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">Database error: ' . $e->getMessage() . '</p>';
        }
        
        phpinfo();
    });
}

// Traitement de la requête
try {
    $router->dispatch();
} catch (Exception $e) {
    // Logger l'erreur
    error_log(date('Y-m-d H:i:s') . " - Erreur: " . $e->getMessage() . "\n", 3, $appConfig['log']['path'] . 'error.log');
    
    // Logger avec le système de logs si disponible
    if (class_exists('\App\Helpers\Logger')) {
        \App\Helpers\Logger::error('Erreur de routage', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // En mode debug, afficher l'erreur
    if ($appConfig['debug']) {
        echo '<h1>Erreur de routage</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Fichier:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Ligne:</strong> ' . $e->getLine() . '</p>';
        echo '<p><strong>URI:</strong> ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // En production, rediriger vers la page d'erreur
        header('Location: /error/500');
    }
    exit;
}
?>