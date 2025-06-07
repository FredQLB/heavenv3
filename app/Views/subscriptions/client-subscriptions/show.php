<?php 
$pageTitle = $pageTitle ?? 'Détail abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $subscription, $materials, $invoices, $stats;
    
    if (!$subscription) {
        echo '<div class="error-message">Abonnement non trouvé</div>';
        return;
    }
    
    $materials = $materials ?? [];
    $invoices = $invoices ?? [];
    $stats = $stats ?? [];
?>

<div class="subscription-show">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/subscriptions" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <div class="page-title">
                    <h1><?= htmlspecialchars($subscription['raison_sociale']) ?></h1>
                    <div class="subtitle">Abonnement #<?= $subscription['id'] ?></div>
                </div>
            </div>
            <div class="page-actions">
                <a href="/subscriptions/<?= $subscription['id'] ?>/edit" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Modifier
                </a>
                
                <?php if ($subscription['statut'] === 'actif'): ?>
                    <a href="/subscriptions/<?= $subscription['id'] ?>/suspend" 
                       class="btn btn-warning"
                       onclick="return confirm('Êtes-vous sûr de vouloir suspendre cet abonnement ?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                        Suspendre
                    </a>
                <?php elseif ($subscription['statut'] === 'suspendu'): ?>
                    <a href="/subscriptions/<?= $subscription['id'] ?>/resume" 
                       class="btn btn-success"
                       onclick="return confirm('Êtes-vous sûr de vouloir réactiver cet abonnement ?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5,3 19,12 5,21"></polygon>
                        </svg>
                        Réactiver
                    </a>
                <?php endif; ?>
                
                <?php if (in_array($subscription['statut'], ['actif', 'suspendu'])): ?>
                    <a href="/subscriptions/<?= $subscription['id'] ?>/cancel" 
                       class="btn btn-danger"
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
    </div>

    <!-- Statut et alertes -->
    <div class="status-section">
        <div class="status-card">
            <div class="status-main">
                <span class="status-badge status-<?= $subscription['statut'] ?>">
                    <?= htmlspecialchars($subscription['statut_display']) ?>
                </span>
                <?php if ($subscription['jours_restants'] !== null): ?>
                    <div class="expiration-info">
                        <?php if ($subscription['jours_restants'] > 0): ?>
                            <span class="expiration-days <?= $subscription['jours_restants'] <= 7 ? 'urgent' : ($subscription['jours_restants'] <= 30 ? 'warning' : '') ?>">
                                Expire dans <?= $subscription['jours_restants'] ?> jour<?= $subscription['jours_restants'] > 1 ? 's' : '' ?>
                            </span>
                        <?php elseif ($subscription['jours_restants'] === 0): ?>
                            <span class="expiration-days urgent">Expire aujourd'hui</span>
                        <?php else: ?>
                            <span class="expiration-days expired">Expiré</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($subscription['stripe_subscription_id']): ?>
                <div class="stripe-info">
                    <span class="stripe-badge">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="22" height="18" rx="2" ry="2"></rect>
                            <line x1="1" y1="9" x2="23" y2="9"></line>
                        </svg>
                        Stripe: <?= htmlspecialchars($subscription['stripe_subscription_id']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="main-grid">
        <!-- Informations de l'abonnement -->
        <div class="info-card">
            <div class="card-header">
                <h3>Informations de l'abonnement</h3>
            </div>
            <div class="card-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Formule</label>
                        <value><?= htmlspecialchars($subscription['formule_nom']) ?></value>
                    </div>
                    
                    <div class="info-item">
                        <label>Type</label>
                        <value>
                            <span class="type-badge type-<?= $subscription['type_abonnement'] ?>">
                                <?php
                                $typeLabels = [
                                    'application' => 'Application seule',
                                    'application_materiel' => 'Application + Matériel',
                                    'materiel_seul' => 'Matériel seul'
                                ];
                                echo $typeLabels[$subscription['type_abonnement']] ?? $subscription['type_abonnement'];
                                ?>
                            </span>
                        </value>
                    </div>
                    
                    <div class="info-item">
                        <label>Durée</label>
                        <value><?= $subscription['duree'] === 'mensuelle' ? 'Mensuelle' : 'Annuelle' ?></value>
                    </div>
                    
                    <div class="info-item">
                        <label>Date de début</label>
                        <value><?= date('d/m/Y', strtotime($subscription['date_debut'])) ?></value>
                    </div>
                    
                    <?php if ($subscription['date_fin']): ?>
                    <div class="info-item">
                        <label>Date de fin</label>
                        <value><?= date('d/m/Y', strtotime($subscription['date_fin'])) ?></value>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <label>Prix mensuel</label>
                        <value class="price"><?= number_format($subscription['prix_total_mensuel'], 2) ?>€</value>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations client -->
        <div class="info-card">
            <div class="card-header">
                <h3>Client</h3>
                <a href="/clients/<?= $subscription['client_id'] ?>" class="btn btn-sm">Voir profil</a>
            </div>
            <div class="card-content">
                <div class="client-info">
                    <div class="client-name">
                        <a href="/clients/<?= $subscription['client_id'] ?>"><?= htmlspecialchars($subscription['raison_sociale']) ?></a>
                    </div>
                    <div class="client-email"><?= htmlspecialchars($subscription['email_facturation']) ?></div>
                    <div class="client-address">
                        <?= htmlspecialchars($subscription['adresse']) ?><br>
                        <?= htmlspecialchars($subscription['ville']) ?>, <?= htmlspecialchars($subscription['pays']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques utilisateurs -->
        <div class="info-card">
            <div class="card-header">
                <h3>Utilisateurs</h3>
            </div>
            <div class="card-content">
                <div class="users-stats">
                    <div class="stat-row">
                        <span class="stat-label">Utilisateurs inclus :</span>
                        <span class="stat-value"><?= $stats['utilisateurs_inclus'] ?? 0 ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Utilisateurs actuels :</span>
                        <span class="stat-value"><?= $stats['utilisateurs_actuels'] ?? 0 ?></span>
                    </div>
                    <?php if (($stats['utilisateurs_supplementaires'] ?? 0) > 0): ?>
                    <div class="stat-row">
                        <span class="stat-label">Utilisateurs supplémentaires :</span>
                        <span class="stat-value extra"><?= $stats['utilisateurs_supplementaires'] ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Coût supplémentaire :</span>
                        <span class="stat-value price">+<?= number_format($stats['prix_utilisateurs_supp'] ?? 0, 2) ?>€</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Matériel loué -->
        <?php if (!empty($materials)): ?>
        <div class="info-card full-width">
            <div class="card-header">
                <h3>Matériel loué</h3>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Matériel</th>
                                <th>Numéro de série</th>
                                <th>Date location</th>
                                <th>Date retour prévue</th>
                                <th>Dépôt versé</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td>
                                        <div class="material-info">
                                            <div class="material-name"><?= htmlspecialchars($material['materiel_nom']) ?></div>
                                            <div class="material-description"><?= htmlspecialchars($material['description'] ?? '') ?></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($material['numero_serie'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y', strtotime($material['date_location'])) ?></td>
                                    <td><?= $material['date_retour_prevue'] ? date('d/m/Y', strtotime($material['date_retour_prevue'])) : '-' ?></td>
                                    <td><?= number_format($material['depot_verse'], 2) ?>€</td>
                                    <td>
                                        <span class="status-badge status-material-<?= $material['statut'] ?>">
                                            <?= ucfirst($material['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Factures -->
        <div class="info-card full-width">
            <div class="card-header">
                <h3>Factures</h3>
                <a href="/invoices?subscription=<?= $subscription['id'] ?>" class="btn btn-sm">Voir toutes</a>
            </div>
            <div class="card-content">
                <?php if (empty($invoices)): ?>
                    <p class="text-muted">Aucune facture pour cet abonnement</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Facture</th>
                                    <th>Date</th>
                                    <th>Échéance</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $invoice): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice['stripe_invoice_id']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($invoice['date_facture'])) ?></td>
                                        <td><?= $invoice['date_echeance'] ? date('d/m/Y', strtotime($invoice['date_echeance'])) : '-' ?></td>
                                        <td><?= number_format($invoice['montant'], 2) ?>€</td>
                                        <td>
                                            <span class="status-badge status-payment-<?= strtolower(str_replace(' ', '_', $invoice['statut_paiement'])) ?>">
                                                <?= htmlspecialchars($invoice['statut_paiement']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($invoice['lien_telechargement']): ?>
                                                <a href="<?= htmlspecialchars($invoice['lien_telechargement']) ?>" 
                                                   target="_blank" class="btn btn-sm">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7,10 12,15 17,10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    PDF
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="invoices-summary">
                        <div class="summary-item">
                            <span class="summary-label">Total facturé :</span>
                            <span class="summary-value"><?= number_format($stats['total_factures'] ?? 0, 2) ?>€</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Factures payées :</span>
                            <span class="summary-value"><?= $stats['factures_payees'] ?? 0 ?>/<?= count($invoices) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.subscription-show {
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

.page-title h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.subtitle {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}

.status-section {
    margin-bottom: 1.5rem;
}

.status-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.status-main {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
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

.expiration-days {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    font-weight: 500;
}

.expiration-days.urgent {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.expiration-days.warning {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.expiration-days.expired {
    background: rgba(156, 163, 175, 0.1);
    color: #6b7280;
}

.stripe-info {
    display: flex;
    align-items: center;
}

.stripe-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: var(--text-muted);
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.main-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.info-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.info-card.full-width {
    grid-column: 1 / -1;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.card-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.card-content {
    padding: 1.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.info-item value {
    font-weight: 500;
    color: var(--text-primary);
}

.info-item .price {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 1.1rem;
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

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.client-name a {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-primary);
    text-decoration: none;
}

.client-name a:hover {
    color: var(--primary-color);
}

.client-email {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.client-address {
    color: var(--text-muted);
    font-size: 0.875rem;
    line-height: 1.4;
}

.users-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.stat-row:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.stat-value {
    font-weight: 600;
    color: var(--text-primary);
}

.stat-value.extra {
    color: var(--warning-color);
}

.stat-value.price {
    color: var(--primary-color);
}

.material-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.material-name {
    font-weight: 500;
}

.material-description {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.status-material-loue {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-material-retourne {
    background: rgba(156, 163, 175, 0.1);
    color: #6b7280;
}

.status-material-maintenance {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.status-payment-payée {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-payment-en_attente {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.status-payment-en_retard {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.invoices-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.summary-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    text-align: center;
}

.summary-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.summary-value {
    font-weight: 600;
    color: var(--text-primary);
}

.text-muted {
    color: var(--text-muted);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .page-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .status-card {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .status-main {
        justify-content: center;
    }
    
    .main-grid {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .invoices-summary {
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}

/* Animation pour les changements de statut */
.status-badge {
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.05);
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Effet hover sur les cartes */
.info-card {
    transition: var(--transition);
}

.info-card:hover {
    box-shadow: var(--shadow-md);
}
</style>

<script>
// Actualisation automatique du statut (optionnel)
function refreshSubscriptionStatus() {
    const subscriptionId = <?= $subscription['id'] ?>;
    
    // Ici, vous pourriez ajouter un appel AJAX pour actualiser le statut
    // sans recharger la page entière
}

// Confirmation avant actions critiques
document.addEventListener('DOMContentLoaded', function() {
    const dangerousLinks = document.querySelectorAll('a[onclick*="confirm"]');
    
    dangerousLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Le onclick natif gère déjà la confirmation
            // Ici on peut ajouter des animations ou des logs
            console.log('Action critique demandée:', this.textContent.trim());
        });
    });
});

// Copie des identifiants Stripe au clic
document.addEventListener('DOMContentLoaded', function() {
    const stripeBadge = document.querySelector('.stripe-badge');
    if (stripeBadge) {
        stripeBadge.style.cursor = 'pointer';
        stripeBadge.title = 'Cliquer pour copier l\'ID Stripe';
        
        stripeBadge.addEventListener('click', function() {
            const stripeId = this.textContent.split(': ')[1];
            if (navigator.clipboard) {
                navigator.clipboard.writeText(stripeId).then(() => {
                    // Feedback visuel
                    const originalText = this.textContent;
                    this.textContent = 'Copié !';
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 1000);
                });
            }
        });
    }
});

// Auto-refresh pour les abonnements actifs (toutes les 5 minutes)
<?php if ($subscription['statut'] === 'actif'): ?>
setInterval(function() {
    // Ici vous pourriez ajouter un appel AJAX pour vérifier les mises à jour
    // Par exemple, nouvelles factures, changements de statut, etc.
}, 5 * 60 * 1000); // 5 minutes
<?php endif; ?>
</script>

<?php } ?>