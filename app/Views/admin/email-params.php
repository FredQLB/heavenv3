<?php 
$pageTitle = 'Paramètres Email';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    $mailConfig = \App\Helpers\Config::mail();
?>

<div class="email-settings">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <h1>Configuration Email</h1>
                <p>Gérez les paramètres d'envoi d'emails</p>
            </div>
            <div class="page-actions">
                <a href="/admin/test-email" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Tester la configuration
                </a>
            </div>
        </div>
    </div>

    <div class="settings-grid">
        <!-- Configuration SMTP -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Configuration SMTP</h3>
                <span class="status-badge status-<?= !empty($mailConfig['host']) ? 'active' : 'inactive' ?>">
                    <?= !empty($mailConfig['host']) ? 'Configuré' : 'Non configuré' ?>
                </span>
            </div>
            <div class="card-content">
                <div class="config-grid">
                    <div class="config-item">
                        <span class="config-label">Serveur SMTP</span>
                        <span class="config-value"><?= htmlspecialchars($mailConfig['host'] ?? 'Non configuré') ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Port</span>
                        <span class="config-value"><?= htmlspecialchars($mailConfig['port'] ?? 'Non configuré') ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Chiffrement</span>
                        <span class="config-value"><?= htmlspecialchars(strtoupper($mailConfig['encryption'] ?? 'Aucun')) ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Utilisateur</span>
                        <span class="config-value"><?= htmlspecialchars($mailConfig['username'] ?? 'Non configuré') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration expéditeur -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Expéditeur par défaut</h3>
            </div>
            <div class="card-content">
                <div class="config-grid">
                    <div class="config-item">
                        <span class="config-label">Email</span>
                        <span class="config-value"><?= htmlspecialchars(\App\Helpers\Config::get('mail.from.address', 'Non configuré')) ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Nom</span>
                        <span class="config-value"><?= htmlspecialchars(\App\Helpers\Config::get('mail.from.name', 'Non configuré')) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates disponibles -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Templates d'emails</h3>
            </div>
            <div class="card-content">
                <div class="templates-list">
                    <?php 
                    $templates = \App\Helpers\Config::get('mail.templates', []);
                    foreach ($templates as $key => $template): 
                    ?>
                        <div class="template-item">
                            <div class="template-info">
                                <div class="template-name"><?= ucfirst(str_replace('_', ' ', $key)) ?></div>
                                <div class="template-subject"><?= htmlspecialchars($template['subject']) ?></div>
                            </div>
                            <div class="template-actions">
                                <span class="template-status">✓ Disponible</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Actions</h3>
            </div>
            <div class="card-content">
                <div class="actions-list">
                    <form method="POST" action="/admin/send-expiration-notifications" style="display: inline;">
                        <button type="submit" class="action-btn" onclick="return confirm('Envoyer les notifications d\'expiration ?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            Envoyer notifications d'expiration
                        </button>
                    </form>

                    <form method="POST" action="/admin/send-invoice-notifications" style="display: inline;">
                        <button type="submit" class="action-btn" onclick="return confirm('Envoyer les notifications de factures ?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                            </svg>
                            Envoyer notifications de factures
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistiques d'envoi -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Statistiques d'envoi</h3>
                <span class="period-badge">Dernières 24h</span>
            </div>
            <div class="card-content">
                <?php
                // Récupérer les stats depuis les logs (simulation)
                $emailStats = [
                    'welcome_emails' => 5,
                    'credential_emails' => 3,
                    'expiration_notifications' => 2,
                    'invoice_notifications' => 8,
                    'test_emails' => 1
                ];
                $totalEmails = array_sum($emailStats);
                ?>
                
                <div class="stats-summary">
                    <div class="stat-number"><?= $totalEmails ?></div>
                    <div class="stat-label">Emails envoyés</div>
                </div>
                
                <div class="stats-breakdown">
                    <?php foreach ($emailStats as $type => $count): ?>
                        <div class="stat-item">
                            <span class="stat-type"><?= ucfirst(str_replace('_', ' ', $type)) ?></span>
                            <span class="stat-count"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Instructions de configuration -->
        <div class="settings-card">
            <div class="card-header">
                <h3>Instructions de configuration</h3>
            </div>
            <div class="card-content">
                <div class="instructions">
                    <h4>Pour configurer Gmail :</h4>
                    <ol>
                        <li>Activez l'authentification à deux facteurs sur votre compte Gmail</li>
                        <li>Générez un mot de passe d'application spécifique</li>
                        <li>Modifiez le fichier <code>app/Config/mail.php</code></li>
                        <li>Remplacez <code>votre-mot-de-passe-application</code> par le mot de passe généré</li>
                        <li>Testez la configuration avec le bouton ci-dessus</li>
                    </ol>
                    
                    <h4>Configuration requise :</h4>
                    <ul>
                        <li><strong>Host:</strong> smtp.gmail.com</li>
                        <li><strong>Port:</strong> 587</li>
                        <li><strong>Encryption:</strong> TLS</li>
                        <li><strong>Username:</strong> cover.ar.dev@gmail.com</li>
                        <li><strong>Password:</strong> Mot de passe d'application Gmail</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.email-settings {
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

.page-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.page-header p {
    margin: 0;
    color: var(--text-secondary);
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.settings-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.card-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.status-badge, .period-badge {
    padding: 0.25rem 0.75rem;
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

.period-badge {
    background: var(--bg-tertiary);
    color: var(--text-secondary);
}

.card-content {
    padding: 1.5rem;
}

.config-grid {
    display: grid;
    gap: 1rem;
}

.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.config-item:last-child {
    border-bottom: none;
}

.config-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.config-value {
    font-family: monospace;
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.templates-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.template-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.template-name {
    font-weight: 500;
    color: var(--text-primary);
}

.template-subject {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.template-status {
    color: var(--success-color);
    font-size: 0.875rem;
    font-weight: 500;
}

.actions-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    color: var(--text-primary);
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.875rem;
}

.action-btn:hover {
    background: var(--bg-tertiary);
    border-color: var(--primary-color);
}

.stats-summary {
    text-align: center;
    margin-bottom: 1.5rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.stats-breakdown {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.stat-type {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.stat-count {
    font-weight: 600;
    color: var(--text-primary);
}

.instructions {
    color: var(--text-secondary);
    line-height: 1.6;
}

.instructions h4 {
    color: var(--text-primary);
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
}

.instructions h4:first-child {
    margin-top: 0;
}

.instructions code {
    background: var(--bg-secondary);
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
    font-size: 0.875rem;
}

.instructions ol, .instructions ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
}

.instructions li {
    margin: 0.25rem 0;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .config-item {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
}
</style>

<?php } ?>