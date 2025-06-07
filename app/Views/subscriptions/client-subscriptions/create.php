<?php 
$pageTitle = $pageTitle ?? 'Nouvel abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $clients, $formules;
    
    $clients = $clients ?? [];
    $formules = $formules ?? [];
?>

<div class="subscription-create">
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
                <h1>Nouvel abonnement</h1>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/subscriptions" class="subscription-form" id="subscriptionForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Sélection du client -->
            <div class="form-section">
                <h3>Client</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="client_id">Client *</label>
                        <select id="client_id" name="client_id" required onchange="updateClientInfo()">
                            <option value="">Sélectionnez un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" 
                                        data-email="<?= htmlspecialchars($client['email_facturation']) ?>"
                                        data-ville="<?= htmlspecialchars($client['ville']) ?>"
                                        <?= ($_POST['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['raison_sociale']) ?> - <?= htmlspecialchars($client['ville']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Choisissez le client pour cet abonnement</small>
                    </div>
                </div>

                <div id="clientInfo" class="client-info" style="display: none;">
                    <div class="client-details">
                        <div class="client-detail">
                            <span class="detail-label">Email :</span>
                            <span class="detail-value" id="clientEmail">-</span>
                        </div>
                        <div class="client-detail">
                            <span class="detail-label">Ville :</span>
                            <span class="detail-value" id="clientVille">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sélection de la formule -->
            <div class="form-section">
                <h3>Formule d'abonnement</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="formule_id">Formule *</label>
                        <select id="formule_id" name="formule_id" required onchange="updateFormuleInfo()">
                            <option value="">Sélectionnez une formule</option>
                            <?php 
                            $currentType = '';
                            foreach ($formules as $formule): 
                                if ($formule['type_abonnement'] !== $currentType):
                                    if ($currentType !== '') echo '</optgroup>';
                                    $currentType = $formule['type_abonnement'];
                                    $typeLabels = [
                                        'application' => 'Application seule',
                                        'application_materiel' => 'Application + Matériel',
                                        'materiel_seul' => 'Matériel seul'
                                    ];
                                    echo '<optgroup label="' . ($typeLabels[$currentType] ?? $currentType) . '">';
                                endif;
                            ?>
                                <option value="<?= $formule['id'] ?>" 
                                        data-type="<?= $formule['type_abonnement'] ?>"
                                        data-prix="<?= $formule['prix_base'] ?>"
                                        data-duree="<?= $formule['duree'] ?>"
                                        data-users-inclus="<?= $formule['nombre_utilisateurs_inclus'] ?>"
                                        data-cout-user-supp="<?= $formule['cout_utilisateur_supplementaire'] ?? 0 ?>"
                                        data-categories="<?= $formule['nombre_sous_categories'] ?? 'Illimité' ?>"
                                        data-materiel="<?= htmlspecialchars($formule['materiel_nom'] ?? '') ?>"
                                        data-materiel-prix="<?= $formule['materiel_prix'] ?? 0 ?>"
                                        <?= ($_POST['formule_id'] ?? '') == $formule['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($formule['nom']) ?> - <?= number_format($formule['prix_base'], 2) ?>€
                                </option>
                            <?php 
                            endforeach;
                            if ($currentType !== '') echo '</optgroup>';
                            ?>
                        </select>
                        <small class="form-help">Choisissez la formule d'abonnement à souscrire</small>
                    </div>
                </div>

                <div id="formuleInfo" class="formule-info" style="display: none;">
                    <div class="formule-details">
                        <div class="formule-detail">
                            <span class="detail-label">Type :</span>
                            <span class="detail-value" id="formuleType">-</span>
                        </div>
                        <div class="formule-detail">
                            <span class="detail-label">Prix de base :</span>
                            <span class="detail-value" id="formulePrix">-</span>
                        </div>
                        <div class="formule-detail">
                            <span class="detail-label">Durée :</span>
                            <span class="detail-value" id="formuleDuree">-</span>
                        </div>
                        <div class="formule-detail">
                            <span class="detail-label">Utilisateurs inclus :</span>
                            <span class="detail-value" id="formuleUsersInclus">-</span>
                        </div>
                        <div class="formule-detail" id="formuleUsersSuppDetail" style="display: none;">
                            <span class="detail-label">Coût utilisateur supp. :</span>
                            <span class="detail-value" id="formuleUsersSupp">-</span>
                        </div>
                        <div class="formule-detail">
                            <span class="detail-label">Catégories :</span>
                            <span class="detail-value" id="formuleCategories">-</span>
                        </div>
                        <div class="formule-detail" id="formuleMaterielDetail" style="display: none;">
                            <span class="detail-label">Matériel :</span>
                            <span class="detail-value" id="formuleMateriel">-</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration de l'abonnement -->
            <div class="form-section">
                <h3>Configuration</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type_abonnement">Type d'abonnement</label>
                        <select id="type_abonnement" name="type_abonnement">
                            <option value="principal" <?= ($_POST['type_abonnement'] ?? 'principal') === 'principal' ? 'selected' : '' ?>>
                                Principal
                            </option>
                            <option value="supplementaire" <?= ($_POST['type_abonnement'] ?? '') === 'supplementaire' ? 'selected' : '' ?>>
                                Supplémentaire
                            </option>
                        </select>
                        <small class="form-help">Un client ne peut avoir qu'un seul abonnement principal</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_debut">Date de début</label>
                        <input type="date" id="date_debut" name="date_debut" 
                               value="<?= $_POST['date_debut'] ?? date('Y-m-d') ?>">
                        <small class="form-help">Date de début de facturation</small>
                    </div>
                </div>

                <div class="form-row" id="userConfigRow" style="display: none;">
                    <div class="form-group">
                        <label for="nombre_utilisateurs">Nombre d'utilisateurs</label>
                        <input type="number" id="nombre_utilisateurs" name="nombre_utilisateurs" 
                               min="1" value="<?= $_POST['nombre_utilisateurs'] ?? '1' ?>"
                               onchange="calculatePrice()">
                        <small class="form-help">Nombre total d'utilisateurs pour cet abonnement</small>
                    </div>
                </div>
            </div>

            <!-- Aperçu tarifaire -->
            <div class="form-section" id="pricingSection" style="display: none;">
                <h3>Aperçu tarifaire</h3>
                
                <div class="pricing-preview">
                    <div class="pricing-details">
                        <div class="pricing-row">
                            <span class="pricing-label">Prix de base :</span>
                            <span class="pricing-value" id="pricingBase">0,00€</span>
                        </div>
                        <div class="pricing-row" id="pricingExtraRow" style="display: none;">
                            <span class="pricing-label">Utilisateurs supplémentaires :</span>
                            <span class="pricing-value" id="pricingExtra">0,00€</span>
                        </div>
                        <div class="pricing-row" id="pricingDepositRow" style="display: none;">
                            <span class="pricing-label">Dépôt de garantie :</span>
                            <span class="pricing-value" id="pricingDeposit">0,00€</span>
                        </div>
                        <div class="pricing-row total">
                            <span class="pricing-label">Total mensuel :</span>
                            <span class="pricing-value highlight" id="pricingTotal">0,00€</span>
                        </div>
                        <div class="pricing-row first-payment" id="pricingFirstPaymentRow" style="display: none;">
                            <span class="pricing-label">Premier paiement :</span>
                            <span class="pricing-value highlight" id="pricingFirstPayment">0,00€</span>
                        </div>
                        <div class="pricing-row" id="pricingAnnualRow" style="display: none;">
                            <span class="pricing-label">Total annuel :</span>
                            <span class="pricing-value" id="pricingAnnual">0,00€</span>
                        </div>
                    </div>
                    
                    <div class="pricing-breakdown" id="pricingBreakdown" style="display: none;">
                        <h4>Détail du calcul</h4>
                        <div class="breakdown-content">
                            <div class="breakdown-item">
                                <span id="breakdownUsersIncluded">0</span> utilisateur(s) inclus
                            </div>
                            <div class="breakdown-item" id="breakdownExtraUsers" style="display: none;">
                                + <span id="breakdownExtraCount">0</span> utilisateur(s) supplémentaire(s) 
                                à <span id="breakdownExtraPrice">0,00</span>€ chacun
                            </div>
                            <div class="breakdown-item" id="breakdownDeposit" style="display: none;">
                                + Dépôt de garantie <span id="breakdownDepositMaterial"></span> : 
                                <span id="breakdownDepositAmount">0,00</span>€
                            </div>
                        </div>
                        
                        <div class="payment-info" id="paymentInfo" style="display: none;">
                            <div class="payment-note">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <path d="M12 16v-4"></path>
                                    <path d="M12 8h.01"></path>
                                </svg>
                                <div class="payment-text">
                                    <p><strong>Mode de paiement :</strong></p>
                                    <p>Le client sera redirigé vers Stripe pour configurer son mode de paiement et effectuer le premier paiement.</p>
                                    <p>Le dépôt de garantie sera facturé séparément.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="/subscriptions" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Créer l'abonnement
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.subscription-create {
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

.page-header h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
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
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
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

.form-help {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.client-info,
.formule-info {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-top: 0.5rem;
}

.client-details,
.formule-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.client-detail,
.formule-detail {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.detail-value {
    font-weight: 600;
    color: var(--text-primary);
}

.pricing-preview {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.pricing-details {
    margin-bottom: 1rem;
}

.pricing-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--border-color);
}

.pricing-row:last-child {
    border-bottom: none;
}

.pricing-row.total {
    border-top: 2px solid var(--border-color);
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.pricing-label {
    color: var(--text-secondary);
}

.pricing-value {
    font-weight: 600;
    color: var(--text-primary);
}

.pricing-value.highlight {
    color: var(--primary-color);
    font-size: 1.125rem;
}

.pricing-breakdown {
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
    margin-top: 1rem;
}

.pricing-breakdown h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.breakdown-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.breakdown-item {
    font-size: 0.75rem;
    color: var(--text-muted);
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
    .subscription-create {
        max-width: none;
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .subscription-form {
        padding: 1.5rem;
    }
    
    .client-details,
    .formule-details {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

/* Animation pour les sections qui apparaissent */
.form-section {
    opacity: 1;
    transition: opacity 0.3s ease-in-out;
}

.form-section[style*="display: none"] {
    opacity: 0;
}

/* Validation visuelle */
.form-group input:valid,
.form-group select:valid {
    border-color: var(--success-color);
}

.form-group input:invalid:not(:placeholder-shown),
.form-group select:invalid {
    border-color: var(--error-color);
}

/* Loading state */
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
let currentFormule = null;

function updateClientInfo() {
    const clientSelect = document.getElementById('client_id');
    const clientInfo = document.getElementById('clientInfo');
    const selectedOption = clientSelect.options[clientSelect.selectedIndex];
    
    if (clientSelect.value && selectedOption.dataset.email) {
        document.getElementById('clientEmail').textContent = selectedOption.dataset.email;
        document.getElementById('clientVille').textContent = selectedOption.dataset.ville;
        clientInfo.style.display = 'block';
    } else {
        clientInfo.style.display = 'none';
    }
}

function updateFormuleInfo() {
    const formuleSelect = document.getElementById('formule_id');
    const formuleInfo = document.getElementById('formuleInfo');
    const userConfigRow = document.getElementById('userConfigRow');
    const pricingSection = document.getElementById('pricingSection');
    const selectedOption = formuleSelect.options[formuleSelect.selectedIndex];
    
    if (formuleSelect.value && selectedOption.dataset.type) {
        currentFormule = {
            type: selectedOption.dataset.type,
            prix: parseFloat(selectedOption.dataset.prix),
            duree: selectedOption.dataset.duree,
            usersInclus: parseInt(selectedOption.dataset.usersInclus),
            coutUserSupp: parseFloat(selectedOption.dataset.coutUserSupp),
            categories: selectedOption.dataset.categories,
            materiel: selectedOption.dataset.materiel,
            materielPrix: parseFloat(selectedOption.dataset.materielPrix)
        };
        
        // Afficher les informations de la formule
        const typeLabels = {
            'application': 'Application seule',
            'application_materiel': 'Application + Matériel',
            'materiel_seul': 'Matériel seul'
        };
        
        document.getElementById('formuleType').textContent = typeLabels[currentFormule.type] || currentFormule.type;
        document.getElementById('formulePrix').textContent = currentFormule.prix.toFixed(2) + '€';
        document.getElementById('formuleDuree').textContent = currentFormule.duree === 'mensuelle' ? 'Mensuelle' : 'Annuelle';
        document.getElementById('formuleUsersInclus').textContent = currentFormule.usersInclus;
        document.getElementById('formuleCategories').textContent = currentFormule.categories;
        
        // Afficher/masquer les détails selon la formule
        const usersSuppDetail = document.getElementById('formuleUsersSuppDetail');
        if (currentFormule.coutUserSupp > 0) {
            document.getElementById('formuleUsersSupp').textContent = currentFormule.coutUserSupp.toFixed(2) + '€/utilisateur';
            usersSuppDetail.style.display = 'flex';
        } else {
            usersSuppDetail.style.display = 'none';
        }
        
        const materielDetail = document.getElementById('formuleMaterielDetail');
        if (currentFormule.materiel) {
            document.getElementById('formuleMateriel').textContent = currentFormule.materiel + ' (' + currentFormule.materielPrix.toFixed(2) + '€/mois)';
            materielDetail.style.display = 'flex';
        } else {
            materielDetail.style.display = 'none';
        }
        
        formuleInfo.style.display = 'block';
        
        // Afficher la configuration utilisateurs seulement si ce n'est pas "matériel seul"
        if (currentFormule.type !== 'materiel_seul') {
            userConfigRow.style.display = 'grid';
            
            // Définir le nombre d'utilisateurs par défaut
            const nombreUtilisateurs = document.getElementById('nombre_utilisateurs');
            if (nombreUtilisateurs.value < currentFormule.usersInclus) {
                nombreUtilisateurs.value = currentFormule.usersInclus;
            }
        } else {
            userConfigRow.style.display = 'none';
            document.getElementById('nombre_utilisateurs').value = 0;
        }
        
        pricingSection.style.display = 'block';
        calculatePrice();
        
    } else {
        formuleInfo.style.display = 'none';
        userConfigRow.style.display = 'none';
        pricingSection.style.display = 'none';
        currentFormule = null;
    }
}

function calculatePrice() {
    if (!currentFormule) return;
    
    const nombreUtilisateurs = parseInt(document.getElementById('nombre_utilisateurs').value) || 0;
    const utilisateursSupplementaires = Math.max(0, nombreUtilisateurs - currentFormule.usersInclus);
    
    const prixBase = currentFormule.prix;
    const prixUtilisateursSupp = utilisateursSupplementaires * currentFormule.coutUserSupp;
    const prixTotal = prixBase + prixUtilisateursSupp;
    
    // Mettre à jour l'affichage
    document.getElementById('pricingBase').textContent = prixBase.toFixed(2) + '€';
    document.getElementById('pricingTotal').textContent = prixTotal.toFixed(2) + '€';
    
    // Afficher/masquer la ligne des utilisateurs supplémentaires
    const extraRow = document.getElementById('pricingExtraRow');
    if (utilisateursSupplementaires > 0) {
        document.getElementById('pricingExtra').textContent = prixUtilisateursSupp.toFixed(2) + '€';
        extraRow.style.display = 'flex';
    } else {
        extraRow.style.display = 'none';
    }
    
    // Afficher le prix annuel si c'est une formule annuelle
    const annualRow = document.getElementById('pricingAnnualRow');
    if (currentFormule.duree === 'annuelle') {
        document.getElementById('pricingAnnual').textContent = (prixTotal * 12).toFixed(2) + '€';
        annualRow.style.display = 'flex';
    } else {
        annualRow.style.display = 'none';
    }
    
    // Mettre à jour le détail du calcul
    const breakdown = document.getElementById('pricingBreakdown');
    if (currentFormule.type !== 'materiel_seul') {
        document.getElementById('breakdownUsersIncluded').textContent = currentFormule.usersInclus;
        
        const extraUsersBreakdown = document.getElementById('breakdownExtraUsers');
        if (utilisateursSupplementaires > 0) {
            document.getElementById('breakdownExtraCount').textContent = utilisateursSupplementaires;
            document.getElementById('breakdownExtraPrice').textContent = currentFormule.coutUserSupp.toFixed(2);
            extraUsersBreakdown.style.display = 'block';
        } else {
            extraUsersBreakdown.style.display = 'none';
        }
        
        breakdown.style.display = 'block';
    } else {
        breakdown.style.display = 'none';
    }
}

// Validation du formulaire
function validateForm() {
    const clientId = document.getElementById('client_id').value;
    const formuleId = document.getElementById('formule_id').value;
    
    if (!clientId) {
        alert('Veuillez sélectionner un client');
        return false;
    }
    
    if (!formuleId) {
        alert('Veuillez sélectionner une formule d\'abonnement');
        return false;
    }
    
    // Validation du nombre d'utilisateurs
    if (currentFormule && currentFormule.type !== 'materiel_seul') {
        const nombreUtilisateurs = parseInt(document.getElementById('nombre_utilisateurs').value);
        if (nombreUtilisateurs < 1) {
            alert('Le nombre d\'utilisateurs doit être au moins de 1');
            return false;
        }
    }
    
    return true;
}

// Soumission du formulaire avec validation
document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
    if (!validateForm()) {
        e.preventDefault();
        return false;
    }
    
    // Ajouter un état de chargement
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    // Si la validation échoue côté serveur, restaurer le bouton
    setTimeout(() => {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }, 5000);
});

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateClientInfo();
    updateFormuleInfo();
    
    // Auto-focus sur le premier champ
    document.getElementById('client_id').focus();
});

// Recherche en temps réel dans les selects (amélioration UX)
function makeSelectSearchable(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.addEventListener('keydown', function(e) {
        if (e.key.length === 1) {
            const searchTerm = e.key.toLowerCase();
            const options = Array.from(this.options);
            
            const matchingOption = options.find(option => 
                option.text.toLowerCase().startsWith(searchTerm)
            );
            
            if (matchingOption) {
                this.selectedIndex = matchingOption.index;
                this.dispatchEvent(new Event('change'));
            }
        }
    });
}

// Rendre les selects cherchables
makeSelectSearchable('client_id');
makeSelectSearchable('formule_id');
</script>

<?php } ?>