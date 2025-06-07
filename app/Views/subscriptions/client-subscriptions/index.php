<?php 
$pageTitle = $pageTitle ?? 'Abonnements clients';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $subscriptions, $stats, $formules, $clients, $pagination, $search, $filters;
    // Valeurs par défaut
    $subscriptions = $subscriptions ?? [];
    $stats = $stats ?? [
        'total_subscriptions' => 0,
        'active_subscriptions' => 0,
        'pending_subscriptions' => 0,
        'expiring_soon' => 0,
        'monthly_revenue' => 0
    ];
    $formules = $formules ?? [];
    $clients = $clients ?? [];
    $search = $search ?? '';
    $filters = $filters ?? [];
?>

<div class="subscriptions-index">
    <!-- Header avec stats -->
    <div class="page-header">
        <div class="page-header-content">
            <h1>Abonnements clients</h1>
            <div class="page-actions">
                <a href="/subscriptions/create" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nouvel abonnement
                </a>
                <form method="POST" action="/admin/send-expiration-notifications" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Envoyer les notifications d\'expiration ?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        Notifications
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_subscriptions']) ?></div>
                <div class="stat-label">Total abonnements</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
        </div>

        <div class="stat-card stat-success">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['active_subscriptions']) ?></div>
                <div class="stat-label">Abonnements actifs</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card stat-warning">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['pending_subscriptions']) ?></div>
                <div class="stat-label">En attente</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card stat-danger">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['expiring_soon']) ?></div>
                <div class="stat-label">Expirent bientôt</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
        </div>

        <div class="stat-card stat-revenue">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['monthly_revenue'], 2) ?>€</div>
                <div class="stat-label">Revenus mensuels</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filtres de recherche -->
    <div class="filters-card">
        <form method="GET" action="/subscriptions" class="filters-form">
            <div class="filters-content">
                <div class="filter-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Client, email, formule...">
                </div>

                <div class="filter-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="actif" <?= ($filters['status'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                        <option value="en_attente" <?= ($filters['status'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="suspendu" <?= ($filters['status'] ?? '') === 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                        <option value="annule" <?= ($filters['status'] ?? '') === 'annule' ? 'selected' : '' ?>>Annulé</option>
                        <option value="expire_soon" <?= ($filters['status'] ?? '') === 'expire_soon' ? 'selected' : '' ?>>Expire bientôt</option>
                        <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expiré</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <option value="">Tous les types</option>
                        <option value="application" <?= ($filters['type'] ?? '') === 'application' ? 'selected' : '' ?>>Application seule</option>
                        <option value="application_materiel" <?= ($filters['type'] ?? '') === 'application_materiel' ? 'selected' : '' ?>>Application + Matériel</option>
                        <option value="materiel_seul" <?= ($filters['type'] ?? '') === 'materiel_seul' ? 'selected' : '' ?>>Matériel seul</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="formule">Formule</label>
                    <select id="formule" name="formule">
                        <option value="">Toutes les formules</option>
                        <?php foreach ($formules as $formule): ?>
                            <option value="<?= $formule['id'] ?>" 
                                    <?= ($filters['formule'] ?? '') == $formule['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($formule['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Filtrer
                    </button>
                    <a href="/subscriptions" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table des abonnements -->
    <div class="subscriptions-card">
        <div class="card-content">
            <?php if (empty($subscriptions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <h3>Aucun abonnement trouvé</h3>
                    <p>Aucun abonnement ne correspond à vos critères de recherche.</p>
                    <a href="/subscriptions/create" class="btn btn-primary">Créer un abonnement</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="subscriptions-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Formule</th>
                                <th>Type</th>
                                <th>Utilisateurs</th>
                                <th>Prix mensuel</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr data-subscription-id="<?= $subscription['id'] ?>" 
                                    class="subscription-row <?= $subscription['statut'] === 'actif' ? 'active' : '' ?>">
                                    
                                    <!-- Client -->
                                    <td>
                                        <div class="client-info">
                                            <div class="client-name">
                                                <a href="/clients/<?= $subscription['client_id'] ?>" class="text-link">
                                                    <?= htmlspecialchars($subscription['raison_sociale']) ?>
                                                </a>
                                            </div>
                                            <div class="client-location"><?= htmlspecialchars($subscription['ville']) ?></div>
                                        </div>
                                    </td>
                                    
                                    <!-- Formule -->
                                    <td>
                                        <div class="formule-info">
                                            <div class="formule-name"><?= htmlspecialchars($subscription['formule_nom']) ?></div>
                                            <?php if (!empty($subscription['materiel_nom'])): ?>
                                                <div class="materiel-name">+ <?= htmlspecialchars($subscription['materiel_nom']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <!-- Type -->
                                    <td>
                                        <span class="type-badge type-<?= $subscription['type_abonnement'] ?>">
                                            <?php
                                            $typeLabels = [
                                                'application' => 'App',
                                                'application_materiel' => 'App + Mat',
                                                'materiel_seul' => 'Matériel'
                                            ];
                                            echo $typeLabels[$subscription['type_abonnement']] ?? $subscription['type_abonnement'];
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Utilisateurs -->
                                    <td>
                                        <span class="user-count"><?= $subscription['nombre_utilisateurs_actuels'] ?></span>
                                    </td>
                                    
                                    <!-- Prix -->
                                    <td>
                                        <div class="price-info">
                                            <span class="price"><?= number_format($subscription['prix_total_mensuel'], 2) ?>€</span>
                                            <span class="duration">/<?= $subscription['duree'] === 'mensuelle' ? 'mois' : 'an' ?></span>
                                        </div>
                                    </td>
                                    
                                    <!-- Date début -->
                                    <td>
                                        <span class="date"><?= date('d/m/Y', strtotime($subscription['date_debut'])) ?></span>
                                    </td>
                                    
                                    <!-- Date fin -->
                                    <td>
                                        <?php if ($subscription['date_fin']): ?>
                                            <div class="date-info">
                                                <span class="date"><?= date('d/m/Y', strtotime($subscription['date_fin'])) ?></span>
                                                <?php if ($subscription['jours_restants'] !== null): ?>
                                                    <div class="days-remaining <?= $subscription['jours_restants'] <= 7 ? 'urgent' : ($subscription['jours_restants'] <= 30 ? 'warning' : '') ?>">
                                                        <?php if ($subscription['jours_restants'] > 0): ?>
                                                            <?= $subscription['jours_restants'] ?> jour<?= $subscription['jours_restants'] > 1 ? 's' : '' ?>
                                                        <?php elseif ($subscription['jours_restants'] === 0): ?>
                                                            Expire aujourd'hui
                                                        <?php else: ?>
                                                            Expiré
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-date">Sans limite</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Statut -->
                                    <td>
                                        <span class="status-badge status-<?= $subscription['statut'] ?>">
                                            <?= htmlspecialchars($subscription['statut_display']) ?>
                                        </span>
                                    </td>
                                    
                                    <!-- Actions -->
                                    <td>
                                        <div class="actions-menu">
                                            <button type="button" class="btn-action" onclick="toggleActionsMenu(this)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="1"></circle>
                                                    <circle cx="12" cy="5" r="1"></circle>
                                                    <circle cx="12" cy="19" r="1"></circle>
                                                </svg>
                                            </button>
                                            <div class="actions-dropdown">
                                                <a href="/subscriptions/<?= $subscription['id'] ?>" class="action-item">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    Voir détails
                                                </a>
                                                
                                                <a href="/subscriptions/<?= $subscription['id'] ?>/edit" class="action-item">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                    Modifier
                                                </a>
                                                
                                                <hr class="action-divider">
                                                
                                                <?php if ($subscription['statut'] === 'actif'): ?>
                                                    <a href="/subscriptions/<?= $subscription['id'] ?>/suspend" 
                                                       class="action-item"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir suspendre cet abonnement ?')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <rect x="6" y="4" width="4" height="16"></rect>
                                                            <rect x="14" y="4" width="4" height="16"></rect>
                                                        </svg>
                                                        Suspendre
                                                    </a>
                                                <?php elseif ($subscription['statut'] === 'suspendu'): ?>
                                                    <a href="/subscriptions/<?= $subscription['id'] ?>/resume" 
                                                       class="action-item"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir réactiver cet abonnement ?')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polygon points="5,3 19,12 5,21"></polygon>
                                                        </svg>
                                                        Réactiver
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($subscription['statut'], ['actif', 'suspendu'])): ?>
                                                    <a href="/subscriptions/<?= $subscription['id'] ?>/cancel" 
                                                       class="action-item action-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir annuler cet abonnement ? Cette action est irréversible.')">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                                        </svg>
                                                        Annuler
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination && $pagination->getTotalPages() > 1): ?>
                    <?php require_once 'app/Views/partials/pagination.php'; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.subscriptions-index {
    padding: 0;
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

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.page-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-card.stat-success {
    border-left: 4px solid var(--success-color);
}

.stat-card.stat-warning {
    border-left: 4px solid var(--warning-color);
}

.stat-card.stat-danger {
    border-left: 4px solid var(--error-color);
}

.stat-card.stat-revenue {
    border-left: 4px solid var(--primary-color);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.stat-icon {
    color: var(--text-muted);
}

.filters-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.filters-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
}

.filter-group input,
.filter-group select {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
}

.filter-actions {
    display: flex;
    gap: 0.5rem;
    align-items: end;
}

.subscriptions-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.empty-state p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

.subscription-row.active {
    background: rgba(34, 197, 94, 0.05);
}

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name a {
    font-weight: 500;
    color: var(--text-primary);
    text-decoration: none;
}

.client-name a:hover {
    color: var(--primary-color);
}

.client-location {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.formule-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.formule-name {
    font-weight: 500;
}

.materiel-name {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.type-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 12px;
}

.type-application {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.type-application_materiel {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.type-materiel_seul {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.user-count {
    font-weight: 500;
}

.price-info {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.price {
    font-weight: 600;
}

.duration {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.date {
    font-size: 0.875rem;
}

.date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.days-remaining {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-weight: 500;
}

.days-remaining.urgent {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.days-remaining.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.no-date {
    color: var(--text-muted);
    font-style: italic;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 12px;
}

.status-actif {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-en_attente {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.status-suspendu {
    background: rgba(156, 163, 175, 0.1);
    color: #6b7280;
}

.status-annule {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.actions-menu {
    position: relative;
}

.btn-action {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.btn-action:hover {
    background: var(--bg-secondary);
}

.actions-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    min-width: 150px;
    z-index: 1000;
    display: none;
}

.actions-dropdown.show {
    display: block;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: var(--text-primary);
    transition: var(--transition);
    font-size: 0.875rem;
}

.action-item:hover {
    background: var(--bg-secondary);
}

.action-danger {
    color: var(--error-color);
}

.action-divider {
    margin: 0;
    border: 0;
    border-top: 1px solid var(--border-color);
}

@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .page-actions {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-content {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        grid-column: 1 / -1;
        justify-content: stretch;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<script>
function toggleActionsMenu(button) {
    const dropdown = button.nextElementSibling;
    const isOpen = dropdown.classList.contains('show');
    
    // Fermer tous les autres menus
    document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
        menu.classList.remove('show');
    });
    
    // Toggle le menu actuel
    if (!isOpen) {
        dropdown.classList.add('show');
    }
}

// Fermer les menus quand on clique ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.actions-menu')) {
        document.querySelectorAll('.actions-dropdown.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Auto-submit du formulaire de filtres
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.filters-form');
    const inputs = form.querySelectorAll('select');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-submit après un court délai pour permettre les sélections multiples
            setTimeout(() => {
                form.submit();
            }, 100);
        });
    });
});
</script>

<?php } ?>