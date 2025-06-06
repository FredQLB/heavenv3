<?php 
$pageTitle = $pageTitle ?? 'Nouveau client';
require_once 'app/Views/layouts/main.php';

function renderContent() {
?>

<div class="client-create">
    <!-- Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <a href="/clients" class="btn-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15,18 9,12 15,6"></polyline>
                    </svg>
                    Retour
                </a>
                <h1>Nouveau client</h1>
            </div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <form method="POST" action="/clients" class="client-form" id="clientForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Session::generateCsrfToken() ?>">
            
            <!-- Informations générales -->
            <div class="form-section">
                <h3>Informations générales</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="raison_sociale">Raison sociale *</label>
                        <input type="text" id="raison_sociale" name="raison_sociale" required 
                               value="<?= htmlspecialchars($_POST['raison_sociale'] ?? '') ?>"
                               placeholder="Ex: ACME Corporation">
                        <small class="form-help">Nom officiel de l'entreprise</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="email_facturation">Email de facturation *</label>
                        <input type="email" id="email_facturation" name="email_facturation" required 
                               value="<?= htmlspecialchars($_POST['email_facturation'] ?? '') ?>"
                               placeholder="facturation@example.com">
                        <small class="form-help">Adresse email pour l'envoi des factures</small>
                    </div>
                </div>
            </div>

            <!-- Adresse -->
            <div class="form-section">
                <h3>Adresse</h3>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="adresse">Adresse *</label>
                        <textarea id="adresse" name="adresse" required rows="3"
                                  placeholder="123 Rue de la Paix&#10;Bâtiment A, 3ème étage"><?= htmlspecialchars($_POST['adresse'] ?? '') ?></textarea>
                        <small class="form-help">Adresse complète sur plusieurs lignes si nécessaire</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="code_postal">Code postal *</label>
                        <input type="text" id="code_postal" name="code_postal" required 
                               value="<?= htmlspecialchars($_POST['code_postal'] ?? '') ?>"
                               placeholder="75001">
                        <small class="form-help">Code postal de l'adresse</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="ville">Ville *</label>
                        <input type="text" id="ville" name="ville" required 
                               value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>"
                               placeholder="Paris">
                        <small class="form-help">Ville de l'adresse</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="pays">Pays *</label>
                        <select id="pays" name="pays" required onchange="toggleTvaField()">
                            <option value="">Sélectionnez un pays</option>
                            <option value="France" <?= ($_POST['pays'] ?? '') === 'France' ? 'selected' : '' ?>>France</option>
                            <option value="Allemagne" <?= ($_POST['pays'] ?? '') === 'Allemagne' ? 'selected' : '' ?>>Allemagne</option>
                            <option value="Belgique" <?= ($_POST['pays'] ?? '') === 'Belgique' ? 'selected' : '' ?>>Belgique</option>
                            <option value="Espagne" <?= ($_POST['pays'] ?? '') === 'Espagne' ? 'selected' : '' ?>>Espagne</option>
                            <option value="Italie" <?= ($_POST['pays'] ?? '') === 'Italie' ? 'selected' : '' ?>>Italie</option>
                            <option value="Pays-Bas" <?= ($_POST['pays'] ?? '') === 'Pays-Bas' ? 'selected' : '' ?>>Pays-Bas</option>
                            <option value="Suisse" <?= ($_POST['pays'] ?? '') === 'Suisse' ? 'selected' : '' ?>>Suisse</option>
                            <option value="Luxembourg" <?= ($_POST['pays'] ?? '') === 'Luxembourg' ? 'selected' : '' ?>>Luxembourg</option>
                            <option value="Autre" <?= ($_POST['pays'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre</option>
                        </select>
                        <small class="form-help">Pays de l'entreprise</small>
                    </div>
                    
                    <div class="form-group" id="tvaGroup" style="display: none;">
                        <label for="numero_tva">Numéro TVA intracommunautaire</label>
                        <input type="text" id="numero_tva" name="numero_tva" 
                               value="<?= htmlspecialchars($_POST['numero_tva'] ?? '') ?>"
                               placeholder="Ex: FR12345678901">
                        <small class="form-help">Obligatoire pour les clients hors de France</small>
                    </div>
                </div>
            </div>

            <!-- Informations Stripe -->
            <div class="form-section">
                <h3>Intégration Stripe</h3>
                
                <div class="stripe-info">
                    <div class="stripe-notice">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 16v-4"></path>
                            <path d="M12 8h.01"></path>
                        </svg>
                        <div class="stripe-notice-content">
                            <strong>Création automatique sur Stripe</strong>
                            <p>Un compte client sera automatiquement créé sur Stripe lors de l'enregistrement. 
                               Cela permettra la gestion des abonnements et de la facturation.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Première configuration -->
            <div class="form-section">
                <h3>Configuration initiale</h3>
                
                <div class="config-options">
                    <div class="config-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="create_admin_user" value="1" 
                                   <?= ($_POST['create_admin_user'] ?? '1') ? 'checked' : '' ?>>
                            <span class="checkbox-custom"></span>
                            Créer automatiquement un utilisateur administrateur
                        </label>
                        <small class="form-help">Un email avec les identifiants sera envoyé à l'adresse de facturation</small>
                    </div>
                    
                    <div class="config-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_welcome_email" value="1" 
                                   <?= ($_POST['send_welcome_email'] ?? '1') ? 'checked' : '' ?>>
                            <span class="checkbox-custom"></span>
                            Envoyer un email de bienvenue
                        </label>
                        <small class="form-help">Email d'accueil avec les informations de connexion</small>
                    </div>
                </div>
            </div>

            <!-- Prochaines étapes -->
            <div class="form-section">
                <h3>Prochaines étapes</h3>
                
                <div class="next-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Création du client</div>
                            <div class="step-description">Le client sera créé dans la base de données et sur Stripe</div>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Ajout d'utilisateurs</div>
                            <div class="step-description">Vous pourrez créer des utilisateurs pour ce client</div>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">Configuration des abonnements</div>
                            <div class="step-description">Sélection des formules et création des abonnements</div>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <div class="step-title">Gestion des catégories</div>
                            <div class="step-description">Attribution des accès aux catégories de textures</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="/clients" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    Créer le client
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.client-create {
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

.client-form {
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
.form-group textarea,
.form-group select {
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 0.875rem;
    transition: var(--transition);
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-help {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
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
    min-width: 18px;
    border: 2px solid var(--border-color);
    border-radius: 3px;
    position: relative;
    transition: var(--transition);
    margin-top: 0.125rem;
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

.stripe-info {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1.5rem;
}

.stripe-notice {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    color: var(--info-color);
}

.stripe-notice-content {
    flex: 1;
}

.stripe-notice-content strong {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.stripe-notice-content p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.5;
}

.config-options {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.config-option {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.next-steps {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.step-number {
    width: 32px;
    height: 32px;
    min-width: 32px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.step-content {
    flex: 1;
    padding-top: 0.25rem;
}

.step-title {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.step-description {
    font-size: 0.875rem;
    color: var(--text-secondary);
    line-height: 1.5;
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
    .client-create {
        max-width: none;
        margin: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .client-form {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .stripe-notice {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .step-item {
        gap: 0.75rem;
    }
}
</style>

<script>
function toggleTvaField() {
    const paysSelect = document.getElementById('pays');
    const tvaGroup = document.getElementById('tvaGroup');
    const tvaInput = document.getElementById('numero_tva');
    
    if (paysSelect.value && paysSelect.value !== 'France') {
        tvaGroup.style.display = 'block';
        tvaInput.required = true;
    } else {
        tvaGroup.style.display = 'none';
        tvaInput.required = false;
        tvaInput.value = '';
    }
}

// Validation en temps réel de l'email
document.getElementById('email_facturation').addEventListener('blur', function() {
    const email = this.value.trim();
    if (email && !isValidEmail(email)) {
        this.style.borderColor = 'var(--error-color)';
        showFieldError(this, 'Veuillez saisir une adresse email valide');
    } else {
        clearFieldError(this);
    }
});

// Validation du numéro TVA
document.getElementById('numero_tva').addEventListener('blur', function() {
    const tva = this.value.trim();
    const pays = document.getElementById('pays').value;
    
    if (tva && pays !== 'France') {
        // Validation basique du format européen
        if (!/^[A-Z]{2}[A-Z0-9]+$/.test(tva)) {
            this.style.borderColor = 'var(--error-color)';
            showFieldError(this, 'Le format du numéro TVA n\'est pas valide (ex: FR12345678901)');
        } else {
            clearFieldError(this);
        }
    }
});

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = 'color: var(--error-color); font-size: 0.75rem; margin-top: 0.25rem;';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.style.borderColor = '';
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Validation du formulaire
document.getElementById('clientForm').addEventListener('submit', function(e) {
    const requiredFields = ['raison_sociale', 'email_facturation', 'adresse', 'code_postal', 'ville', 'pays'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.getElementById(fieldName);
        if (!field.value.trim()) {
            isValid = false;
            field.style.borderColor = 'var(--error-color)';
            showFieldError(field, 'Ce champ est obligatoire');
        }
    });
    
    // Validation email
    const emailField = document.getElementById('email_facturation');
    if (emailField.value && !isValidEmail(emailField.value)) {
        isValid = false;
        emailField.style.borderColor = 'var(--error-color)';
        showFieldError(emailField, 'Veuillez saisir une adresse email valide');
    }
    
    // Validation TVA si nécessaire
    const paysField = document.getElementById('pays');
    const tvaField = document.getElementById('numero_tva');
    if (paysField.value && paysField.value !== 'France' && !tvaField.value.trim()) {
        isValid = false;
        tvaField.style.borderColor = 'var(--error-color)';
        showFieldError(tvaField, 'Le numéro TVA est obligatoire pour les clients hors de France');
    }
    
    if (!isValid) {
        e.preventDefault();
        // Scroll vers la première erreur
        const firstError = document.querySelector('.field-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    toggleTvaField();
});
</script>

<?php } ?>