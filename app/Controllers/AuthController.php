<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Logger;
use App\Helpers\Config;

class AuthController
{
    public function showLogin()
    {
        // Si déjà connecté, rediriger vers le dashboard
        if (Session::isLoggedIn()) {
            header('Location: /dashboard');
            exit;
        }

        $pageTitle = 'Connexion';
        require_once 'app/Views/auth/login.php';
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Vérification CSRF
        if (!Session::verifyCsrfToken($csrfToken)) {
            Logger::warning('Tentative de connexion avec token CSRF invalide', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /login');
            exit;
        }

        // Validation des champs
        if (empty($email) || empty($password)) {
            Logger::info('Tentative de connexion avec champs vides', ['email' => $email]);
            Session::setFlash('error', 'Veuillez remplir tous les champs');
            header('Location: /login');
            exit;
        }

        // Vérification des identifiants
        try {
            $user = Database::fetch(
                "SELECT id, nom, prenom, email, mot_de_passe, type_utilisateur, actif, client_id 
                 FROM utilisateurs 
                 WHERE email = ? AND actif = 1",
                [$email]
            );

            if ($user && md5($password) === $user['mot_de_passe']) {
                // Seuls les MegaAdmin peuvent accéder à cette interface
                if ($user['type_utilisateur'] !== 'MegaAdmin') {
                    Logger::warning('Tentative de connexion non autorisée', [
                        'email' => $email,
                        'user_type' => $user['type_utilisateur']
                    ]);
                    Session::setFlash('error', 'Accès non autorisé à cette interface');
                    header('Location: /login');
                    exit;
                }

                // Connexion réussie
                Session::set('user_id', $user['id']);
                Session::set('user_type', $user['type_utilisateur']);
                Session::set('user_name', $user['prenom'] . ' ' . $user['nom']);
                Session::set('user_email', $user['email']);
                Session::set('client_id', $user['client_id']);

                // Enregistrer la connexion dans les logs
                Logger::logAuth('login_success', $user['id'], $user['email']);

                // Optionnel : Enregistrer en base si table logs_connexion existe
                try {
                    Database::query(
                        "INSERT INTO logs_connexion (utilisateur_id, ip_address, user_agent, date_connexion) 
                         VALUES (?, ?, ?, NOW())",
                        [$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']
                    );
                } catch (\Exception $e) {
                    // Table n'existe peut-être pas, ignorer l'erreur
                    Logger::debug('Impossible d\'enregistrer la connexion en base', [
                        'error' => $e->getMessage()
                    ]);
                }

                Session::setFlash('success', 'Connexion réussie. Bienvenue ' . $user['prenom'] . ' !');
                header('Location: /dashboard');
                exit;
            } else {
                Logger::logAuth('login_failed', null, $email);
                Session::setFlash('error', 'Email ou mot de passe incorrect');
                header('Location: /login');
                exit;
            }
        } catch (\Exception $e) {
            Logger::error("Erreur lors de la connexion : " . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            Session::setFlash('error', 'Une erreur est survenue lors de la connexion');
            header('Location: /login');
            exit;
        }
    }

    public function logout()
    {
        $userId = Session::getUserId();
        $userEmail = Session::get('user_email');

        if (Session::isLoggedIn()) {
            // Enregistrer la déconnexion dans les logs
            Logger::logAuth('logout', $userId, $userEmail);

            // Optionnel : Enregistrer en base si table logs_connexion existe
            try {
                Database::query(
                    "UPDATE logs_connexion SET date_deconnexion = NOW() 
                     WHERE utilisateur_id = ? AND date_deconnexion IS NULL 
                     ORDER BY date_connexion DESC LIMIT 1",
                    [$userId]
                );
            } catch (\Exception $e) {
                // Table n'existe peut-être pas, ignorer l'erreur
                Logger::debug('Impossible d\'enregistrer la déconnexion en base', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Session::destroy();
        Session::setFlash('success', 'Vous avez été déconnecté avec succès');
        header('Location: /login');
        exit;
    }

    public function showForgotPassword()
    {
        $pageTitle = 'Mot de passe oublié';
        require_once 'app/Views/auth/forgot-password.php';
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /forgot-password');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? '';

        // Vérification CSRF
        if (!Session::verifyCsrfToken($csrfToken)) {
            Logger::warning('Tentative de reset de mot de passe avec token CSRF invalide', [
                'email' => $email
            ]);
            Session::setFlash('error', 'Token de sécurité invalide');
            header('Location: /forgot-password');
            exit;
        }

        if (empty($email)) {
            Session::setFlash('error', 'Veuillez saisir votre adresse email');
            header('Location: /forgot-password');
            exit;
        }

        try {
            $user = Database::fetch(
                "SELECT id, email, nom, prenom FROM utilisateurs 
                 WHERE email = ? AND actif = 1 AND type_utilisateur = 'MegaAdmin'",
                [$email]
            );

            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));

                // Créer la table si elle n'existe pas
                $this->createPasswordResetTable();

                // Stocker le token en base
                Database::query(
                    "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()",
                    [$user['id'], $token, $expiry]
                );

                Logger::info('Token de réinitialisation généré', [
                    'user_id' => $user['id'],
                    'email' => $email
                ]);

                // Ici, vous pourriez envoyer un email avec le lien de réinitialisation
                // EmailService::sendPasswordReset($user['email'], $token);

                // Pour cette version de base, on affiche juste un message
                Session::setFlash('success', 'Si cette adresse email existe, vous recevrez un lien de réinitialisation');
            } else {
                // Même message pour éviter l'énumération d'emails
                Logger::info('Tentative de reset pour email inexistant', ['email' => $email]);
                Session::setFlash('success', 'Si cette adresse email existe, vous recevrez un lien de réinitialisation');
            }
        } catch (\Exception $e) {
            Logger::error("Erreur lors de la réinitialisation du mot de passe : " . $e->getMessage(), [
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            Session::setFlash('error', 'Une erreur est survenue');
        }

        header('Location: /forgot-password');
        exit;
    }

    /**
     * Créer la table password_reset_tokens si elle n'existe pas
     */
    private function createPasswordResetTable()
    {
        try {
            Database::query("
                CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    used_at DATETIME NULL,
                    UNIQUE KEY unique_user (user_id),
                    INDEX idx_token (token),
                    INDEX idx_expires (expires_at),
                    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
                )
            ");
        } catch (\Exception $e) {
            Logger::error("Erreur lors de la création de la table password_reset_tokens", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Réinitialiser le mot de passe avec un token
     */
    public function resetPassword()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            Session::setFlash('error', 'Token de réinitialisation manquant');
            header('Location: /login');
            exit;
        }

        try {
            // Vérifier le token
            $resetData = Database::fetch(
                "SELECT prt.*, u.email, u.nom, u.prenom 
                 FROM password_reset_tokens prt
                 JOIN utilisateurs u ON prt.user_id = u.id
                 WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used_at IS NULL",
                [$token]
            );

            if (!$resetData) {
                Logger::warning('Tentative d\'utilisation de token invalide ou expiré', ['token' => $token]);
                Session::setFlash('error', 'Token de réinitialisation invalide ou expiré');
                header('Location: /login');
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $csrfToken = $_POST['csrf_token'] ?? '';

                // Vérifications
                if (!Session::verifyCsrfToken($csrfToken)) {
                    Session::setFlash('error', 'Token de sécurité invalide');
                    header('Location: /reset-password?token=' . $token);
                    exit;
                }

                if (empty($password) || strlen($password) < 6) {
                    Session::setFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                    header('Location: /reset-password?token=' . $token);
                    exit;
                }

                if ($password !== $confirmPassword) {
                    Session::setFlash('error', 'Les mots de passe ne correspondent pas');
                    header('Location: /reset-password?token=' . $token);
                    exit;
                }

                // Mettre à jour le mot de passe
                Database::beginTransaction();
                try {
                    // Mettre à jour le mot de passe
                    Database::update(
                        'utilisateurs',
                        ['mot_de_passe' => md5($password)],
                        'id = ?',
                        [$resetData['user_id']]
                    );

                    // Marquer le token comme utilisé
                    Database::update(
                        'password_reset_tokens',
                        ['used_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$resetData['id']]
                    );

                    Database::commit();

                    Logger::info('Mot de passe réinitialisé avec succès', [
                        'user_id' => $resetData['user_id'],
                        'email' => $resetData['email']
                    ]);

                    Session::setFlash('success', 'Votre mot de passe a été réinitialisé avec succès');
                    header('Location: /login');
                    exit;
                } catch (\Exception $e) {
                    Database::rollback();
                    throw $e;
                }
            }

            // Afficher le formulaire de réinitialisation
            $pageTitle = 'Nouveau mot de passe';
            require_once 'app/Views/auth/reset-password.php';

        } catch (\Exception $e) {
            Logger::error("Erreur lors de la réinitialisation du mot de passe", [
                'token' => $token,
                'error' => $e->getMessage()
            ]);
            Session::setFlash('error', 'Une erreur est survenue lors de la réinitialisation');
            header('Location: /login');
            exit;
        }
    }
}
?>