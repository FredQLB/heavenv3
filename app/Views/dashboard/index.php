<?php 
$pageTitle = $pageTitle ?? 'Tableau de bord';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $stats, $recentActivity, $recentClients, $expiringSubscriptions;
?>

<div class="dashboard">
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_clients'] ?? 0) ?></div>
                <div class="stat-label">Clients actifs</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['total_users'] ?? 0) ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['active_subscriptions'] ?? 0) ?></div>
                <div class="stat-label">Abonnements actifs</div>
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
                <div class="stat-number"><?= number_format($stats['monthly_revenue'] ?? 0, 2) ?>€</div>
                <div class="stat-label">Revenus mensuels</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-number"><?= number_format($stats['rented_equipment'] ?? 0) ?></div>
                <div class="stat-label">Matériel loué</div>
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
                <div class="stat-number"><?= number_format($stats['new_clients_month'] ?? 0) ?></div>
                <div class="stat-label">Nouveaux clients ce mois</div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="dashboard-grid">
        <!-- Recent Activity -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Activité récente</h3>
            </div>
            <div class="card-content">
                <?php if (empty($recentActivity ?? [])): ?>
                    <p class="text-muted">Aucune activité récente</p>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach (($recentActivity ?? []) as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon activity-<?= $activity['type'] ?>">
                                    <?php if ($activity['icon'] === 'user-plus'): ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <line x1="19" y1="8" x2="19" y2="14"></line>
                                            <line x1="22" y1="11" x2="16" y2="11"></line>
                                        </svg>
                                    <?php else: ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                            <line x1="8" y1="21" x2="16" y2="21"></line>
                                            <line x1="12" y1="17" x2="12" y2="21"></line>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?= htmlspecialchars($activity['title']) ?></div>
                                    <div class="activity-date"><?= date('d/m/Y H:i', strtotime($activity['date'])) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Clients -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Clients récents</h3>
                <a href="/clients" class="btn btn-sm">Voir tout</a>
            </div>
            <div class="card-content">
                <?php if (empty($recentClients ?? [])): ?>
                    <p class="text-muted">Aucun client récent</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Ville</th>
                                    <th>Utilisateurs</th>
                                    <th>Date création</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($recentClients ?? []) as $client): ?>
                                    <tr>
                                        <td>
                                            <div class="client-info">
                                                <div class="client-name"><?= htmlspecialchars($client['raison_sociale'] ?? '') ?></div>
                                                <div class="client-email"><?= htmlspecialchars($client['email_facturation'] ?? '') ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($client['ville'] ?? '') ?>, <?= htmlspecialchars($client['pays'] ?? '') ?></td>
                                        <td>
                                            <span class="badge"><?= ($client['nb_users'] ?? 0) ?> utilisateur<?= ($client['nb_users'] ?? 0) > 1 ? 's' : '' ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($client['date_creation'] ?? 'now')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expiring Subscriptions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3>Abonnements à renouveler</h3>
                <a href="/subscriptions" class="btn btn-sm">Voir tout</a>
            </div>
            <div class="card-content">
                <?php if (empty($expiringSubscriptions ?? [])): ?>
                    <p class="text-muted">Aucun abonnement à renouveler dans les 30 prochains jours</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Formule</th>
                                    <th>Expiration</th>
                                    <th>Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($expiringSubscriptions ?? []) as $sub): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sub['raison_sociale'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sub['formule_name'] ?? '') ?></td>
                                        <td>
                                            <?php 
                                            $dateFinSub = $sub['date_fin'] ?? null;
                                            if ($dateFinSub) {
                                                $daysLeft = ceil((strtotime($dateFinSub) - time()) / (60 * 60 * 24));
                                                $urgentClass = $daysLeft <= 7 ? 'urgent' : ($daysLeft <= 15 ? 'warning' : '');
                                            } else {
                                                $daysLeft = 0;
                                                $urgentClass = '';
                                            }
                                            ?>
                                            <span class="badge <?= $urgentClass ?>">
                                                <?= $dateFinSub ? date('d/m/Y', strtotime($dateFinSub)) : 'N/A' ?>
                                                <?php if ($dateFinSub): ?>
                                                    (<?= $daysLeft ?> jour<?= $daysLeft > 1 ? 's' : '' ?>)
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($sub['prix_total_mensuel'] ?? 0, 2) ?>€/mois</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php } ?>