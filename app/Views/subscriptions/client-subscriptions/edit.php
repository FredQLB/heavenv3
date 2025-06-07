<?php 
$pageTitle = $pageTitle ?? 'Modifier l\'abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $subscription, $formules;
    
    if (!$subscription) {
        echo '<div class="error-message">Abonnement non trouvé</div>';
        return;
    }
    
    $formules = $formules ?? [];
?>

<div class="subscription-edit">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/subscriptions/<?= $subscription['id'] ?>" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <div class="page-title">
                    <h1>Modifier l'abonnement</h1>
                    <div class="subtitle"><?= htmlspecialchars($subscription['raison_sociale']) ?> - #<?= $subscription['id'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Avertissements -->
    <?php if ($subscription['statut'] !== 'actif'): ?>
        <div class="alert alert-warning">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            Cet abonnement n'est pas actif. Certaines modifications peuvent ne pas être synchronisées avec Stripe.
        </div>
    <?php endif; ?>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/subscriptions/<?= $subscription['id'] ?>/update" class="subscription-form" id="subscriptionForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Informations de base (lecture seule) -->
            <div class="form-section">
                <h3>Informations de base</h3>
                <p class="section-help">Ces informations ne peuvent pas être modifiées. Pour changer de formule, créez un nouvel abonnement.</p>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Client</label>
                        <input type="text" value="<?= htmlspecialchars($subscription['raison_sociale']) ?>" readonly class="readonly">
                    </div>
                    
                    <div class="form-group">
                        <label>Formule</label>
                        <input type="text" value="<?= htmlspecialchars($subscription['formule_nom']) ?>" readonly class="readonly">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Date de début</label>
                        <input type="text" value="<?= date('d/m/Y', strtotime($subscription['date_debut'])) ?>" readonly class="readonly">
                    </div>
                    
                    <div class="form-group">
                        <label>Date de fin</label>
                        <input type="text" value="<?= $subscription['date_fin'] ? date('d/m/Y', strtotime($subscription['date_fin'])) : 'Sans limite' ?>" readonly class="readonly">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Statut actuel</label>
                        <span class="status-badge status-<?= $subscription['statut'] ?>">
                            <?= htmlspecialchars($subscription['statut_display']) ?>
                        </span>
                    </div>
                    
                    <?php if ($subscription['stripe_subscription_id']): ?>
                    <div class="form-group">
                        <label>ID Stripe</label>
                        <input type="text" value="<?= htmlspecialchars($subscription['stripe_subscription_id']) ?>" readonly class="readonly">
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Configuration modifiable -->
            <div class="form-section">
                <h3>Configuration</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_utilisateurs_actuels">Nombre d'utilisateurs actuels *</label>
                        <input type="number" id="nombre_utilisateurs_actuels" name="nombre_utilisateurs_actuels" 
                               min="<?= $subscription['nombre_utilisateurs_inclus'] ?>" 
                               value="<?= $_POST['nombre_utilisateurs_actuels'] ?? $subscription['nombre_utilisateurs_actuels'] ?>"
                               onchange="calculateNewPrice()" required>
                        <small class="form-help">
                            Minimum <?= $subscription['nombre_utilisateurs_inclus'] ?> utilisateur(s) inclus dans la formule
                        </small>
                    </div>
                </div>

                <div class="price-impact" id="priceImpact" style="display: none;">
                    <div class="impact-content">
                        <div class="impact-header">
                            <h4>Impact tarifaire</h4>
                        </div>
                        <div class="impact-details">
                            <div class="impact-row">
                                <span class="impact-label">Prix actuel :</span>
                                <span class="impact-value"><?= number_format($subscription['prix_total_mensuel'], 2) ?>€/mois</span>
                            </div>
                            <div class="impact-row">
                                <span class="impact-label">Nouveau prix :</span>
                                <span class="impact-value" id="newPrice">0,00€/mois</span>
                            </div>
                            <div class="impact-row difference">
                                <span class="impact-label">Différence :</span>
                                <span class="impact-value" id="priceDifference">0,00€/mois</span>
                            </div>
                        </div>
                        <div class="impact-note">
                            <small>La modification sera prise en compte sur la prochaine facture</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options avancées -->
            <div class="form-section">
                <h3>Options avancées</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="recalculate_price" value="1" onchange="togglePriceRecalculation()">
                            <span class="checkbox-custom"></span>
                            Recalculer automatiquement le prix
                        </label>
                        <small class="form-help">
                            Recalcule le prix total basé sur la formule et le nombre d'utilisateurs
                        </small>
                    </div>
                </div>

                <div class="form-row" id="manualPriceRow" style="display: none;">
                    <div class="form-group">
                        <label for="prix_total_mensuel_manual">Prix mensuel manuel (€)</label>
                        <input type="number" id="prix_total_mensuel_manual" name="prix_total_mensuel_manual" 
                               step="0.01" min="0" 
                               value="<?= $_POST['prix_total_mensuel_manual'] ?? $subscription['prix_total_mensuel'] ?>">
                        <small class="form-help">
                            Définir un prix personnalisé pour cet abonnement
                        </small>
                    </div>
                </div>
            </div>

            <!-- Historique des modifications (si applicable) -->
            <?php if ($subscription['date_modification'] !== $subscription['date_creation']): ?>
            <div class="form-section">
                <h3>Historique</h3>
                <div class="history-info">
                    <div class="history-item">
                        <span class="history-label">Créé le :</span>
                        <span class="history-value"><?= date('d/m/Y à H:i', strtotime($subscription['date_creation'])) ?></span>
                    </div>
                    <div class="history-item">
                        <span class="history-label">Dernière modification :</span>
                        <span class="history-value"><?= date('d/m/Y à H:i', strtotime($subscription['date_modification'])) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="form-actions">
                <a href="/subscriptions/<?= $subscription['id'] ?>" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.subscription-edit {
    max-width: 800px;
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

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
}

.alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    color: #92400e;
}

.form-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.subscription-form {
    padding: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
}

.section-help {
    margin: 0 0 1.5rem 0;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.form-group input.readonly {
    background: var(--bg-secondary);
    color: var(--text-muted);
    cursor: not-allowed;
}

.form-help {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 12px;
    align-self: flex-start;
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    width: 18px;
    height: 18px;
    border: 2px solid var(--border-color);
    border-radius: 3px;
    position: relative;
    transition: var(--transition);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
    background: var(--primary-color);
    border-color: var(--primary-color);
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 2px;
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.price-impact {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-top: 1rem;
}

.impact-header h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
}

.impact-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.impact-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
}

.impact-row.difference {
    border-top: 1px solid var(--border-color);
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-weight: 600;
}

.impact-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.impact-value {
    font-weight: 600;
    color: var(--text-primary);
}

.impact-note {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
}

.impact-note small {
    color: var(--text-muted);
    font-style: italic;
}

.history-info {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
}

.history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.history-item:last-child {
    border-bottom: none;
}

.history-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.history-value {
    font-size: 0.875rem;
    color: var(--text-primary);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .subscription-edit {
        max-width: none;
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .subscription-form {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .impact-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .history-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}

/* Animation pour les changements de prix */
.price-impact {
    transition: all 0.3s ease;
}

.impact-value {
    transition: color 0.3s ease;
}

.impact-value.positive {
    color: var(--error-color);
}

.impact-value.negative {
    color: var(--success-color);
}

.impact-value.neutral {
    color: var(--text-muted);
}

/* Validation visuelle */
.form-group input:valid {
    border-color: var(--success-color);
}

.form-group input:invalid:not(:placeholder-shown) {
    border-color: var(--error-color);
}

/* État de chargement */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: translate(-50%, -50%) rotate(360deg);
    }
}
</style>

<script>
// Données de la formule actuelle
const currentFormule = {
    usersInclus: <?= $subscription['nombre_utilisateurs_inclus'] ?>,
    coutUserSupp: <?= $subscription['cout_utilisateur_supplementaire'] ?? 0 ?>,
    prixBase: <?= $subscription['prix_total_mensuel'] ?> - (Math.max(0, <?= $subscription['nombre_utilisateurs_actuels'] ?> - <?= $subscription['nombre_utilisateurs_inclus'] ?>) * <?= $subscription['cout_utilisateur_supplementaire'] ?? 0 ?>)
};

const currentPrice = <?= $subscription['prix_total_mensuel'] ?>;

