<?php 
$pageTitle = $pageTitle ?? 'Modifier le matériel';
require_once 'app/Views/layouts/main.php';

function renderContent() {
    global $material;
?>

<div class="material-edit">
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
                <h1>Modifier le matériel</h1>
            </div>
            <div class="page-header-right">
                <a href="/materials/<?= $material['id'] ?>" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    Voir les détails
                </a>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/materials/<?= $material['id'] ?>/update" class="material-form" id="materialForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Informations générales -->
            <div class="form-section">
                <h3>Informations générales</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="nom">Nom du matériel *</label>
                        <input type="text" id="nom" name="nom" required 
                               value="<?= htmlspecialchars($_POST['nom'] ?? $material['nom']) ?>"
                               placeholder="Ex: Casque VR Meta Quest 3">
                        <small class="form-help">Nom commercial du matériel</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Description détaillée du matériel, caractéristiques techniques..."><?= htmlspecialchars($_POST['description'] ?? $material['description'] ?? '') ?></textarea>
                        <small class="form-help">Description optionnelle du matériel</small>
                    </div>
                </div>
            </div>

            <!-- Tarification -->
            <div class="form-section">
                <h3>Tarification</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="prix_mensuel">Prix mensuel (€) *</label>
                        <input type="number" id="prix_mensuel" name="prix_mensuel" 
                               min="0" step="0.01" required 
                               value="<?= htmlspecialchars($_POST['prix_mensuel'] ?? $material['prix_mensuel']) ?>"
                               placeholder="0.00">
                        <small class="form-help">Prix de location mensuel</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="depot_garantie">Dépôt de garantie (€) *</label>
                        <input type="number" id="depot_garantie" name="depot_garantie" 
                               min="0" step="0.01" required 
                               value="<?= htmlspecialchars($_POST['depot_garantie'] ?? $material['depot_garantie']) ?>"
                               placeholder="0.00">
                        <small class="form-help">Montant du dépôt de garantie</small>
                    </div>
                </div>
                
                <div class="pricing-preview" id="pricingPreview">
                    <h4>Aperçu tarifaire</h4>
                    <div class="preview-content">
                        <div class="preview-item">
                            <span class="preview-label">Prix mensuel :</span>
                            <span class="preview-value" id="previewMonthlyPrice">0,00€</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Prix annuel :</span>
                            <span class="preview-value" id="previewYearlyPrice">0,00€</span>
                        </div>
                        <div class="preview-item">
                            <span class="preview-label">Dépôt de garantie :</span>
                            <span class="preview-value" id="previewDeposit">0,00€</span>
                        </div>
                        <div class="preview-item total">
                            <span class="preview-label">Coût total première année :</span>
                            <span class="preview-value" id="previewTotal">0,00€</span>
                        </div>
                    </div>
                </div>
                
                <!-- Warning si changement de prix -->
                <div class="price-warning" id="priceWarning" style="display: none;">
                    <div class="warning-content">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <div class="warning-text">
                            <strong>Attention :</strong> La modification des prix affectera les nouvelles formules utilisant ce matériel.
                            Les formules existantes conserveront leurs prix actuels.
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
                                   <?= ($_POST['actif'] ?? $material['actif']) ? 'checked' : '' ?>>
                            <span class="checkbox-custom"></span>
                            Matériel actif
                        </label>
                        <small class="form-help">Un matériel inactif n'est pas proposé dans les nouvelles formules</small>
                    </div>
                </div>
                
                <?php if (!$material['actif']): ?>
                <div class="status-warning">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Ce matériel est actuellement inactif et n'est pas proposé dans les nouvelles formules.
                </div>
                <?php endif; ?>
            </div>

            <!-- Informations de création -->
            <div class="form-section">
                <h3>Informations</h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Date de création :</span>
                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($material['date_creation'])) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ID matériel :</span>
                        <span class="info-value">#<?= $material['id'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="/materials" class="btn btn-secondary">Annuler</a>
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
.material-edit {
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

.page-header-right {
    display: flex;
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

.material-form {
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
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    transition: var(--transition);
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
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

.preview-item.total {
    padding-top: 0.75rem;
    border-top: 1px solid var(--border-color);
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

.price-warning,
.status-warning {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    color: #92400e;
    padding: 1rem;
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    margin-top: 1rem;
}

.warning-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.warning-text {
    flex: 1;
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
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
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

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .material-edit {
        max-width: none;
        margin: 0;
    }
    
    .page-header-content {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .page-header-right {
        justify-content: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .material-form {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Valeurs originales pour détecter les changements
const originalPrice = <?= $material['prix_mensuel'] ?>;
const originalDeposit = <?= $material['depot_garantie'] ?>;

function updatePricingPreview() {
    const monthlyPrice = parseFloat(document.getElementById('prix_mensuel').value) || 0;
    const deposit = parseFloat(document.getElementById('depot_garantie').value) || 0;
    
    const yearlyPrice = monthlyPrice * 12;
    const totalFirstYear = yearlyPrice + deposit;
    
    document.getElementById('previewMonthlyPrice').textContent = monthlyPrice.toFixed(2) + '€';
    document.getElementById('previewYearlyPrice').textContent = yearlyPrice.toFixed(2) + '€';
    document.getElementById('previewDeposit').textContent = deposit.toFixed(2) + '€';
    document.getElementById('previewTotal').textContent = totalFirstYear.toFixed(2) + '€';
    
    // Afficher l'avertissement si les prix ont changé
    const priceChanged = (monthlyPrice !== originalPrice) || (deposit !== originalDeposit);
    const warningDiv = document.getElementById('priceWarning');
    
    if (priceChanged) {
        warningDiv.style.display = 'block';
    } else {
        warningDiv.style.display = 'none';
    }
}

// Event listeners
document.getElementById('prix_mensuel').addEventListener('input', updatePricingPreview);
document.getElementById('depot_garantie').addEventListener('input', updatePricingPreview);

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    updatePricingPreview();
});

// Validation du formulaire
document.getElementById('materialForm').addEventListener('submit', function(e) {
    const nom = document.getElementById('nom');
    const prixMensuel = document.getElementById('prix_mensuel');
    const depotGarantie = document.getElementById('depot_garantie');
    
    // Validation du nom
    if (!nom.value.trim()) {
        e.preventDefault();
        alert('Veuillez saisir le nom du matériel.');
        nom.focus();
        return;
    }
    
    // Validation du prix mensuel
    if (!prixMensuel.value || parseFloat(prixMensuel.value) <= 0) {
        e.preventDefault();
        alert('Veuillez saisir un prix mensuel valide.');
        prixMensuel.focus();
        return;
    }
    
    // Validation du dépôt de garantie
    if (!depotGarantie.value || parseFloat(depotGarantie.value) < 0) {
        e.preventDefault();
        alert('Veuillez saisir un dépôt de garantie valide.');
        depotGarantie.focus();
        return;
    }
    
    // Confirmation si changement de prix significatif
    const priceChanged = (parseFloat(prixMensuel.value) !== originalPrice) || 
                        (parseFloat(depotGarantie.value) !== originalDeposit);
    
    if (priceChanged) {
        if (!confirm('Vous avez modifié les prix de ce matériel. Cela affectera les nouvelles formules créées. Confirmer les modifications ?')) {
            e.preventDefault();
            return;
        }
    }
});
</script>

<?php } ?>