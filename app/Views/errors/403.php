<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - Cover AR Admin</title>
    <link rel="stylesheet" href="/public/css/admin.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M4.93 4.93l14.14 14.14"></path>
                </svg>
            </div>
            
            <div class="error-details">
                <h1 class="error-title">403</h1>
                <h2 class="error-subtitle">Accès refusé</h2>
                <p class="error-message">
                    Vous n'avez pas les permissions nécessaires pour accéder à cette page.
                </p>
                
                <div class="error-actions">
                    <a href="/dashboard" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9,22 9,12 15,12 15,22"></polyline>
                        </svg>
                        Retour au tableau de bord
                    </a>
                    <a href="javascript:history.back()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Page précédente
                    </a>
                </div>
            </div>
        </div>
        
        <div class="error-footer">
            <p>Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur système.</p>
        </div>
    </div>
</body>
</html>