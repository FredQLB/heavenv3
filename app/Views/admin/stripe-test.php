<?php 
$pageTitle = 'Test de configuration Stripe';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    // Vérifier la configuration Stripe
    try {
        $stripeStatus = \App\Services\StripeService::checkConfiguration();
    } catch (Exception $e) {
        $stripeStatus = ['error' => $e->getMessage()];
    }
?>

<div class="stripe-test">
    <div class="page-header">
        <div class="page-header-content">
            <h1>Test de configuration Stripe</h1>
            <p>Vérifiez que votre intégration Stripe fonctionne correctement.</p>
        </div>
    </div>

    <!-- Configuration Status -->
    <div class="config-card">
        <h3>État de la configuration</h3>
        
        <div class="config-checks">
            <div class="check-item <?= ($stripeStatus['secret_key_configured'] ?? false) ? 'success' : 'error' ?>">
                <div class="check-icon">
                    <?php if ($stripeStatus['secret_key_configured'] ?? false): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="check-content">
                    <div class="check-title">Clé secrète Stripe</div>
                    <div class="check-description">
                        <?= ($stripeStatus['secret_key_configured'] ?? false) ? 'Configurée' : 'Non configurée dans app/Config/stripe.php' ?>
                    </div>
                </div>
            </div>

            <div class="check-item <?= ($stripeStatus['publishable_key_configured'] ?? false) ? 'success' : 'error' ?>">
                <div class="check-icon">
                    <?php if ($stripeStatus['publishable_key_configured'] ?? false): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="check-content">
                    <div class="check-title">Clé publique Stripe</div>
                    <div class="check-description">
                        <?= ($stripeStatus['publishable_key_configured'] ?? false) ? 'Configurée' : 'Non configurée dans app/Config/stripe.php' ?>
                    </div>
                </div>
            </div>

            <div class="check-item <?= ($stripeStatus['api_connection'] ?? false) ? 'success' : 'error' ?>">
                <div class="check-icon">
                    <?php if ($stripeStatus['api_connection'] ?? false): ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="check-content">
                    <div class="check-title">Connexion API Stripe</div>
                    <div class="check-description">
                        <?php if ($stripeStatus['api_connection'] ?? false): ?>
                            Connexion réussie
                        <?php else: ?>
                            <?= $stripeStatus['api_error'] ?? 'Impossible de se connecter à Stripe' ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="check-item info">
                <div class="check-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 16v-4"></path>
                        <path d="M12 8h.01"></path>
                    </svg>
                </div>
                <div class="check-content">
                    <div class="check-title">Environnement</div>
                    <div class="check-description">
                        Mode <?= $stripeStatus['environment'] ?? 'non défini' ?>
                        <?php if (($stripeStatus['environment'] ?? '') === 'sandbox'): ?>
                            <span class="env-badge sandbox">Test</span>
                        <?php else: ?>
                            <span class="env-badge live">Production</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Instructions -->
    <?php if (!($stripeStatus['secret_key_configured'] ?? false) || !($stripeStatus['publishable_key_configured'] ?? false)): ?>
    <div class="config-help">
        <h3>Configuration Stripe</h3>
        <p>Pour configurer Stripe, modifiez le fichier <code>app/Config/stripe.php</code> :</p>
        
        <div class="code-example">
            <pre><code>'sandbox' => [
    'publishable_key' => 'pk_test_votre_clé_publique_ici',
    'secret_key' => 'sk_test_votre_clé_secrète_ici',
    'webhook_secret' => 'whsec_votre_secret_webhook_ici',
],

