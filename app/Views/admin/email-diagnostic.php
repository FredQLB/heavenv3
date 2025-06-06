<?php 
$pageTitle = 'Diagnostic Email';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    // Exécuter le diagnostic
    $diagnosis = \App\Services\EmailService::diagnoseEmailConfiguration();
    
    // Test de configuration si demandé
    $testResult = null;
    if (isset($_GET['test'])) {
        $testResult = \App\Services\EmailService::testEmailConfiguration($_GET['email'] ?? null);
    }
?>

<div class="email-diagnostic">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <h1>Diagnostic Email</h1>
                <p>Vérification complète de la configuration email</p>
            </div>
            <div class="page-actions">
                <a href="?test=1" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    Tester maintenant
                </a>
            </div>
        </div>
    </div>

    <?php if ($testResult): ?>
    <div class="test-result">
        <div class="alert alert-<?= $testResult['success'] ? 'success' : 'error' ?>">
            <div class="alert-content">
                <div class="alert-icon">
                    <?php if ($testResult['success']): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="alert-message">
                    <?php if ($testResult['success']): ?>
                        <strong>Test réussi !</strong> <?= htmlspecialchars($testResult['message']) ?>
                    <?php else: ?>
                        <strong>Test échoué :</strong> <?= htmlspecialchars($testResult['error']) ?>
                        <?php if (!empty($testResult['details'])): ?>
                            <div class="error-details">
                                <p><strong>Détails de la configuration :</strong></p>
                                <ul>
                                    <li>Host: <?= htmlspecialchars($testResult['details']['host']) ?></li>
                                    <li>Port: <?= htmlspecialchars($testResult['details']['port']) ?></li>
                                    <li>Username: <?= htmlspecialchars($testResult['details']['username']) ?></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="diagnostic-grid">
        <!-- Statut général -->
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Statut général</h3>
                <span class="status-badge status-<?= empty($diagnosis['issues']) ? 'success' : 'error' ?>">
                    <?= empty($diagnosis['issues']) ? 'OK' : count($diagnosis['issues']) . ' problème(s)' ?>
                </span>
            </div>
            <div class="card-content">
                <div class="status-grid">
                    <div class="status-item">
                        <span class="status-label">PHPMailer</span>
                        <span class="status-value <?= $diagnosis['phpmailer_available'] ? 'success' : 'error' ?>">
                            <?= $diagnosis['phpmailer_available'] ? '✓ Installé' : '✗ Manquant' ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Configuration</span>
                        <span class="status-value <?= $diagnosis['config_loaded'] ? 'success' : 'error' ?>">
                            <?= $diagnosis['config_loaded'] ? '✓ Chargée' : '✗ Manquante' ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Extensions PHP</span>
                        <span class="status-value <?= extension_loaded('openssl') && function_exists('curl_init') ? 'success' : 'warning' ?>">
                            <?= extension_loaded('openssl') && function_exists('curl_init') ? '✓ OK' : '⚠ Incomplètes' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration SMTP -->
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Configuration SMTP</h3>
            </div>
            <div class="card-content">
                <?php if (!empty($diagnosis['smtp_settings'])): ?>
                    <div class="config-grid">
                        <?php foreach ($diagnosis['smtp_settings'] as $key => $value): ?>
                            <div class="config-item">
                                <span class="config-label"><?= ucfirst($key) ?></span>
                                <span class="config-value"><?= htmlspecialchars($value) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-config">Configuration SMTP non disponible</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Problèmes détectés -->
        <?php if (!empty($diagnosis['issues'])): ?>
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Problèmes détectés</h3>
                <span class="count-badge"><?= count($diagnosis['issues']) ?></span>
            </div>
            <div class="card-content">
                <ul class="issues-list">
                    <?php foreach ($diagnosis['issues'] as $issue): ?>
                        <li class="issue-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            <?= htmlspecialchars($issue) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommandations -->
        <?php if (!empty($diagnosis['recommendations'])): ?>
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Recommandations</h3>
                <span class="count-badge"><?= count($diagnosis['recommendations']) ?></span>
            </div>
            <div class="card-content">
                <ol class="recommendations-list">
                    <?php foreach ($diagnosis['recommendations'] as $recommendation): ?>
                        <li class="recommendation-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                            <?= htmlspecialchars($recommendation) ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <!-- Guide de configuration Gmail -->
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Guide Gmail</h3>
            </div>
            <div class="card-content">
                <div class="guide-content">
                    <h4>Configuration Gmail en 5 étapes :</h4>
                    <ol class="guide-steps">
                        <li>
                            <strong>Activer l'authentification à 2 facteurs</strong>
                            <p>Allez dans votre compte Google → Sécurité → Validation en 2 étapes</p>
                        </li>
                        <li>
                            <strong>Générer un mot de passe d'application</strong>
                            <p>Dans Sécurité → Validation en 2 étapes → Mots de passe des applications</p>
                            <p>Choisir "Autre" et saisir "Cover AR"</p>
                        </li>
                        <li>
                            <strong>Copier le mot de passe généré</strong>
                            <p>Il ressemble à : <code>abcd efgh ijkl mnop</code></p>
                        </li>
                        <li>
                            <strong>Modifier le fichier de configuration</strong>
                            <p>Fichier : <code>app/Config/mail.php</code></p>
                            <p>Remplacer : <code>'password' => 'votre-mot-de-passe-application'</code></p>
                        </li>
                        <li>
                            <strong>Tester la configuration</strong>
                            <p>Utiliser le bouton "Tester maintenant" ci-dessus</p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Test personnalisé -->
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Test personnalisé</h3>
            </div>
            <div class="card-content">
                <form method="GET" class="test-form">
                    <div class="form-group">
                        <label for="email">Email de test :</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($_GET['email'] ?? 'cover.ar.dev@gmail.com') ?>"
                               placeholder="Saisissez un email">
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="test" value="1" class="btn btn-primary">
                            Envoyer email de test
                        </button>
                        <a href="/clients/1/test-email?type=welcome" class="btn btn-secondary">
                            Test avec client
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs récents -->
        <div class="diagnostic-card">
            <div class="card-header">
                <h3>Logs récents</h3>
            </div>
            <div class="card-content">
                <?php 
                // Simulation des logs récents
                $recentLogs = [
                    ['time' => '2024-12-19 14:30:25', 'level' => 'INFO', 'message' => 'Service Email initialisé avec succès'],
                    ['time' => '2024-12-19 14:30:30', 'level' => 'ERROR', 'message' => 'SMTP connect() failed'],
                    ['time' => '2024-12-19 14:31:15', 'level' => 'INFO', 'message' => 'Test email envoyé avec succès']
                ];
                ?>
                <div class="logs-container">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="log-entry log-<?= strtolower($log['level']) ?>">
                            <span class="log-time"><?= $log['time'] ?></span>
                            <span class="log-level"><?= $log['level'] ?></span>
                            <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.email-diagnostic {
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

.test-result {
    margin-bottom: 1.5rem;
}

.alert {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: var(--success-color);
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.2);
    color: var(--error-color);
}

