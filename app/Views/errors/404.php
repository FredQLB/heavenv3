<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - Cover AR Admin</title>
    <link rel="stylesheet" href="/public/css/admin.css">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="M21 21l-4.35-4.35"></path>
                </svg>
            </div>
            
            <div class="error-details">
                <h1 class="error-title">404</h1>
                <h2 class="error-subtitle">Page non trouvée</h2>
                <p class="error-message">
                    La page que vous recherchez n'existe pas ou a été déplacée.
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
            <p>Vérifiez l'URL ou utilisez la navigation pour accéder à la page souhaitée.</p>
        </div>
    </div>
</body>
</html>