'live' => [
    'publishable_key' => 'pk_live_votre_clé_publique_ici',
    'secret_key' => 'sk_live_votre_clé_secrète_ici',
    'webhook_secret' => 'whsec_votre_secret_webhook_ici',
],</code></pre>
        </div>
        
        <div class="help-links">
            <p>Vous pouvez obtenir vos clés API depuis votre <a href="https://dashboard.stripe.com/apikeys" target="_blank">tableau de bord Stripe</a>.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Test Actions -->
    <?php if ($stripeStatus['api_connection'] ?? false): ?>
    <div class="test-actions">
        <h3>Actions de test</h3>
        
        <div class="test-buttons">
            <button onclick="testCreateProduct()" class="btn btn-primary">
                Tester la création d'un produit
            </button>
            <button onclick="syncStripeData()" class="btn btn-secondary">
                Synchroniser les données Stripe
            </button>
            <button onclick="viewStripeProducts()" class="btn btn-secondary">
                Voir les produits Stripe
            </button>
        </div>
        
        <div id="testResults" class="test-results" style="display: none;">
            <h4>Résultats du test</h4>
            <div id="testOutput"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Current Stripe Data -->
    <div class="stripe-data">
        <h3>Données Stripe existantes</h3>
        
        <?php 
        try {
            $plans = \App\Helpers\Database::fetchAll("
                SELECT id, nom, stripe_product_id, stripe_price_id, stripe_price_supplementaire_id, actif
                FROM formules_abonnement 
                WHERE stripe_product_id IS NOT NULL
                ORDER BY nom
            ");
        } catch (Exception $e) {
            $plans = [];
        }
        ?>
        
        <?php if (empty($plans)): ?>
            <div class="empty-state">
                <p>Aucune formule synchronisée avec Stripe pour le moment.</p>
                <a href="/subscription-plans/create" class="btn btn-primary">Créer une formule</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Formule</th>
                            <th>ID Produit Stripe</th>
                            <th>ID Prix Stripe</th>
                            <th>Prix Supplémentaire</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= htmlspecialchars($plan['nom']) ?></td>
                            <td>
                                <code class="stripe-id"><?= htmlspecialchars($plan['stripe_product_id']) ?></code>
                            </td>
                            <td>
                                <code class="stripe-id"><?= htmlspecialchars($plan['stripe_price_id']) ?></code>
                            </td>
                            <td>
                                <?php if ($plan['stripe_price_supplementaire_id']): ?>
                                    <code class="stripe-id"><?= htmlspecialchars($plan['stripe_price_supplementaire_id']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $plan['actif'] ? 'active' : 'inactive' ?>">
                                    <?= $plan['actif'] ? 'Actif' : 'Inactif' ?>
                                </span>
                            </td>
                            <td>
                                <a href="https://dashboard.stripe.com/products/<?= $plan['stripe_product_id'] ?>" 
                                   target="_blank" class="btn btn-sm">
                                    Voir sur Stripe
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

<style>
.stripe-test {
    max-width: 1000px;
    margin: 0 auto;
}

.page-header {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.page-header p {
    margin: 0;
    color: var(--text-secondary);
}

.config-card,
.config-help,
.test-actions,
.stripe-data {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.config-card h3,
.config-help h3,
.test-actions h3,
.stripe-data h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
}

.config-checks {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.check-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.check-item.success {
    background: rgba(34, 197, 94, 0.05);
    border-color: rgba(34, 197, 94, 0.2);
}

.check-item.error {
    background: rgba(239, 68, 68, 0.05);
    border-color: rgba(239, 68, 68, 0.2);
}

.check-item.info {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
}

.check-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.check-item.success .check-icon {
    background: rgba(34, 197, 94, 0.1);
    color: #15803d;
}

.check-item.error .check-icon {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.check-item.info .check-icon {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.check-content {
    flex: 1;
}

.check-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.check-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.env-badge {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.env-badge.sandbox {
    background: rgba(59, 130, 246, 0.1);
    color: #2563eb;
}

.env-badge.live {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.code-example {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin: 1rem 0;
}

.code-example pre {
    margin: 0;
    font-family: 'Monaco', 'Consolas', monospace;
    font-size: 0.875rem;
    line-height: 1.4;
}

.help-links {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.help-links a {
    color: var(--primary-color);
    text-decoration: none;
}

.help-links a:hover {
    text-decoration: underline;
}

.test-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
}

.test-results {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.test-results h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
}

#testOutput {
    font-family: monospace;
    white-space: pre-wrap;
    font-size: 0.875rem;
}

.stripe-id {
    font-family: monospace;
    font-size: 0.75rem;
    background: var(--bg-secondary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

@media (max-width: 768px) {
    .stripe-test {
        margin: 0;
    }
    
    .test-buttons {
        flex-direction: column;
    }
    
    .check-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
async function testCreateProduct() {
    const testResults = document.getElementById('testResults');
    const testOutput = document.getElementById('testOutput');
    
    testResults.style.display = 'block';
    testOutput.textContent = 'Test en cours...\n';
    
    try {
        const response = await fetch('/api/stripe/test-product', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            testOutput.textContent = `✅ Test réussi!\n\nProduit créé:\n${JSON.stringify(result.data, null, 2)}`;
        } else {
            testOutput.textContent = `❌ Test échoué:\n${result.error}`;
        }
    } catch (error) {
        testOutput.textContent = `❌ Erreur de test:\n${error.message}`;
    }
}

async function syncStripeData() {
    const testResults = document.getElementById('testResults');
    const testOutput = document.getElementById('testOutput');
    
    testResults.style.display = 'block';
    testOutput.textContent = 'Synchronisation en cours...\n';
    
    try {
        const response = await fetch('/api/stripe/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            testOutput.textContent = `✅ Synchronisation réussie!\n\n${JSON.stringify(result.stats, null, 2)}`;
            setTimeout(() => location.reload(), 2000);
        } else {
            testOutput.textContent = `❌ Synchronisation échouée:\n${result.error}`;
        }
    } catch (error) {
        testOutput.textContent = `❌ Erreur de synchronisation:\n${error.message}`;
    }
}

async function viewStripeProducts() {
    const testResults = document.getElementById('testResults');
    const testOutput = document.getElementById('testOutput');
    
    testResults.style.display = 'block';
    testOutput.textContent = 'Récupération des produits Stripe...\n';
    
    try {
        const response = await fetch('/api/stripe/products', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            testOutput.textContent = `✅ Produits Stripe récupérés:\n\n${JSON.stringify(result.products, null, 2)}`;
        } else {
            testOutput.textContent = `❌ Erreur de récupération:\n${result.error}`;
        }
    } catch (error) {
        testOutput.textContent = `❌ Erreur:\n${error.message}`;
    }
}
</script>

<?php } ?>