function calculateNewPrice() {
    const nombreUtilisateurs = parseInt(document.getElementById('nombre_utilisateurs_actuels').value) || 0;
    const priceImpact = document.getElementById('priceImpact');
    
    if (nombreUtilisateurs < currentFormule.usersInclus) {
        priceImpact.style.display = 'none';
        return;
    }
    
    const utilisateursSupplementaires = Math.max(0, nombreUtilisateurs - currentFormule.usersInclus);
    const newPrice = currentFormule.prixBase + (utilisateursSupplementaires * currentFormule.coutUserSupp);
    const difference = newPrice - currentPrice;
    
    // Afficher l'impact
    document.getElementById('newPrice').textContent = newPrice.toFixed(2) + '€/mois';
    
    const differenceElement = document.getElementById('priceDifference');
    const differenceText = (difference >= 0 ? '+' : '') + difference.toFixed(2) + '€/mois';
    differenceElement.textContent = differenceText;
    
    // Couleur selon la différence
    differenceElement.className = 'impact-value ' + (difference > 0 ? 'positive' : difference < 0 ? 'negative' : 'neutral');
    
    priceImpact.style.display = 'block';
}

function togglePriceRecalculation() {
    const checkbox = document.querySelector('input[name="recalculate_price"]');
    const manualPriceRow = document.getElementById('manualPriceRow');
    
    if (checkbox.checked) {
        manualPriceRow.style.display = 'none';
        calculateNewPrice();
    } else {
        manualPriceRow.style.display = 'grid';
    }
}

// Validation du formulaire
function validateForm() {
    const nombreUtilisateurs = parseInt(document.getElementById('nombre_utilisateurs_actuels').value);
    
    if (nombreUtilisateurs < currentFormule.usersInclus) {
        alert(`Le nombre d'utilisateurs ne peut pas être inférieur à ${currentFormule.usersInclus} (inclus dans la formule)`);
        return false;
    }
    
    const recalculatePrice = document.querySelector('input[name="recalculate_price"]').checked;
    if (!recalculatePrice) {
        const manualPrice = parseFloat(document.getElementById('prix_total_mensuel_manual').value);
        if (isNaN(manualPrice) || manualPrice < 0) {
            alert('Le prix mensuel manuel doit être un nombre positif');
            return false;
        }
    }
    
    return true;
}

// Confirmation des modifications importantes
function confirmSignificantChanges() {
    const nombreUtilisateurs = parseInt(document.getElementById('nombre_utilisateurs_actuels').value);
    const currentUsers = <?= $subscription['nombre_utilisateurs_actuels'] ?>;
    
    if (Math.abs(nombreUtilisateurs - currentUsers) >= 5) {
        return confirm('Vous modifiez le nombre d\'utilisateurs de façon importante. Cette modification sera reflétée sur la prochaine facture. Continuer ?');
    }
    
    return true;
}

// Soumission du formulaire
document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
    if (!validateForm() || !confirmSignificantChanges()) {
        e.preventDefault();
        return false;
    }
    
    // État de chargement
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Restaurer si erreur
    setTimeout(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }, 5000);
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    calculateNewPrice();
    
    // Auto-focus sur le champ principal
    document.getElementById('nombre_utilisateurs_actuels').focus();
    
    // Surveiller les changements en temps réel
    document.getElementById('nombre_utilisateurs_actuels').addEventListener('input', calculateNewPrice);
});

// Sauvegarde automatique des brouillons (optionnel)
let autoSaveTimeout;
function autoSave() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        const formData = new FormData(document.getElementById('subscriptionForm'));
        
        // Ici vous pourriez implémenter une sauvegarde automatique
        console.log('Auto-sauvegarde:', Object.fromEntries(formData));
    }, 2000);
}

// Activer l'auto-sauvegarde sur les champs principaux
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#subscriptionForm input, #subscriptionForm select');
    inputs.forEach(input => {
        input.addEventListener('change', autoSave);
    });
});

// Gestion des raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl+S pour sauvegarder
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('subscriptionForm').dispatchEvent(new Event('submit'));
    }
    
    // Escape pour annuler
    if (e.key === 'Escape') {
        if (confirm('Quitter sans sauvegarder ?')) {
            window.location.href = '/subscriptions/<?= $subscription['id'] ?>';
        }
    }
});

// Avertissement avant fermeture si modifications non sauvegardées
let hasUnsavedChanges = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('subscriptionForm');
    const initialFormData = new FormData(form);
    const initialData = Object.fromEntries(initialFormData);
    
    // Surveiller les changements
    form.addEventListener('input', function() {
        const currentFormData = new FormData(form);
        const currentData = Object.fromEntries(currentFormData);
        
        hasUnsavedChanges = JSON.stringify(initialData) !== JSON.stringify(currentData);
    });
    
    // Marquer comme sauvegardé lors de la soumission
    form.addEventListener('submit', function() {
        hasUnsavedChanges = false;
    });
});

window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
        return e.returnValue;
    }
});
</script>

<?php } ?>