<?php 
$pageTitle = $pageTitle ?? 'Détails du client';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $client, $users, $subscriptions, $materials, $categories, $invoices, $client_stats;
    
    // Valeurs par défaut
    $users = $users ?? [];
    $subscriptions = $subscriptions ?? [];
    $materials = $materials ?? [];
    $categories = $categories ?? [];
    $invoices = $invoices ?? [];
    $client_stats = $client_stats ?? [];
?>

<div class="client-show">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/clients" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <div class="page-title">
                    <h1><?= htmlspecialchars($client['raison_sociale']) ?></h1>
                    <div class="client-badges">
                        <span class="status-badge status-<?= $client['actif'] ? 'active' : 'inactive' ?>">
                            <?= $client['actif'] ? 'Actif' : 'Inactif' ?>
                        </span>
                        <?php if (!empty($client['stripe_customer_id'])): ?>
                            <span class="stripe-badge">Stripe</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="page-actions">
                <a href="/users/create?client_id=<?= $client['id'] ?>" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <line x1="19" y1="8" x2="19" y2="14"></line>
                        <line x1="22" y1="11" x2="16" y2="11"></line>
                    </svg>
                    Ajouter utilisateur
                </a>
                <a href="/subscriptions/create?client_id=<?= $client['id'] ?>" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    Nouvel abonnement
                </a>
                <a href="/clients/<?= $client['id'] ?>/edit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Modifier
                </a>
            </div>
        </div>
    </div>

    <!-- Informations générales -->
    <div class="client-overview">
        <div class="overview-card">
            <h3>Informations client</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Email de facturation</span>
                    <span class="info-value"><?= htmlspecialchars($client['email_facturation']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Adresse</span>
                    <span class="info-value">
                        <?= htmlspecialchars($client['adresse']) ?><br>
                        <?= htmlspecialchars($client['code_postal']) ?> <?= htmlspecialchars($client['ville']) ?><br>
                        <?= htmlspecialchars($client['pays']) ?>
                    </span>
                </div>
                <?php if (!empty($client['numero_tva'])): ?>
                <div class="info-item">
                    <span class="info-label">Numéro TVA</span>
                    <span class="info-value"><?= htmlspecialchars($client['numero_tva']) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <span class="info-label">Date de création</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($client['date_creation'])) ?></span>
                </div>
                <?php if ($client['date_modification'] !== $client['date_creation']): ?>
                <div class="info-item">
                    <span class="info-label">Dernière modification</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($client['date_modification'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($client['stripe_customer_id'])): ?>
                <div class="info-item">
                    <span class="info-label">ID Stripe</span>
                    <span class="info-value stripe-id"><?= htmlspecialchars($client['stripe_customer_id']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistiques du client -->
        <div class="stats-card">
            <h3>Statistiques</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $client_stats['total_users'] ?? 0 ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $client_stats['active_subscriptions'] ?? 0 ?></div>
                    <div class="stat-label">Abonnements actifs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $client_stats['active_materials'] ?? 0 ?></div>
                    <div class="stat-label">Matériels loués</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($client_stats['revenue_mensuel'] ?? 0, 0) ?>€</div>
                    <div class="stat-label">CA mensuel</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Utilisateurs -->
    <div class="content-section">
        <div class="section-header">
            <h3>Utilisateurs (<?= count($users) ?>)</h3>
            <a href="/users/create?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter
            </a>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <p>Aucun utilisateur associé à ce client.</p>
                <a href="/users/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">Créer un utilisateur</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Connexion</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="user-type user-type-<?= strtolower($user['type_utilisateur']) ?>">
                                        <?= htmlspecialchars($user['type_utilisateur']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($user['telephone'] ?: 'Non renseigné') ?>
                                </td>
                                <td>
                                    <span class="connection-status <?= $user['identifiant_appareil'] ? 'connected' : 'not-connected' ?>">
                                        <?= $user['statut_connexion'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $user['actif'] ? 'active' : 'inactive' ?>">
                                        <?= $user['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-menu">
                                        <a href="/users/<?= $user['id'] ?>/edit" class="action-item" title="Modifier">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <a href="/users/<?= $user['id'] ?>/toggle" class="action-item" title="<?= $user['actif'] ? 'Désactiver' : 'Activer' ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <?php if ($user['actif']): ?>
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                                <?php endif; ?>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Abonnements -->
    <div class="content-section">
        <div class="section-header">
            <h3>Abonnements (<?= count($subscriptions) ?>)</h3>
            <a href="/subscriptions/create?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter
            </a>
        </div>
        
        <?php if (empty($subscriptions)): ?>
            <div class="empty-state">
                <p>Aucun abonnement pour ce client.</p>
                <a href="/subscriptions/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">Créer un abonnement</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Prix mensuel</th>
                            <th>Prochaine facture</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr>
                                <td>
                                    <div class="subscription-info">
                                        <div class="subscription-name"><?= htmlspecialchars($subscription['formule_nom']) ?></div>
                                        <div class="subscription-id">ID: <?= $subscription['id'] ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="subscription-type type-<?= $subscription['type_abonnement'] ?>">
                                        <?php
                                        $types = [
                                            'principal' => 'Principal',
                                            'supplementaire' => 'Supplémentaire'
                                        ];
                                        echo $types[$subscription['type_abonnement']] ?? $subscription['type_abonnement'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="subscription-period">
                                        <div class="period-start">Depuis le <?= date('d/m/Y', strtotime($subscription['date_debut'])) ?></div>
                                        <?php if ($subscription['date_fin']): ?>
                                            <div class="period-end">Jusqu'au <?= date('d/m/Y', strtotime($subscription['date_fin'])) ?></div>
                                        <?php else: ?>
                                            <div class="period-ongoing">En cours</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="price-amount"><?= number_format($subscription['prix_total_mensuel'], 2) ?>€</span>
                                </td>
                                <td>
                                    <?php if ($subscription['prochaine_facture'] && $subscription['statut'] === 'actif'): ?>
                                        <span class="next-invoice"><?= date('d/m/Y', strtotime($subscription['prochaine_facture'])) ?></span>
                                    <?php else: ?>
                                        <span class="no-invoice">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-subscription-<?= $subscription['statut'] ?>">
                                        <?= ucfirst($subscription['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-menu">
                                        <a href="/subscriptions/<?= $subscription['id'] ?>" class="action-item" title="Voir">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                        <a href="/subscriptions/<?= $subscription['id'] ?>/edit" class="action-item" title="Modifier">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Matériel loué -->
    <div class="content-section">
        <div class="section-header">
            <h3>Matériel loué (<?= count($materials) ?>)</h3>
            <a href="/materials/rentals/create?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter
            </a>
        </div>
        
        <?php if (empty($materials)): ?>
            <div class="empty-state">
                <p>Aucun matériel loué par ce client.</p>
                <a href="/materials/rentals/create?client_id=<?= $client['id'] ?>" class="btn btn-primary">Louer du matériel</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Matériel</th>
                            <th>N° série</th>
                            <th>Date location</th>
                            <th>Type</th>
                            <th>Prix mensuel</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materials as $material): ?>
                            <tr>
                                <td>
                                    <div class="material-info">
                                        <div class="material-name"><?= htmlspecialchars($material['materiel_nom']) ?></div>
                                        <div class="material-id">Location #<?= $material['id'] ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="serial-number"><?= htmlspecialchars($material['numero_serie'] ?: 'N/A') ?></span>
                                </td>
                                <td>
                                    <span class="rental-date"><?= date('d/m/Y', strtotime($material['date_location'])) ?></span>
                                </td>
                                <td>
                                    <span class="rental-type type-<?= $material['inclus_dans_abonnement'] ? 'included' : 'separate' ?>">
                                        <?= $material['type_location'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="price-amount"><?= number_format($material['prix_mensuel'], 2) ?>€</span>
                                </td>
                                <td>
                                    <span class="status-badge status-material-<?= $material['statut'] ?>">
                                        <?php
                                        $statuses = [
                                            'loue' => 'Loué',
                                            'retourne' => 'Retourné',
                                            'maintenance' => 'Maintenance'
                                        ];
                                        echo $statuses[$material['statut']] ?? $material['statut'];
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Catégories autorisées -->
    <?php if (!empty($categories)): ?>
    <div class="content-section">
        <div class="section-header">
            <h3>Catégories autorisées (<?= count($categories) ?>)</h3>
            <a href="/clients/<?= $client['id'] ?>/categories" class="btn btn-sm btn-secondary">Gérer</a>
        </div>
        
        <div class="categories-grid">
            <?php 
            $categoriesGrouped = [];
            foreach ($categories as $cat) {
                $categoriesGrouped[$cat['categorie']][] = $cat['sous_categorie'];
            }
            ?>
            <?php foreach ($categoriesGrouped as $categoryName => $subCategories): ?>
                <div class="category-group">
                    <div class="category-name"><?= htmlspecialchars($categoryName) ?></div>
                    <div class="subcategories-list">
                        <?php foreach ($subCategories as $subCat): ?>
                            <span class="subcategory-tag"><?= htmlspecialchars($subCat) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Factures récentes -->
    <div class="content-section">
        <div class="section-header">
            <h3>Factures récentes (<?= count($invoices) ?>)</h3>
            <a href="/invoices?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-secondary">Voir toutes</a>
        </div>
        
        <?php if (empty($invoices)): ?>
            <div class="empty-state">
                <p>Aucune facture pour ce client.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Facture</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td>
                                    <div class="invoice-info">
                                        <div class="invoice-id"><?= htmlspecialchars($invoice['stripe_invoice_id']) ?></div>
                                        <div class="invoice-type"><?= ucfirst($invoice['type_facture']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="invoice-date"><?= date('d/m/Y', strtotime($invoice['date_facture'])) ?></span>
                                </td>
                                <td>
                                    <span class="price-amount"><?= number_format($invoice['montant'], 2) ?>€</span>
                                </td>
                                <td>
                                    <span class="status-badge status-payment-<?= strtolower(str_replace(' ', '-', $invoice['statut_paiement'])) ?>">
                                        <?= $invoice['statut_paiement'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-menu">
                                        <?php if (!empty($invoice['lien_telechargement'])): ?>
                                            <a href="<?= htmlspecialchars($invoice['lien_telechargement']) ?>" 
                                               class="action-item" title="Télécharger" target="_blank">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7,10 12,15 17,10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/invoices/<?= $invoice['id'] ?>" class="action-item" title="Voir">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.client-show {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.page-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-back {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.btn-back:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.page-title h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.client-badges {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.stripe-badge {
    background: rgba(99, 102, 241, 0.1);
    color: #3730a3;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.client-overview {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.overview-card,
.stats-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.overview-card h3,
.stats-card h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: var(--text-primary);
    line-height: 1.5;
}

.stripe-id {
    font-family: monospace;
    font-size: 0.875rem;
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.content-section {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.section-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

.empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-secondary);
}

.empty-state p {
    margin-bottom: 1rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 500;
    color: var(--text-primary);
}

.user-email {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.user-type {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.user-type-megaadmin {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.user-type-admin {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.user-type-user {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.connection-status {
    font-size: 0.875rem;
    font-weight: 500;
}

.connection-status.connected {
    color: var(--success-color);
}

.connection-status.not-connected {
    color: var(--text-muted);
}

.subscription-info,
.material-info,
.invoice-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.subscription-name,
.material-name {
    font-weight: 500;
    color: var(--text-primary);
}

.subscription-id,
.material-id {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.subscription-type,
.rental-type {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.type-principal {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.type-supplementaire {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.type-included {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.type-separate {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.subscription-period {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.period-start {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.period-end {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.period-ongoing {
    font-size: 0.75rem;
    color: var(--success-color);
    font-weight: 500;
}

.price-amount {
    font-weight: 600;
    color: var(--text-primary);
}

.next-invoice {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.no-invoice {
    color: var(--text-muted);
}

.serial-number {
    font-family: monospace;
    font-size: 0.875rem;
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.rental-date,
.invoice-date {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.invoice-type {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: capitalize;
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-active {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-inactive {
    background: rgba(156, 163, 175, 0.1);
    color: #6b7280;
}

.status-subscription-actif {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-subscription-suspendu {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.status-subscription-annule {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.status-subscription-en_attente {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.status-material-loue {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.status-material-retourne {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-material-maintenance {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.status-payment-payée {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-payment-en-attente {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.status-payment-en-retard {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.categories-grid {
    padding: 1.5rem;
    display: grid;
    gap: 1rem;
}

.category-group {
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.category-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.subcategories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.subcategory-tag {
    background: var(--bg-secondary);
    color: var(--text-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.actions-menu {
    display: flex;
    gap: 0.5rem;
}

.action-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    text-decoration: none;
    transition: var(--transition);
}

.action-item:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .client-show {
        max-width: none;
        margin: 0;
    }
    
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .page-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .client-overview {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .actions-menu {
        justify-content: center;
    }
}
</style>

<?php } ?>