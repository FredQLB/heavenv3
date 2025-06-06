<?php 
$pageTitle = $pageTitle ?? 'Gestion du matériel';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $materials, $stats;
    
    // Valeurs par défaut
    $materials = $materials ?? [];
    $stats = $stats ?? [
        'total_materials' => 0,
        'active_materials' => 0,
        'rented_count' => 0,
        'total_revenue' => 0
    ];
?>

<div class="materials-management">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <h1>Gestion du matériel</h1>
            <div class="page-actions">
                <a href="/materials/rentals" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <path d="M7 15h0M12 15h0M17 15h0"></path>
                    </svg>
                    Voir les locations
                </a>
                <a href="/materials/create" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nouveau matériel
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['total_materials'] ?></div>
                <div class="stat-label">Total matériels</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="M7 15h0M12 15h0M17 15h0"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['active_materials'] ?></div>
                <div class="stat-label">Matériels actifs</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['rented_count'] ?></div>
                <div class="stat-label">Actuellement loués</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_revenue'], 2) ?>€</div>
                <div class="stat-label">Revenus matériel</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
        <form method="GET" class="filters-form">
            <div class="filters-content">
                <div class="filter-group">
                    <label for="search">Recherche</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           placeholder="Nom, description...">
                </div>
                <div class="filter-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?= ($_GET['status'] ?? '') === '1' ? 'selected' : '' ?>>Actif</option>
                        <option value="0" <?= ($_GET['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactif</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="price_min">Prix min (€)</label>
                    <input type="number" id="price_min" name="price_min" step="0.01" min="0"
                           value="<?= htmlspecialchars($_GET['price_min'] ?? '') ?>"
                           placeholder="0.00">
                </div>
                <div class="filter-group">
                    <label for="price_max">Prix max (€)</label>
                    <input type="number" id="price_max" name="price_max" step="0.01" min="0"
                           value="<?= htmlspecialchars($_GET['price_max'] ?? '') ?>"
                           placeholder="1000.00">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                    <a href="/materials" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table des matériels -->
    <div class="materials-card">
        <div class="card-content">
            <?php if (empty($materials)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="M7 15h0M12 15h0M17 15h0"></path>
                        </svg>
                    </div>
                    <h3>Aucun matériel trouvé</h3>
                    <p>Commencez par ajouter votre premier modèle de matériel à louer.</p>
                    <a href="/materials/create" class="btn btn-primary">Ajouter un matériel</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="materials-table">
                        <thead>
                            <tr>
                                <th>Matériel</th>
                                <th>Prix mensuel</th>
                                <th>Dépôt garantie</th>
                                <th>Utilisation</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td>
                                        <div class="material-info">
                                            <div class="material-name">
                                                <a href="/materials/<?= $material['id'] ?>" class="material-link">
                                                    <?= htmlspecialchars($material['nom']) ?>
                                                </a>
                                            </div>
                                            <?php if (!empty($material['description'])): ?>
                                                <div class="material-description">
                                                    <?= htmlspecialchars(substr($material['description'], 0, 100)) ?>
                                                    <?= strlen($material['description']) > 100 ? '...' : '' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="price-amount"><?= number_format($material['prix_mensuel'], 2) ?>€</span>
                                    </td>
                                    
                                    <td>
                                        <span class="deposit-amount"><?= number_format($material['depot_garantie'], 2) ?>€</span>
                                    </td>
                                    
                                    <td>
                                        <div class="usage-stats">
                                            <?php $stats = $material['usage_stats'] ?? []; ?>
                                            <div class="usage-item">
                                                <span class="usage-label">Formules:</span>
                                                <span class="usage-count"><?= $stats['plans_count'] ?? 0 ?></span>
                                            </div>
                                            <div class="usage-item">
                                                <span class="usage-label">Locations:</span>
                                                <span class="usage-count"><?= $stats['active_rentals'] ?? 0 ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="status-badge status-<?= $material['actif'] ? 'active' : 'inactive' ?>">
                                            <?= $material['actif'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="actions-menu">
                                            <a href="/materials/<?= $material['id'] ?>" class="action-item" title="Voir les détails">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            <a href="/materials/<?= $material['id'] ?>/edit" class="action-item" title="Modifier">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                            </a>
                                            <a href="/materials/<?= $material['id'] ?>/toggle" 
                                               class="action-item" 
                                               title="<?= $material['actif'] ? 'Désactiver' : 'Activer' ?>"
                                               onclick="return confirm('Êtes-vous sûr de vouloir <?= $material['actif'] ? 'désactiver' : 'activer' ?> ce matériel ?')">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <?php if ($material['actif']): ?>
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                    <?php else: ?>
                                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                        <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                                    <?php endif; ?>
                                                </svg>
                                            </a>
                                            <a href="/materials/<?= $material['id'] ?>/delete" 
                                               class="action-item action-danger" 
                                               title="Supprimer"
                                               onclick="return confirmDelete('<?= htmlspecialchars($material['nom']) ?>', this.href)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <polyline points="3,6 5,6 21,6"></polyline>
                                                    <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
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
</div>

<style>
.materials-management {
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

.materials-card {
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

.material-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.material-name {
    font-weight: 500;
}

.material-link {
    color: var(--primary-color);
    text-decoration: none;
}

.material-link:hover {
    text-decoration: underline;
}

.material-description {
    font-size: 0.75rem;
    color: var(--text-secondary);
    line-height: 1.4;
}

.price-amount,
.deposit-amount {
    font-weight: 600;
    color: var(--text-primary);
}

.usage-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.usage-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
}

.usage-label {
    color: var(--text-secondary);
}

.usage-count {
    font-weight: 500;
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

.action-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
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
    
    .usage-stats {
        font-size: 0.75rem;
    }
}
</style>

<?php } ?>