<?php 
$pageTitle = $pageTitle ?? 'Gestion des clients';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $clients, $stats, $countries, $pagination, $search, $filters;
    
    // Valeurs par défaut
    $clients = $clients ?? [];
    $stats = $stats ?? [
        'total_clients' => 0,
        'active_clients' => 0,
        'with_subscriptions' => 0,
        'new_this_month' => 0
    ];
    $countries = $countries ?? [];
    $search = $search ?? '';
    $filters = $filters ?? [];
?>

<div class="clients-management">
    <!-- Filtres -->
    <div class="filters-card">
        <form method="GET" class="filters-form">
            <div class="filters-content">
                <div class="filter-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Nom, email, ville...">
                </div>
                <div class="filter-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>Actif</option>
                        <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="country">Pays</label>
                    <select id="country" name="country">
                        <option value="">Tous les pays</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country['pays']) ?>" 
                                    <?= ($filters['country'] ?? '') === $country['pays'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($country['pays']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="subscription_status">Abonnements</label>
                    <select id="subscription_status" name="subscription_status">
                        <option value="">Tous</option>
                        <option value="active" <?= ($filters['subscription_status'] ?? '') === 'active' ? 'selected' : '' ?>>Avec abonnements actifs</option>
                        <option value="inactive" <?= ($filters['subscription_status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Sans abonnement actif</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="/clients" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table des clients -->
    <div class="clients-card">
        <div class="card-content">
            <?php if (empty($clients)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3>Aucun client trouvé</h3>
                    <p>Commencez par ajouter votre premier client.</p>
                    <a href="/clients/create" class="btn btn-primary">Ajouter un client</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="clients-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Localisation</th>
                                <th>Utilisateurs</th>
                                <th>Abonnements</th>
                                <th>Dernière activité</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>
                                        <div class="client-info">
                                            <div class="client-name">
                                                <a href="/clients/<?= $client['id'] ?>" class="client-link">
                                                    <?= htmlspecialchars($client['raison_sociale']) ?>
                                                </a>
                                            </div>
                                            <div class="client-email"><?= htmlspecialchars($client['email_facturation']) ?></div>
                                            <?php if (!empty($client['numero_tva'])): ?>
                                                <div class="client-tva">TVA: <?= htmlspecialchars($client['numero_tva']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="location-info">
                                            <div class="location-city"><?= htmlspecialchars($client['ville']) ?></div>
                                            <div class="location-country"><?= htmlspecialchars($client['pays']) ?></div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="users-count">
                                            <span class="count-number"><?= $client['nb_users'] ?></span>
                                            <span class="count-label">utilisateur<?= $client['nb_users'] > 1 ? 's' : '' ?></span>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="subscriptions-info">
                                            <div class="subscriptions-count">
                                                <span class="count-number"><?= $client['nb_subscriptions'] ?></span>
                                                <span class="count-label">abonnement<?= $client['nb_subscriptions'] > 1 ? 's' : '' ?></span>
                                            </div>
                                            <?php if (!empty($client['formules_souscrites'])): ?>
                                                <div class="subscriptions-plans">
                                                    <?= htmlspecialchars(substr($client['formules_souscrites'], 0, 50)) ?>
                                                    <?= strlen($client['formules_souscrites']) > 50 ? '...' : '' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <?php if ($client['derniere_souscription']): ?>
                                            <span class="activity-date">
                                                <?= date('d/m/Y', strtotime($client['derniere_souscription'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="activity-date">
                                                <?= date('d/m/Y', strtotime($client['date_creation'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="status-badge status-<?= $client['actif'] ? 'active' : 'inactive' ?>">
                                            <?= $client['actif'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="actions-menu">
                                            <a href="/clients/<?= $client['id'] ?>" class="action-item" title="Voir les détails">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            <a href="/clients/<?= $client['id'] ?>/edit" class="action-item" title="Modifier">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="/users/create?client_id=<?= $client['id'] ?>" class="action-item" title="Ajouter un utilisateur">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                                    <circle cx="9" cy="7" r="4"></circle>
                                                    <line x1="19" y1="8" x2="19" y2="14"></line>
                                                    <line x1="22" y1="11" x2="16" y2="11"></line>
                                                </svg>
                                            </a>
                                            <a href="/subscriptions/create?client_id=<?= $client['id'] ?>" class="action-item" title="Créer un abonnement">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                                    <line x1="8" y1="21" x2="16" y2="21"></line>
                                                    <line x1="12" y1="17" x2="12" y2="21"></line>
                                                </svg>
                                            </a>
                                            <a href="/clients/<?= $client['id'] ?>/toggle" 
                                               class="action-item" 
                                               title="<?= $client['actif'] ? 'Désactiver' : 'Activer' ?>"
                                               onclick="return confirm('Êtes-vous sûr de vouloir <?= $client['actif'] ? 'désactiver' : 'activer' ?> ce client ?')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <?php if ($client['actif']): ?>
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                    <?php else: ?>
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

                <!-- Pagination -->
                <?php if ($pagination && $pagination->getTotalPages() > 1): ?>
                    <div class="pagination-container">
                        <?php require_once 'app/Views/partials/pagination.php'; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.clients-management {
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
}

.clients-card {
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

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name {
    font-weight: 500;
}

.client-link {
    color: var(--primary-color);
    text-decoration: none;
}

.client-link:hover {
    text-decoration: underline;
}

.client-email {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.client-tva {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-family: monospace;
}

.location-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.location-city {
    font-weight: 500;
    color: var(--text-primary);
}

.location-country {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.users-count,
.subscriptions-count {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
}

.count-number {
    font-weight: 600;
    color: var(--text-primary);
}

.count-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.subscriptions-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.subscriptions-plans {
    font-size: 0.75rem;
    color: var(--text-muted);
    line-height: 1.2;
}

.activity-date {
    font-size: 0.875rem;
    color: var(--text-primary);
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

.pagination-container {
    padding: 1rem;
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
    
    .filters-content {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        flex-direction: column;
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