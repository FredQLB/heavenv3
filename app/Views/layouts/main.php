<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - Cover AR Admin</title>
    <link rel="stylesheet" href="/public/css/admin.css">
</head>
<body class="admin-layout">
    <?php require_once 'app/Views/partials/header.php'; ?>
    
    <div class="admin-container">
        <?php require_once 'app/Views/partials/sidebar.php'; ?>
        
        <main class="main-content">
            <?php require_once 'app/Views/partials/breadcrumb.php'; ?>
            
            <div class="content-wrapper">
                <?php require_once 'app/Views/partials/alerts.php'; ?>
                
                <?php 
                if (function_exists('renderContent')) {
                    renderContent();
                } else {
                    echo '<p>Contenu non disponible</p>';
                }
                ?>
            </div>
        </main>
    </div>

    <?php require_once 'app/Views/partials/footer.php'; ?>
    
    <script src="/public/js/admin.js"></script>
</body>
</html>