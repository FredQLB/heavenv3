<?php 
$pageTitle = $pageTitle ?? 'Créer une formule d\'abonnement';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $materials;

    print_r($materials);
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarification -->
            <div class="form-section">
                <h3>Tarification</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prix_base">Prix de base (€) *</label>
                        <input type="number" id="prix_base" name="prix_base" 
                               min="0" step="0.01" required 
                               value="<?= htmlspecialchars($_POST['prix_base'] ?? '') ?>"
                               placeholder="0.00">
                        <small class="form-help">Prix de base de la formule (hors utilisateurs supplémentaires)</small>
                    </div>
                </div>
                
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
    
    updatePricingPreview();
}

function updateMaterialInfo() {
    const materialSelect = document.getElementById('modele_materiel_id');
    const materialInfo = document.getElementById('materialInfo');
    const selectedOption = materialSelect.options[materialSelect.selectedIndex];
    
    if (materialSelect.value && selectedOption.dataset.price) {
        const price = parseFloat(selectedOption.dataset.price);
        const deposit = parseFloat(selectedOption.dataset.deposit);
        
        document.getElementById('materialPrice').textContent = price.toFixed(2) + '€/mois';
        document.getElementById('materialDeposit').textContent = deposit.toFixed(2) + '€';
        
        materialInfo.style.display = 'block';
    } else {
        materialInfo.style.display = 'none';
    }
    
    updatePricingPreview();
}

function updatePricingPreview() {
    const basePrice = parseFloat(document.getElementById('prix_base').value) || 0;
    const extraUserCost = parseFloat(document.getElementById('cout_utilisateur_supplementaire').value) || 0;
    const materialSelect = document.getElementById('modele_materiel_id');
    const selectedMaterial = materialSelect.options[materialSelect.selectedIndex];
    const materialPrice = selectedMaterial && selectedMaterial.dataset.price ? parseFloat(selectedMaterial.dataset.price) : 0;
    
    // Mise à jour de l'aperçu
    document.getElementById('previewBasePrice').textContent = basePrice.toFixed(2) + '€';
    
    const extraUsersItem = document.getElementById('previewExtraUsers');
    if (extraUserCost > 0) {
        document.getElementById('previewExtraPrice').textContent = '+' + extraUserCost.toFixed(2) + '€/utilisateur';
        extraUsersItem.style.display = 'flex';
    } else {
        extraUsersItem.style.display = 'none';
    }
    
    const materialItem = document.getElementById('previewMaterial');
    if (materialPrice > 0) {
        document.getElementById('previewMaterialPrice').textContent = materialPrice.toFixed(2) + '€/mois';
        materialItem.style.display = 'flex';
    } else {
        materialItem.style.display = 'none';
    }
}

// Event listeners
document.getElementById('modele_materiel_id').addEventListener('change', updateMaterialInfo);
document.getElementById('prix_base').addEventListener('input', updatePricingPreview);
document.getElementById('cout_utilisateur_supplementaire').addEventListener('input', updatePricingPreview);

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    toggleMaterialSection();
    updateMaterialInfo();
    updatePricingPreview();
});

// Validation du formulaire
document.getElementById('planForm').addEventListener('submit', function(e) {
    const typeSelect = document.getElementById('type_abonnement');
    const materialSelect = document.getElementById('modele_materiel_id');
    const basePrice = document.getElementById('prix_base');
    
    // Validation du matériel pour les types qui l'exigent
    if (['application_materiel', 'materiel_seul'].includes(typeSelect.value)) {
        if (!materialSelect.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un modèle de matériel pour ce type d\'abonnement.');
            materialSelect.focus();
            return;
        }
    }
    
    // Validation du prix
    if (!basePrice.value || parseFloat(basePrice.value) <= 0) {
        e.preventDefault();
        alert('Veuillez saisir un prix de base valide.');
        basePrice.focus();
        return;
    }
});
</script>

<?php } ?>