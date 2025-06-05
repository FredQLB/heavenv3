<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Mot de passe oublié' ?> - Cover AR Admin</title>
    <link rel="stylesheet" href="/public/css/admin.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <h1>Cover AR</h1>
                    <p>Interface d'administration</p>
                </div>
            </div>

            <div class="auth-content">
                <h2>Mot de passe oublié</h2>
                <p class="text-muted">Saisissez votre adresse email pour recevoir un lien de réinitialisation.</p>

                <?php require_once 'app/Views/partials/alerts.php'; ?>

                <form method="POST" action="/forgot-password" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                    
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        Envoyer le lien de réinitialisation
                    </button>
                </form>

                <div class="auth-links">
                    <a href="/login">← Retour à la connexion</a>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; <?= date('Y') ?> Cover AR. Tous droits réservés.</p>
            </div>
        </div>
    </div>

    <script src="/public/js/admin.js"></script>
</body>
</html>