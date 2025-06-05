<?php 
$pageTitle = $pageTitle ?? 'Formules d\'abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $plans, $stats;
    
    // Debug dans la vue
    if (\App\Helpers\Config::get('app.debug', false)) {
        echo "<!-- DEBUG: Plans count: " . (is_array($plans) ? count($plans) : 'not array') . " -->";
        echo "<!-- DEBUG: Stats: " . (is_array($stats) ? json_encode($stats) : 'not array') . " -->";
    }
    
    // Valeurs par défaut si les variables ne sont pas définies
    $plans = $plans ?? [];
    $stats = $stats ?? [
        'total_plans' => 0,
        'active_plans' => 0,
        'app_plans' => 0,
        'mixed_plans' => 0,
        'material_plans' => 0
    ];
?>

<div class="subscription-plans">
    <!-- Header avec stats -->
    <div class="page-header">
        <div class="page-header-content">
            <h1>Formules d'abonnement</h1>
            <div class="page-actions">
                <a href="/subscription-plans/create" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nouvelle formule
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['total_plans'] ?? 0 ?></div>
                <div class="stat-label">Total formules</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['active_plans'] ?? 0 ?></div>
                <div class="stat-label">Formules actives</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['app_plans'] ?? 0 ?></div>
                <div class="stat-label">App seulement</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $stats['mixed_plans'] ?? 0 ?></div>
                <div class="stat-label">App + Matériel</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                    <path d="M7 15h0M12 15h0M17 15h0"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
        <div class="filters-content">
            <div class="filter-group">
                <label for="filter-type">Type</label>
                <select id="filter-type" onchange="filterPlans()">
                    <option value="">Tous les types</option>
                    <option value="application">Application seule</option>
                    <option value="application_materiel">Application + Matériel</option>
                    <option value="materiel_seul">Matériel seul</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-status">Statut</label>
                <select id="filter-status" onchange="filterPlans()">
                    <option value="">Tous les statuts</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-duration">Durée</label>
                <select id="filter-duration" onchange="filterPlans()">
                    <option value="">Toutes les durées</option>
                    <option value="mensuelle">Mensuelle</option>
                    <option value="annuelle">Annuelle</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="text" id="filter-search" placeholder="Rechercher..." onkeyup="filterPlans()">
            </div>
        </div>
    </div>

    <!-- Table des formules -->
    <div class="plans-card">
        <div class="card-content">
            <?php if (empty($plans ?? [])): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <h3>Aucune formule d'abonnement</h3>
                    <p>Créez votre première formule pour commencer à proposer des abonnements à vos clients.</p>
                    <a href="/subscription-plans/create" class="btn btn-primary">Créer une formule</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="plans-table">
                        <thead>
                            <tr>
                                <th>Formule</th>
                                <th>Type</th>
                                <th>Prix</th>
                                <th>Utilisateurs</th>
                                <th>Catégories</th>
                                <th>Durée</th>
                                <th>Matériel</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($plans ?? []) as $plan): ?>
                                <tr data-type="<?= $plan['type_abonnement'] ?>" 
                                    data-status="<?= $plan['actif'] ?>" 
                                    data-duration="<?= $plan['duree'] ?>"
                                    data-name="<?= strtolower($plan['nom']) ?>">
                                    
                                    <td>
                                        <div class="plan-info">
                                            <div class="plan-name"><?= htmlspecialchars($plan['nom']) ?></div>
                                            <?php if (!empty($plan['lien_inscription'])): ?>
                                                <div class="plan-link">
                                                    <a href="<?= htmlspecialchars($plan['lien_inscription']) ?>" target="_blank" class="text-link">
                                                        Lien d'inscription
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge badge-type-<?= $plan['type_abonnement'] ?>">
                                            <?php
                                            $typeLabels = [
                                                'application' => 'App seule',
                                                'application_materiel' => 'App + Matériel',
                                                'materiel_seul' => 'Matériel seul'
                                            ];
                                            echo $typeLabels[$plan['type_abonnement']] ?? $plan['type_abonnement'];
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="price-info">
                                            <div class="base-price"><?= number_format($plan['prix_base'], 2) ?>€</div>
                                            <?php if ($plan['cout_utilisateur_supplementaire']): ?>
                                                <div class="extra-price">+<?= number_format($plan['cout_utilisateur_supplementaire'], 2) ?>€/user</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="user-count"><?= $plan['nombre_utilisateurs_inclus'] ?> inclus</span>
                                    </td>
                                    
                                    <td>
                                        <?php if ($plan['nombre_sous_categories']): ?>
                                            <span class="category-count"><?= $plan['nombre_sous_categories'] ?> catégories</span>
                                        <?php else: ?>
                                            <span class="category-unlimited">Illimité</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="duration-badge duration-<?= $plan['duree'] ?>">
                                            <?= ucfirst($plan['duree']) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php if ($plan['materiel_nom']): ?>
                                            <span class="material-name"><?= htmlspecialchars($plan['materiel_nom']) ?></span>
                                        <?php else: ?>
                                            <span class="no-material">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <span class="status-badge status-<?= $plan['actif'] ? 'active' : 'inactive' ?>">
                                            <?= $plan['actif'] ? 'Actif' : 'Inactif' ?>
                                        </span>
                                    </td>
                                    
                                    <td>

                                        <a href="/subscription-plans/<?= $plan['id'] ?>/edit" class="action-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </a>
                                        <a href="/subscription-plans/<?= $plan['id'] ?>/toggle" 
                                           class="action-item"
                                           onclick="return confirm('Êtes-vous sûr de vouloir <?= $plan['actif'] ? 'désactiver' : 'activer' ?> cette formule ?')">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <?php if ($plan['actif']): ?>
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                <?php else: ?>
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                                <?php endif; ?>
                                            </svg>
                                        </a>
                                        <a href="/subscription-plans/<?= $plan['id'] ?>/delete" 
                                           class="action-item action-danger"
                                           onclick="return confirmDelete('<?= htmlspecialchars($plan['nom']) ?>', this.href)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3,6 5,6 21,6"></polyline>
                                                <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                                            </svg>
                                        </a>
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
.subscription-plans {
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

.plans-card {
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

.plan-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.plan-name {
    font-weight: 500;
}

.plan-link {
    font-size: 0.75rem;
}

.text-link {
    color: var(--primary-color);
    text-decoration: none;
}

.text-link:hover {
    text-decoration: underline;
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 12px;
}

.badge-type-application {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.badge-type-application_materiel {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.badge-type-materiel_seul {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.price-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.base-price {
    font-weight: 600;
}

.extra-price {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.user-count,
.category-count,
.category-unlimited {
    font-size: 0.875rem;
}

.category-unlimited {
    color: var(--success-color);
    font-weight: 500;
}

.duration-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.duration-mensuelle {
    background: rgba(99, 102, 241, 0.1);
    color: #3730a3;
}

.duration-annuelle {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.material-name {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.no-material {
    color: var(--text-muted);
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
    
    .filters-content {
        grid-template-columns: 1fr;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}
</style>

<script>
function filterPlans() {
    const typeFilter = document.getElementById('filter-type').value;
    const statusFilter = document.getElementById('filter-status').value;
    const durationFilter = document.getElementById('filter-duration').value;
    const searchFilter = document.getElementById('filter-search').value.toLowerCase();
    
    const rows = document.querySelectorAll('#plans-table tbody tr');
    
    rows.forEach(row => {
        const type = row.dataset.type;
        const status = row.dataset.status;
        const duration = row.dataset.duration;
        const name = row.dataset.name;
        
        let show = true;
        
        if (typeFilter && type !== typeFilter) {
            show = false;
        }
        
        if (statusFilter && status !== statusFilter) {
            show = false;
        }
        
        if (durationFilter && duration !== durationFilter) {
            show = false;
        }
        
        if (searchFilter && !name.includes(searchFilter)) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

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
</script>

<?php } ?>