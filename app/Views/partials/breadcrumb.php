<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$breadcrumbs = [];

// Définir les breadcrumbs selon la page
switch ($currentPath) {
    case '/':
    case '/dashboard':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => null]
        ];
        break;
    case '/clients':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Clients', 'url' => null]
        ];
        break;
    case '/users':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Utilisateurs', 'url' => null]
        ];
        break;
    case '/categories':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Catégories', 'url' => null]
        ];
        break;
    case '/materials':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Matériel', 'url' => null]
        ];
        break;
    case '/subscription-plans':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Formules d\'abonnement', 'url' => null]
        ];
        break;
    case '/subscriptions':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Abonnements clients', 'url' => null]
        ];
        break;
    case '/invoices':
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard'],
            ['title' => 'Factures', 'url' => null]
        ];
        break;
    default:
        $breadcrumbs = [
            ['title' => 'Tableau de bord', 'url' => '/dashboard']
        ];
}

if (!empty($breadcrumbs)):
?>
<div class="breadcrumb-container">
    <nav class="breadcrumb">
        <ol class="breadcrumb-list">
            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item <?= $breadcrumb['url'] === null ? 'active' : '' ?>">
                    <?php if ($breadcrumb['url']): ?>
                        <a href="<?= $breadcrumb['url'] ?>" class="breadcrumb-link">
                            <?= htmlspecialchars($breadcrumb['title']) ?>
                        </a>
                    <?php else: ?>
                        <span class="breadcrumb-current"><?= htmlspecialchars($breadcrumb['title']) ?></span>
                    <?php endif; ?>
                    
                    <?php if ($index < count($breadcrumbs) - 1): ?>
                        <svg class="breadcrumb-separator" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>
<?php endif; ?>