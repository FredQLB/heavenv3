<?php 
$pageTitle = $pageTitle ?? 'Ajouter un matériel';
require_once 'app/Views/layouts/main.php';

function renderContent() {
?>

<div class="material-create">
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
                <h1>Ajouter un matériel</h1>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/materials" class="material-form" id="materialForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Informations générales -->
            <div class="form-section">
                <h3>Informations générales</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="nom">Nom du matériel *</label>
                        <input type="text" id="nom" name="nom" required 
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                               placeholder="Ex: Casque VR Meta Quest 3">
                        <small class="form-help">Nom commercial du matériel</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"
                                  placeholder="Description détaillée du matériel, caractéristiques techniques..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
                               value="<?= htmlspecialchars($_POST['prix_mensuel'] ?? '') ?>"
                               placeholder="0.00">
                        <small class="form-help">Prix de location mensuel</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="depot_garantie">Dépôt de garantie (€) *</label>
                        <input type="number" id="depot_garantie" name="depot_garantie" 
                               min="0" step="0.01" required 
                               value="<?= htmlspecialchars($_POST['depot_garantie'] ?? '') ?>"
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
                            Matériel actif
                        </label>
                        <small class="form-help">Un matériel inactif n'est pas proposé dans les formules</small>
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
                    Ajouter le matériel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.material-create {
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

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .material-create {
        max-width: none;
        margin: 0;
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
}
</style>

<script>
function updatePricingPreview() {
    const monthlyPrice = parseFloat(document.getElementById('prix_mensuel').value) || 0;
    const deposit = parseFloat(document.getElementById('depot_garantie').value) || 0;
    
    const yearlyPrice = monthlyPrice * 12;
    const totalFirstYear = yearlyPrice + deposit;
    
    document.getElementById('previewMonthlyPrice').textContent = monthlyPrice.toFixed(2) + '€';
    document.getElementById('previewYearlyPrice').textContent = yearlyPrice.toFixed(2) + '€';
    document.getElementById('previewDeposit').textContent = deposit.toFixed(2) + '€';
    document.getElementById('previewTotal').textContent = totalFirstYear.toFixed(2) + '€';
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
});
</script>

<?php } ?>