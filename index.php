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

// Routes du tableau de bord (nécessite authentification)
$router->get('/dashboard', 'DashboardController@index', ['auth']);

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

// Traitement de la requête
try {
    $router->dispatch();
} catch (Exception $e) {
    // Logger l'erreur
    error_log(date('Y-m-d H:i:s') . " - Erreur: " . $e->getMessage() . "\n", 3, $appConfig['log']['path'] . 'error.log');
    
    // Rediriger vers la page d'erreur
    header('Location: /error/500');
    exit;
}
?>