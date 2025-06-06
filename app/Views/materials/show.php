<?php 
$pageTitle = $pageTitle ?? 'Détails du matériel';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $material, $usage_stats, $plans, $rentals, $rental_history;
    
    // Valeurs par défaut
    $usage_stats = $usage_stats ?? [];
    $plans = $plans ?? [];
    $rentals = $rentals ?? [];
    $rental_history = $rental_history ?? [];
?>

<div class="material-show">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/materials" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <div class="page-title">
                    <h1><?= htmlspecialchars($material['nom']) ?></h1>
                    <span class="status-badge status-<?= $material['actif'] ? 'active' : 'inactive' ?>">
                        <?= $material['actif'] ? 'Actif' : 'Inactif' ?>
                    </span>
                </div>
            </div>
            <div class="page-actions">
                <a href="/materials/<?= $material['id'] ?>/edit" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Modifier
                </a>
                <a href="/materials/<?= $material['id'] ?>/toggle" 
                   class="btn btn-outline"
                   onclick="return confirm('Êtes-vous sûr de vouloir <?= $material['actif'] ? 'désactiver' : 'activer' ?> ce matériel ?')">
                    <?= $material['actif'] ? 'Désactiver' : 'Activer' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="material-overview">
        <div class="overview-card">
            <h3>Informations générales</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Prix mensuel</span>
                    <span class="info-value price"><?= number_format($material['prix_mensuel'], 2) ?>€</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Dépôt de garantie</span>
                    <span class="info-value price"><?= number_format($material['depot_garantie'], 2) ?>€</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Prix annuel</span>
                    <span class="info-value price"><?= number_format($material['prix_mensuel'] * 12, 2) ?>€</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date de création</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($material['date_creation'])) ?></span>
                </div>
            </div>
            
            <?php if (!empty($material['description'])): ?>
            <div class="description">
                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($material['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistiques d'usage -->
        <div class="stats-card">
            <h3>Statistiques d'utilisation</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $usage_stats['plans_count'] ?? 0 ?></div>
                    <div class="stat-label">Formules actives</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $usage_stats['active_rentals'] ?? 0 ?></div>
                    <div class="stat-label">Locations actives</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $usage_stats['total_rentals'] ?? 0 ?></div>
                    <div class="stat-label">Total locations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($usage_stats['revenue_generated'] ?? 0, 0) ?>€</div>
                    <div class="stat-label">Revenus générés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formules utilisant ce matériel -->
    <div class="content-section">
        <div class="section-header">
            <h3>Formules d'abonnement utilisant ce matériel</h3>
            <span class="section-count"><?= count($plans) ?> formule<?= count($plans) > 1 ? 's' : '' ?></span>
        </div>
        
        <?php if (empty($plans)): ?>
            <div class="empty-state">
                <p>Aucune formule n'utilise actuellement ce matériel.</p>
                <a href="/subscription-plans/create" class="btn btn-primary">Créer une formule</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td>
                                    <a href="/subscription-plans/<?= $plan['id'] ?>" class="plan-link">
                                        <?= htmlspecialchars($plan['nom']) ?>
                                    </a>
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
                                <td><?= number_format($plan['prix_base'], 2) ?>€</td>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Locations actuelles -->
    <div class="content-section">
        <div class="section-header">
            <h3>Locations actuelles</h3>
            <span class="section-count"><?= count($rentals) ?> location<?= count($rentals) > 1 ? 's' : '' ?></span>
        </div>
        
        <?php if (empty($rentals)): ?>
            <div class="empty-state">
                <p>Aucune location active pour ce matériel.</p>
                <a href="/materials/rentals/create" class="btn btn-primary">Créer une location</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>N° série</th>
                            <th>Date location</th>
                            <th>Retour prévu</th>
                            <th>Statut abonnement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td>
                                    <a href="/clients/<?= $rental['client_id'] ?>" class="client-link">
                                        <?= htmlspecialchars($rental['raison_sociale']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($rental['numero_serie'] ?: 'N/A') ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($rental['date_location'])) ?></td>
                                <td>
                                    <?php if ($rental['date_retour_prevue']): ?>
                                        <?= date('d/m/Y', strtotime($rental['date_retour_prevue'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non défini</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $rental['abonnement_statut'] === 'actif' ? 'active' : 'inactive' ?>">
                                        <?= ucfirst($rental['abonnement_statut'] ?? 'Inconnu') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions-menu">
                                        <form method="POST" action="/materials/rentals/<?= $rental['id'] ?>/update" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="maintenance">
                                            <button type="submit" class="action-btn" 
                                                    onclick="return confirm('Mettre ce matériel en maintenance ?')"
                                                    title="Mettre en maintenance">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                    <path d="M12 1v6m0 6v6"></path>
                                                    <path d="M21 12h-6m-6 0H3"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Historique des locations -->
    <div class="content-section">
        <div class="section-header">
            <h3>Historique des locations</h3>
            <span class="section-count">Dernières <?= count($rental_history) ?> locations</span>
        </div>
        
        <?php if (empty($rental_history)): ?>
            <div class="empty-state">
                <p>Aucun historique de location pour ce matériel.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Période</th>
                            <th>Durée</th>
                            <th>Statut</th>
                            <th>Dépôt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rental_history as $rental): ?>
                            <tr>
                                <td>
                                    <a href="/clients/<?= $rental['client_id'] ?>" class="client-link">
                                        <?= htmlspecialchars($rental['raison_sociale']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="period-info">
                                        <div class="period-start">Du <?= date('d/m/Y', strtotime($rental['date_location'])) ?></div>
                                        <?php if ($rental['date_retour_effective']): ?>
                                            <div class="period-end">Au <?= date('d/m/Y', strtotime($rental['date_retour_effective'])) ?></div>
                                        <?php elseif ($rental['statut'] === 'loue'): ?>
                                            <div class="period-ongoing">En cours</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($rental['date_retour_effective']): ?>
                                        <?php
                                        $start = new DateTime($rental['date_location']);
                                        $end = new DateTime($rental['date_retour_effective']);
                                        $duration = $start->diff($end)->days;
                                        ?>
                                        <span class="duration"><?= $duration ?> jour<?= $duration > 1 ? 's' : '' ?></span>
                                    <?php else: ?>
                                        <?php
                                        $start = new DateTime($rental['date_location']);
                                        $now = new DateTime();
                                        $duration = $start->diff($now)->days;
                                        ?>
                                        <span class="duration ongoing"><?= $duration ?> jour<?= $duration > 1 ? 's' : '' ?> (en cours)</span>
                                    <?php endif; ?>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.material-show {
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

.page-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-title h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
}

.btn-outline:hover {
    background: var(--bg-secondary);
}

.material-overview {
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
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.info-value {
    font-weight: 600;
    color: var(--text-primary);
}

.info-value.price {
    color: var(--primary-color);
    font-size: 1.1rem;
}

.description {
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.description h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.description p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.6;
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

.section-count {
    font-size: 0.875rem;
    color: var(--text-secondary);
    background: var(--bg-secondary);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
}

.empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-secondary);
}

.empty-state p {
    margin-bottom: 1rem;
}

.plan-link,
.client-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.plan-link:hover,
.client-link:hover {
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

.action-item,
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

.action-item:hover,
.action-btn:hover {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.period-info {
    font-size: 0.875rem;
}

.period-start {
    color: var(--text-primary);
}

.period-end {
    color: var(--text-secondary);
    font-size: 0.75rem;
}

.period-ongoing {
    color: var(--primary-color);
    font-weight: 500;
    font-size: 0.75rem;
}

.duration {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.duration.ongoing {
    color: var(--primary-color);
    font-weight: 500;
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

.text-muted {
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .material-show {
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
    }
    
    .material-overview {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .actions-menu {
        justify-content: center;
    }
}
</style>

<?php } ?>::generateCsrfToken() ?>">