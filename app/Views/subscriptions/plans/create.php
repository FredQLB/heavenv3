<?php 
$pageTitle = $pageTitle ?? 'Créer une formule d\'abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $materials;
?>

<div class="subscription-plan-create">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/subscription-plans" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <h1>Créer une formule d'abonnement</h1>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/subscription-plans" class="plan-form" id="planForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Informations générales -->
            <div class="form-section">
                <h3>Informations générales</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom de la formule *</label>
                        <input type="text" id="nom" name="nom" required 
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                               placeholder="Ex: Formule Pro">
                        <small class="form-help">Nom commercial de votre formule d'abonnement</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type_abonnement">Type d'abonnement *</label>
                        <select id="type_abonnement" name="type_abonnement" required onchange="toggleMaterialSection()">
                            <option value="">Sélectionnez un type</option>
                            <option value="application" <?= ($_POST['type_abonnement'] ?? '') === 'application' ? 'selected' : '' ?>>
                                Application seule
                            </option>
                            <option value="application_materiel" <?= ($_POST['type_abonnement'] ?? '') === 'application_materiel' ? 'selected' : '' ?>>
                                Application + Matériel
                            </option>
                            <option value="materiel_seul" <?= ($_POST['type_abonnement'] ?? '') === 'materiel_seul' ? 'selected' : '' ?>>
                                Matériel seul
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="duree">Durée de facturation *</label>
                        <select id="duree" name="duree" required>
                            <option value="">Sélectionnez une durée</option>
                            <option value="mensuelle" <?= ($_POST['duree'] ?? '') === 'mensuelle' ? 'selected' : '' ?>>
                                Mensuelle
                            </option>
                            <option value="annuelle" <?= ($_POST['duree'] ?? '') === 'annuelle' ? 'selected' : '' ?>>
                                Annuelle
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Tarification</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prix_base">Prix de base (€) *</label>
                        <input type="number" id="prix_base" name="prix_base" 
                               min="0" step="0.10" required 
                               value="<?= htmlspecialchars($_POST['prix_base'] ?? '') ?>"
                               placeholder="0.00" data-baseAppPrice="90.00">
                        <small class="form-help">Prix de base de la formule (hors utilisateurs supplémentaires)</small>
                    </div>
                </div>
            </div>

            <!-- Configuration utilisateurs -->
            <div class="form-section" id="usersSection">
                <h3>Configuration des utilisateurs</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_utilisateurs_inclus">Nombre d'utilisateurs inclus *</label>
                        <input type="number" id="nombre_utilisateurs_inclus" name="nombre_utilisateurs_inclus" 
                               min="0" required value="<?= htmlspecialchars($_POST['nombre_utilisateurs_inclus'] ?? '1') ?>">
                        <small class="form-help">Nombre d'utilisateurs inclus dans le prix de base</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="cout_utilisateur_supplementaire">Coût par utilisateur supplémentaire (€)</label>
                        <input type="number" id="cout_utilisateur_supplementaire" name="cout_utilisateur_supplementaire" 
                               min="0" step="0.01" value="<?= htmlspecialchars($_POST['cout_utilisateur_supplementaire'] ?? '') ?>"
                               placeholder="0.00">
                        <small class="form-help">Prix par utilisateur au-delà du nombre inclus</small>
                    </div>
                </div>
            </div>

            <!-- Configuration catégories -->
            <div class="form-section" id="categoriesSection">
                <h3>Accès aux catégories</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_sous_categories">Nombre de sous-catégories autorisées</label>
                        <input type="number" id="nombre_sous_categories" name="nombre_sous_categories" 
                               min="0" value="<?= htmlspecialchars($_POST['nombre_sous_categories'] ?? '') ?>"
                               placeholder="Laissez vide pour un accès illimité">
                        <small class="form-help">Nombre de sous-catégories de textures accessibles (vide = illimité)</small>
                    </div>
                </div>
            </div>

            <!-- Configuration matériel -->
            <div class="form-section" id="materialSection" style="display: none;">
                <h3>Configuration du matériel</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="modele_materiel_id">Modèle de matériel *</label>
                        <select id="modele_materiel_id" name="modele_materiel_id">
                            <option value="">Sélectionnez un modèle</option>
                            <?php foreach (($materials ?? []) as $material): ?>
                                <option value="<?= $material['id'] ?>" 
                                        data-price="<?= $material['prix_mensuel'] ?>"
                                        data-deposit="<?= $material['depot_garantie'] ?>"
                                        <?= ($_POST['modele_materiel_id'] ?? '') == $material['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($material['nom']) ?> 
                                    (<?= number_format($material['prix_mensuel'], 2) ?>€/mois)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Modèle de matériel inclus dans cette formule</small>
                    </div>
                </div>
                
                <div id="materialInfo" class="material-info" style="display: none;">
                    <div class="material-details">
                        <div class="material-detail">
                            <span class="detail-label">Prix mensuel :</span>
                            <span class="detail-value" id="materialPrice">-</span>
                        </div>
                        <div class="material-detail">
                            <span class="detail-label">Dépôt de garantie :</span>
                            <span class="detail-value" id="materialDeposit">-</span>
                            <input type="hidden" name="DepositAmount" value="<?= $material['depot_garantie'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarification -->
            <div class="form-section">                
                <div class="pricing-preview" id="pricingPreview">
                    <h4>Aperçu tarifaire</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <span class="preview-label">Prix de base :</span>
                            <span class="preview-value" id="previewBasePrice">0,00€</span>
                        </div>
                        <div class="preview-item" id="previewExtraUsers" style="display: none;">
                            <span class="preview-label">Utilisateurs supplémentaires :</span>
                            <span class="preview-value" id="previewExtraPrice">+0,00€/utilisateur</span>
                        </div>
                        <div class="preview-item" id="previewMaterial" style="display: none;">
                            <span class="preview-label">Matériel inclus :</span>
                            <span class="preview-value" id="previewMaterialPrice">0,00€/mois</span>
                        </div>
                        <div class="preview-item" id="previewDeposit" style="display: none;">
                            <span class="preview-label">Dépôt de Garantie :</span>
                            <span class="preview-value" id="previewDepositPrice">0,00€</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="form-section">
                <h3>Statut</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="actif" value="1" 
                                   <?= ($_POST['actif'] ?? '1') ? 'checked' : '' ?>>
                            <span class="checkbox-custom"></span>
                            Formule active
                        </label>
                        <small class="form-help">Une formule inactive n'est pas proposée aux nouveaux clients</small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="/subscription-plans" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Créer la formule
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.subscription-plan-create {
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

.plan-form {
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
    display: inline-block;
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

.material-info {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-top: 0.5rem;
}

.material-details {
    display: flex;
    gap: 2rem;
}

.material-detail {
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
    margin-top: 1rem;
}

.pricing-preview h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.preview-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.preview-value {
    font-weight: 600;
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
    .subscription-plan-create {
        max-width: none;
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .plan-form {
        padding: 1.5rem;
    }
    
    .material-details {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

/* Styles CSS à ajouter dans create.php et edit.php */

.price-calculation-info {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: var(--border-radius);
    font-size: 0.75rem;
}

.calculation-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1d4ed8;
    font-weight: 500;
}

.calculation-detail svg {
    flex-shrink: 0;
    color: #3b82f6;
}

/* Mise à jour de l'aperçu tarifaire pour mieux différencier les composants */
.pricing-preview {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-top: 1rem;
}

.pricing-preview h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.preview-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.preview-item.total {
    border-top: 1px solid var(--border-color);
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    font-weight: 600;
}

.preview-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.preview-value {
    font-weight: 600;
    color: var(--text-primary);
}

.preview-value.highlight {
    color: var(--primary-color);
    font-size: 1.1rem;
}

/* Style pour indiquer que le prix est calculé automatiquement */
.form-group.auto-calculated input {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.3);
}

.form-group.auto-calculated label::after {
    content: " (calculé automatiquement)";
    font-size: 0.75rem;
    color: #3b82f6;
    font-weight: normal;
}

/* Animation pour les changements de prix */
@keyframes priceUpdate {
    0% { background-color: rgba(59, 130, 246, 0.2); }
    100% { background-color: transparent; }
}

.price-updated {
    animation: priceUpdate 0.5s ease-out;
}
</style>

<script>
function toggleMaterialSection() {
    const typeSelect = document.getElementById('type_abonnement');
    const materialSection = document.getElementById('materialSection');
    const usersSection = document.getElementById('usersSection');
    const categoriesSection = document.getElementById('categoriesSection');
    const materialSelect = document.getElementById('modele_materiel_id');
    
    const requiresMaterial = ['application_materiel', 'materiel_seul'].includes(typeSelect.value);
    const isAppOnly = typeSelect.value === 'materiel_seul';
    
    // Afficher/masquer la section matériel
    materialSection.style.display = requiresMaterial ? 'block' : 'none';
    
    // Pour "matériel seul", masquer les sections utilisateurs et catégories
    usersSection.style.display = isAppOnly ? 'none' : 'block';
    categoriesSection.style.display = isAppOnly ? 'none' : 'block';
    
    // Rendre le matériel obligatoire ou non
    materialSelect.required = requiresMaterial;
    
    // Réinitialiser la sélection de matériel si plus nécessaire
    if (!requiresMaterial) {
        materialSelect.value = '';
        updateMaterialInfo();
    }
    
    updatePricingPreview();
}

function updateMaterialInfo() {
    const materialSelect = document.getElementById('modele_materiel_id');
    const materialInfo = document.getElementById('materialInfo');
    const selectedOption = materialSelect.options[materialSelect.selectedIndex];
    const typeSelect = document.getElementById('type_abonnement');
    const basePriceInput = document.getElementById('prix_base');
    
    if (materialSelect.value && selectedOption.dataset.price) {
        const price = parseFloat(selectedOption.dataset.price);
        const deposit = parseFloat(selectedOption.dataset.deposit);
        
        document.getElementById('materialPrice').textContent = price.toFixed(2) + '€/mois';
        document.getElementById('materialDeposit').textContent = deposit.toFixed(2) + '€';
        
        materialInfo.style.display = 'block';
        
        // NOUVELLE LOGIQUE : Mise à jour automatique du prix de base
        updateBasePriceWithMaterial(price);
        
    } else {
        materialInfo.style.display = 'none';
        
        // Si aucun matériel sélectionné, réinitialiser le prix de base
        if (['application_materiel', 'materiel_seul'].includes(typeSelect.value)) {
            updateBasePriceWithMaterial(0);
        }
    }
    
    updatePricingPreview();
}

function updateBasePriceWithMaterial(materialPrice) {
    const typeSelect = document.getElementById('type_abonnement');
    const basePriceInput = document.getElementById('prix_base');
    const dureeSelect = document.getElementById('duree');
    
    // Sauvegarder le prix de base "application seule" si pas encore fait
    if (!basePriceInput.dataset.baseAppPrice) {
        basePriceInput.dataset.baseAppPrice = basePriceInput.value || '0';
    }
    
    const baseAppPrice = parseFloat(basePriceInput.dataset.baseAppPrice) || 0;
    let newPrice = 0;
    
    if (typeSelect.value === 'application_materiel') {
        // Application + Matériel : prix app + prix matériel
        newPrice = baseAppPrice + materialPrice;
    } else if (typeSelect.value === 'materiel_seul') {
        // Matériel seul : seulement le prix du matériel
        newPrice = materialPrice;
    } else {
        // Application seule : prix de base application
        newPrice = baseAppPrice;
    }
    
    // Ajuster selon la durée (annuelle = 12 mois avec éventuelle remise)
    if (dureeSelect.value === 'annuelle' && newPrice > 0) {
        // Appliquer une remise de 10% pour l'annuel par exemple
        newPrice = newPrice * 12 * 0.9; // 10% de remise
    }
    
    basePriceInput.value = newPrice.toFixed(2);
    
    // Afficher une indication visuelle du calcul
    updatePriceCalculationInfo(typeSelect.value, baseAppPrice, materialPrice, dureeSelect.value);
}

function updatePriceCalculationInfo(type, appPrice, materialPrice, duree) {
    let existingInfo = document.getElementById('priceCalculationInfo');
    if (existingInfo) {
        existingInfo.remove();
    }
    
    if ((type === 'application_materiel' || type === 'materiel_seul') && materialPrice > 0) {
        const basePriceGroup = document.getElementById('prix_base').closest('.form-group');
        const infoDiv = document.createElement('div');
        
        let calculationText = '';
        if (type === 'application_materiel') {
            calculationText = `Calcul automatique : ${appPrice.toFixed(2)}€ (app) + ${materialPrice.toFixed(2)}€ (matériel)`;
        } else if (type === 'materiel_seul') {
            calculationText = `Prix du matériel : ${materialPrice.toFixed(2)}€/mois`;
        }
        
        if (duree === 'annuelle') {
            calculationText += ' × 12 mois × 0.9 (remise 10%)';
        }
        
        infoDiv.innerHTML = ``;
        
        basePriceGroup.appendChild(infoDiv);
    }
}

function handleDurationChange() {
    // Recalculer le prix quand la durée change
    const materialSelect = document.getElementById('modele_materiel_id');
    const selectedOption = materialSelect.options[materialSelect.selectedIndex];
    const materialPrice = selectedOption && selectedOption.dataset.price ? parseFloat(selectedOption.dataset.price) : 0;
    
    updateBasePriceWithMaterial(materialPrice);
    updatePricingPreview();
}

function handleBasePriceManualChange() {
    const basePriceInput = document.getElementById('prix_base');
    const typeSelect = document.getElementById('type_abonnement');
    
    // Si l'utilisateur modifie manuellement le prix et qu'on est en mode "application seule",
    // sauvegarder cette valeur comme nouveau prix de base application
    if (typeSelect.value === 'application' || typeSelect.value === '') {
        basePriceInput.dataset.baseAppPrice = basePriceInput.value;
    }
    
    updatePricingPreview();
}

function updatePricingPreview() {
    const basePrice = parseFloat(document.getElementById('prix_base').value) || 0;
    const extraUserCost = parseFloat(document.getElementById('cout_utilisateur_supplementaire').value) || 0;
    const materialSelect = document.getElementById('modele_materiel_id');
    const selectedMaterial = materialSelect.options[materialSelect.selectedIndex];
    const materialPrice = selectedMaterial && selectedMaterial.dataset.price ? parseFloat(selectedMaterial.dataset.price) : 0;
    const depositPrice = selectedMaterial && selectedMaterial.dataset.deposit ? parseFloat(selectedMaterial.dataset.deposit) : 0;
    const typeSelect = document.getElementById('type_abonnement');
    const dureeSelect = document.getElementById('duree');
    const userNumber = parseFloat(document.getElementById('nombre_utilisateurs_inclus').value) || 0;
    
    // Mise à jour de l'aperçu
    document.getElementById('previewBasePrice').textContent = basePrice.toFixed(2) + '€';
    
    const extraUsersItem = document.getElementById('previewExtraUsers');
    if (extraUserCost > 0 && typeSelect.value !== 'materiel_seul') {
        document.getElementById('previewExtraPrice').textContent = '+' + extraUserCost.toFixed(2) + '€/utilisateur';
        extraUsersItem.style.display = 'flex';
    } else {
        extraUsersItem.style.display = 'none';
    }
    
    const materialItem = document.getElementById('previewMaterial');
    if (['application_materiel', 'materiel_seul'].includes(typeSelect.value) && materialPrice > 0) {
        // Afficher le prix du matériel comme information, mais il est déjà inclus dans le prix de base
        totalMaterialPrice = materialPrice*userNumber;
        document.getElementById('previewMaterialPrice').textContent = totalMaterialPrice.toFixed(2) + '€/mois (inclus)';
        materialItem.style.display = 'flex';
    } else {
        materialItem.style.display = 'none';
    }

    const depositItem = document.getElementById('previewDeposit');
    if (['application_materiel', 'materiel_seul'].includes(typeSelect.value) && depositPrice > 0) {
        // Afficher le prix du matériel comme information, mais il est déjà inclus dans le prix de base
        totaldepositPrice = depositPrice*userNumber;
        document.getElementById('previewDepositPrice').textContent = totaldepositPrice.toFixed(2) + '€';
        depositItem.style.display = 'flex';
    } else {
        depositItem.style.display = 'none';
    }
    
    // Calcul du prix total selon la durée
    let totalPrice = basePrice;
    if (dureeSelect.value === 'annuelle') {
        // Le prix de base est déjà calculé pour l'année
        totalPrice = basePrice;
    }
    
    // Ajouter un item pour le prix total si différent du prix de base
    let totalItem = document.getElementById('previewTotal');
    if (!totalItem) {
        totalItem = document.createElement('div');
        totalItem.id = 'previewTotal';
        totalItem.className = 'preview-item total';
        totalItem.innerHTML = `
            <span class="preview-label">Prix total :</span>
            <span class="preview-value highlight" id="previewTotalValue">0,00€</span>
        `;
        document.querySelector('.preview-content').appendChild(totalItem);
    }
    
    const totalValueSpan = document.getElementById('previewTotalValue');
    if (dureeSelect.value === 'annuelle') {
        totalValueSpan.textContent = totalPrice.toFixed(2) + '€/an';
        totalItem.style.display = 'flex';
    } else {
        totalValueSpan.textContent = totalPrice.toFixed(2) + '€/mois';
        totalItem.style.display = 'flex';
    }
    
    // Animation de mise à jour
    const priceInput = document.getElementById('prix_base');
    priceInput.classList.add('price-updated');
    setTimeout(() => {
        priceInput.classList.remove('price-updated');
    }, 500);
}

// Event listeners mis à jour
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('modele_materiel_id');
    const basePriceInput = document.getElementById('prix_base');
    const dureeSelect = document.getElementById('duree');
    const typeSelect = document.getElementById('type_abonnement');
    const userInput = document.getElementById('nombre_utilisateurs_inclus');
    
    materialSelect.addEventListener('change', updateMaterialInfo);
    dureeSelect.addEventListener('change', handleDurationChange);
    basePriceInput.addEventListener('input', handleBasePriceManualChange);
    basePriceInput.addEventListener('input', updatePricingPreview);
    basePriceInput.addEventListener('input', updatePriceCalculationInfo);
    userInput.addEventListener('input', updatePricingPreview);
    document.getElementById('cout_utilisateur_supplementaire').addEventListener('input', updatePricingPreview);
    
    // Initialisation
    toggleMaterialSection();
    updateMaterialInfo();
    updatePricingPreview();
});
</script>

<?php } ?>