.alert-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert-icon {
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.error-details {
    margin-top: 1rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.error-details ul {
    margin: 0.5rem 0 0 1rem;
}

.diagnostic-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.diagnostic-card {
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

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-success {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.status-error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc2626;
}

.count-badge {
    background: var(--bg-tertiary);
    color: var(--text-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.card-content {
    padding: 1.5rem;
}

.status-grid {
    display: grid;
    gap: 1rem;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.status-value {
    font-weight: 500;
}

.status-value.success {
    color: var(--success-color);
}

.status-value.error {
    color: var(--error-color);
}

.status-value.warning {
    color: var(--warning-color);
}

.config-grid {
    display: grid;
    gap: 0.75rem;
}

.config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
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

.no-config {
    color: var(--text-muted);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.issues-list, .recommendations-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.issue-item, .recommendation-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.issue-item:last-child, .recommendation-item:last-child {
    border-bottom: none;
}

.issue-item svg {
    color: var(--error-color);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.recommendation-item svg {
    color: var(--info-color);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.guide-content h4 {
    margin: 0 0 1rem 0;
    color: var(--text-primary);
}

.guide-steps {
    margin: 0;
    padding-left: 1.5rem;
}

.guide-steps li {
    margin-bottom: 1.5rem;
}

.guide-steps li:last-child {
    margin-bottom: 0;
}

.guide-steps strong {
    color: var(--text-primary);
    display: block;
    margin-bottom: 0.5rem;
}

.guide-steps p {
    margin: 0.25rem 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.guide-steps code {
    background: var(--bg-secondary);
    padding: 0.125rem 0.25rem;
    border-radius: 3px;
    font-size: 0.875rem;
}

.test-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text-primary);
}

.form-group input {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.form-actions {
    display: flex;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: 1px solid transparent;
    border-radius: var(--border-radius);
    text-decoration: none;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--accent-color);
}

.btn-secondary {
    background: transparent;
    color: var(--text-primary);
    border-color: var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-secondary);
}

.logs-container {
    max-height: 300px;
    overflow-y: auto;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.log-entry {
    display: grid;
    grid-template-columns: auto auto 1fr;
    gap: 1rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
    font-family: monospace;
    font-size: 0.875rem;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-time {
    color: var(--text-muted);
}

.log-level {
    font-weight: 600;
    width: 60px;
}

.log-info .log-level {
    color: var(--info-color);
}

.log-error .log-level {
    color: var(--error-color);
}

.log-warning .log-level {
    color: var(--warning-color);
}

.log-message {
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .diagnostic-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .log-entry {
        grid-template-columns: 1fr;
        gap: 0.25rem;
    }
}
</style>

<?php } ?>