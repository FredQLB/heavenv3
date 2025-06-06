<?php 
$pageTitle = $pageTitle ?? 'Locations de matériel';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $rentals, $rental_stats;
    
    // Valeurs par défaut
    $rentals = $rentals ?? [];
    $rental_stats = $rental_stats ?? [
        'total_rentals' => 0,
        'active_rentals' => 0,
        'maintenance_count' => 0,
        'returned_count' => 0
    ];
?>

<div class="materials-rentals">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/materials" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour au matériel
                </a>
                <h1>Locations de matériel</h1>
            </div>
            <div class="page-actions">
                <a href="/materials/rentals/create" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Nouvelle location
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $rental_stats['total_rentals'] ?></div>
                <div class="stat-label">Total locations</div>
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
                <div class="stat-number"><?= $rental_stats['active_rentals'] ?></div>
                <div class="stat-label">Locations actives</div>
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
                <div class="stat-number"><?= $rental_stats['maintenance_count'] ?></div>
                <div class="stat-label">En maintenance</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M12 1v6m0 6v6"></path>
                    <path d="M21 12h-6m-6 0H3"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= $rental_stats['returned_count'] ?></div>
                <div class="stat-label">Retournés</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"></polyline>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filters-card">
        <div class="filters-content">
            <div class="filter-group">
                <label for="filter-status">Statut</label>
                <select id="filter-status" onchange="filterRentals()">
                    <option value="">Tous les statuts</option>
                    <option value="loue">Loué</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="retourne">Retourné</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-material">Matériel</label>
                <select id="filter-material" onchange="filterRentals()">
                    <option value="">Tous les matériels</option>
                    <?php
                    $materials = array_unique(array_column($rentals, 'materiel_nom'));
                    foreach ($materials as $material):
                    ?>
                        <option value="<?= htmlspecialchars($material) ?>"><?= htmlspecialchars($material) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-client">Client</label>
                <input type="text" id="filter-client" placeholder="Nom du client..." onkeyup="filterRentals()">
            </div>
            <div class="filter-group">
                <label for="filter-subscription">Type abonnement</label>
                <select id="filter-subscription" onchange="filterRentals()">
                    <option value="">Tous types</option>
                    <option value="inclus">Inclus dans abonnement</option>
                    <option value="separe">Facturé séparément</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table des locations -->
    <div class="rentals-card">
        <div class="card-content">
            <?php if (empty($rentals)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="M7 15h0M12 15h0M17 15h0"></path>
                        </svg>
                    </div>
                    <h3>Aucune location trouvée</h3>
                    <p>Commencez par créer votre première location de matériel.</p>
                    <a href="/materials/rentals/create" class="btn btn-primary">Créer une location</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="rentals-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Matériel</th>
                                <th>N° série</th>
                                <th>Date location</th>
                                <th>Retour prévu</th>
                                <th>Durée</th>
                                <th>Type</th>
                                <th>Dépôt</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                                <tr data-status="<?= $rental['statut'] ?>" 
                                    data-material="<?= htmlspecialchars($rental['materiel_nom']) ?>"
                                    data-client="<?= strtolower($rental['raison_sociale']) ?>"
                                    data-subscription="<?= $rental['inclus_dans_abonnement'] ? 'inclus' : 'separe' ?>">
                                    
                                    <td>
                                        <div class="client-info">
                                            <div class="client-name">
                                                <a href="/clients/<?= $rental['client_id'] ?>" class="client-link">
                                                    <?= htmlspecialchars($rental['raison_sociale']) ?>
                                                </a>
                                            </div>
                                            <div class="client-email"><?= htmlspecialchars($rental['email_facturation']) ?></div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <div class="material-info">
                                            <div class="material-name"><?= htmlspecialchars($rental['materiel_nom']) ?></div>
                                            <div class="material-price"><?= number_format($rental['prix_mensuel'], 2) ?>€/mois</div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="serial-number">
                                            <?= htmlspecialchars($rental['numero_serie'] ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="date"><?= date('d/m/Y', strtotime($rental['date_location'])) ?></span>
                                    </td>
                                    
                                    <td>
                                        <?php if ($rental['date_retour_prevue']): ?>
                                            <span class="date"><?= date('d/m/Y', strtotime($rental['date_retour_prevue'])) ?></span>
                                            <?php
                                            $today = new DateTime();
                                            $returnDate = new DateTime($rental['date_retour_prevue']);
                                            $diff = $today->diff($returnDate);
                                            if ($today > $returnDate && $rental['statut'] === 'loue'):
                                            ?>
                                                <div class="overdue">En retard de <?= $diff->days ?> jour<?= $diff->days > 1 ? 's' : '' ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non défini</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php
                                        $start = new DateTime($rental['date_location']);
                                        if ($rental['date_retour_effective']) {
                                            $end = new DateTime($rental['date_retour_effective']);
                                        } else {
                                            $end = new DateTime();
                                        }
                                        $duration = $start->diff($end)->days;
                                        ?>
                                        <span class="duration">
                                            <?= $duration ?> jour<?= $duration > 1 ? 's' : '' ?>
                                            <?php if (!$rental['date_retour_effective'] && $rental['statut'] === 'loue'): ?>
                                                <span class="ongoing">(en cours)</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="rental-type rental-type-<?= $rental['inclus_dans_abonnement'] ? 'included' : 'separate' ?>">
                                            <?= $rental['inclus_dans_abonnement'] ? 'Inclus' : 'Séparé' ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="deposit-info">
                                            <div class="deposit-amount"><?= number_format($rental['depot_verse'], 2) ?>€</div>
                                            <?php if ($rental['depot_rembourse']): ?>
                                                <div class="deposit-status refunded">Remboursé</div>
                                            <?php elseif ($rental['statut'] === 'retourne'): ?>
                                                <div class="deposit-status pending">À rembourser</div>
                                            <?php else: ?>
                                                <div class="deposit-status held">Retenu</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="status-badge status-rental-<?= $rental['statut'] ?>">
                                            <?php
                                            $statusLabels = [
                                                'loue' => 'Loué',
                                                'retourne' => 'Retourné',
                                                'maintenance' => 'Maintenance'
                                            ];
                                            echo $statusLabels[$rental['statut']] ?? $rental['statut'];
                                            ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="actions-menu">
                                            <?php if ($rental['statut'] === 'loue'): ?>
                                                <form method="POST" action="/materials/rentals/<?= $rental['id'] ?>/update" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                                                    <input type="hidden" name="action" value="return">
                                                    <button type="submit" class="action-btn action-return" 
                                                            onclick="return confirm('Confirmer le retour de ce matériel ?')"
                                                            title="Marquer comme retourné">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <polyline points="20,6 9,17 4,12"></polyline>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <form method="POST" action="/materials/rentals/<?= $rental['id'] ?>/update" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                                                    <input type="hidden" name="action" value="maintenance">
                                                    <button type="submit" class="action-btn action-maintenance" 
                                                            onclick="return confirm('Mettre ce matériel en maintenance ?')"
                                                            title="Mettre en maintenance">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <circle cx="12" cy="12" r="3"></circle>
                                                            <path d="M12 1v6m0 6v6"></path>
                                                            <path d="M21 12h-6m-6 0H3"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php elseif ($rental['statut'] === 'maintenance'): ?>
                                                <form method="POST" action="/materials/rentals/<?= $rental['id'] ?>/update" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                                                    <input type="hidden" name="action" value="reactivate">
                                                    <button type="submit" class="action-btn action-reactivate" 
                                                            onclick="return confirm('Réactiver cette location ?')"
                                                            title="Réactiver la location">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                                                            <path d="M21 3v5h-5"></path>
                                                            <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                                                            <path d="M3 21v-5h5"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <a href="/clients/<?= $rental['client_id'] ?>" class="action-btn" title="Voir le client">
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
</div>

<style>
.materials-rentals {
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

.rentals-card {
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

.material-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.material-name {
    font-weight: 500;
    color: var(--text-primary);
}

.material-price {
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.serial-number {
    font-family: monospace;
    font-size: 0.875rem;
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.date {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.overdue {
    font-size: 0.75rem;
    color: var(--error-color);
    font-weight: 500;
    margin-top: 0.25rem;
}

.duration {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.ongoing {
    font-size: 0.75rem;
    color: var(--primary-color);
    font-weight: 500;
}

.rental-type {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.rental-type-included {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.rental-type-separate {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.deposit-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.deposit-amount {
    font-weight: 600;
    color: var(--text-primary);
}

.deposit-status {
    font-size: 0.75rem;
    font-weight: 500;
}

.deposit-status.refunded {
    color: var(--success-color);
}

.deposit-status.pending {
    color: var(--warning-color);
}

.deposit-status.held {
    color: var(--text-secondary);
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-rental-loue {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.status-rental-retourne {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-rental-maintenance {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.actions-menu {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    text-decoration: none;
    border: none;
    background: none;
    cursor: pointer;
    transition: var(--transition);
}

.action-btn:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.action-return:hover {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.action-maintenance:hover {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.action-reactivate:hover {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.text-muted {
    color: var(--text-muted);
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
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .actions-menu {
        justify-content: center;
    }
}
</style>

<script>
function filterRentals() {
    const statusFilter = document.getElementById('filter-status').value;
    const materialFilter = document.getElementById('filter-material').value;
    const clientFilter = document.getElementById('filter-client').value.toLowerCase();
    const subscriptionFilter = document.getElementById('filter-subscription').value;
    
    const rows = document.querySelectorAll('#rentals-table tbody tr');
    
    rows.forEach(row => {
        const status = row.dataset.status;
        const material = row.dataset.material;
        const client = row.dataset.client;
        const subscription = row.dataset.subscription;
        
        let show = true;
        
        if (statusFilter && status !== statusFilter) {
            show = false;
        }
        
        if (materialFilter && material !== materialFilter) {
            show = false;
        }
        
        if (clientFilter && !client.includes(clientFilter)) {
            show = false;
        }
        
        if (subscriptionFilter && subscription !== subscriptionFilter) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
    
    // Compter les résultats visibles
    const visibleRows = document.querySelectorAll('#rentals-table tbody tr:not([style*="none"])');
    console.log(`${visibleRows.length} location(s) affichée(s)`);
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus sur le premier filtre si pas de données
    const table = document.getElementById('rentals-table');
    if (table && table.tbody.children.length === 0) {
        document.getElementById('filter-status').focus();
    }
});
</script>

<?php } ?>