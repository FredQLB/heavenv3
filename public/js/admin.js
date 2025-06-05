// Variables globales
let sidebarOpen = true;
let currentDropdown = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeDropdowns();
    initializeAlerts();
    initializeForms();
    
    // Auto-fermeture des alertes après 5 secondes
    setTimeout(autoCloseAlerts, 5000);
});

// Gestion de la sidebar
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Fermer la sidebar sur mobile lors du clic sur un lien
    if (window.innerWidth <= 1024) {
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
        });
    }
    
    // Gérer le redimensionnement de la fenêtre
    window.addEventListener('resize', handleResize);
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebarOpen = !sidebarOpen;
        
        if (window.innerWidth <= 1024) {
            // Mode mobile/tablette
            sidebar.classList.toggle('show');
        } else {
            // Mode desktop
            if (sidebarOpen) {
                sidebar.style.transform = 'translateX(0)';
                mainContent.style.marginLeft = '280px';
            } else {
                sidebar.style.transform = 'translateX(-100%)';
                mainContent.style.marginLeft = '0';
            }
        }
    }
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebarOpen = false;
        
        if (window.innerWidth <= 1024) {
            sidebar.classList.remove('show');
        } else {
            sidebar.style.transform = 'translateX(-100%)';
            mainContent.style.marginLeft = '0';
        }
    }
}

function handleResize() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        if (window.innerWidth > 1024) {
            // Revenir au mode desktop
            sidebar.classList.remove('show');
            sidebar.style.transform = sidebarOpen ? 'translateX(0)' : 'translateX(-100%)';
            mainContent.style.marginLeft = sidebarOpen ? '280px' : '0';
        } else {
            // Mode mobile/tablette
            sidebar.style.transform = '';
            mainContent.style.marginLeft = '0';
        }
    }
}

// Gestion des dropdowns
function initializeDropdowns() {
    document.addEventListener('click', function(e) {
        // Fermer tous les dropdowns si on clique ailleurs
        if (!e.target.closest('.header-item')) {
            closeAllDropdowns();
        }
    });
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    toggleDropdown(dropdown, 'notifications');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    toggleDropdown(dropdown, 'userMenu');
}

function toggleDropdown(dropdown, type) {
    if (!dropdown) return;
    
    // Fermer les autres dropdowns
    if (currentDropdown && currentDropdown !== type) {
        closeAllDropdowns();
    }
    
    const isShown = dropdown.classList.contains('show');
    
    if (isShown) {
        dropdown.classList.remove('show');
        currentDropdown = null;
    } else {
        dropdown.classList.add('show');
        currentDropdown = type;
    }
}

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.notification-dropdown, .user-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
    currentDropdown = null;
}

// Gestion des alertes
function initializeAlerts() {
    const alerts = document.querySelectorAll('[data-alert]');
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.alert-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => closeAlert(closeBtn));
        }
    });
}

function closeAlert(button) {
    const alert = button.closest('[data-alert]');
    if (alert) {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

function autoCloseAlerts() {
    const alerts = document.querySelectorAll('[data-alert]');
    alerts.forEach(alert => {
        // Ne pas fermer automatiquement les alertes d'erreur
        if (!alert.classList.contains('alert-error')) {
            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) {
                setTimeout(() => closeAlert(closeBtn), Math.random() * 2000);
            }
        }
    });
}

function showAlert(type, message) {
    const alertsContainer = document.querySelector('.alerts-container') || createAlertsContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.setAttribute('data-alert', '');
    
    const icon = getAlertIcon(type);
    
    alert.innerHTML = `
        <div class="alert-content">
            <div class="alert-icon">${icon}</div>
            <div class="alert-message">${message}</div>
        </div>
        <button type="button" class="alert-close" onclick="closeAlert(this)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    `;
    
    alertsContainer.appendChild(alert);
    
    // Animation d'apparition
    setTimeout(() => {
        alert.style.opacity = '1';
        alert.style.transform = 'translateY(0)';
    }, 10);
    
    // Auto-fermeture après 5 secondes (sauf pour les erreurs)
    if (type !== 'error') {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.alert-close');
            if (closeBtn) closeAlert(closeBtn);
        }, 5000);
    }
}

function createAlertsContainer() {
    const container = document.createElement('div');
    container.className = 'alerts-container';
    
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        contentWrapper.insertBefore(container, contentWrapper.firstChild);
    } else {
        document.body.appendChild(container);
    }
    
    return container;
}

function getAlertIcon(type) {
    const icons = {
        success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"></polyline></svg>',
        error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        warning: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>'
    };
    return icons[type] || icons.info;
}

// Gestion des formulaires
function initializeForms() {
    // Validation en temps réel
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearFieldError);
        });
        
        form.addEventListener('submit', validateForm);
    });
}

function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    
    clearFieldError(e);
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Ce champ est obligatoire');
        return false;
    }
    
    if (field.type === 'email' && value && !isValidEmail(value)) {
        showFieldError(field, 'Veuillez saisir une adresse email valide');
        return false;
    }
    
    if (field.type === 'password' && value && value.length < 6) {
        showFieldError(field, 'Le mot de passe doit contenir au moins 6 caractères');
        return false;
    }
    
    return true;
}

function validateForm(e) {
    const form = e.target;
    const fields = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    fields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showAlert('error', 'Veuillez corriger les erreurs dans le formulaire');
    }
}

function showFieldError(field, message) {
    clearFieldError({ target: field });
    
    field.style.borderColor = 'var(--error-color)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = 'color: var(--error-color); font-size: 0.75rem; margin-top: 0.25rem;';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(e) {
    const field = e.target;
    field.style.borderColor = '';
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Utilitaires de confirmation
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function confirmDelete(itemName, deleteUrl) {
    const message = `Êtes-vous sûr de vouloir supprimer "${itemName}" ?\n\nCette action est irréversible.`;
    
    if (confirm(message)) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteUrl;
        
        // Ajouter le token CSRF s'il existe
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = csrfToken.getAttribute('content');
            form.appendChild(tokenInput);
        }
        
        // Ajouter la méthode DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Gestion des tableaux
function initializeTables() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Ajouter la fonctionnalité de tri si nécessaire
        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(table, header));
        });
    });
}

function sortTable(table, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const currentOrder = header.dataset.order || 'asc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    // Réinitialiser les autres en-têtes
    table.querySelectorAll('th[data-sortable]').forEach(th => {
        th.removeAttribute('data-order');
        th.classList.remove('sorted-asc', 'sorted-desc');
    });
    
    // Marquer l'en-tête actuel
    header.dataset.order = newOrder;
    header.classList.add(`sorted-${newOrder}`);
    
    // Trier les lignes
    rows.sort((a, b) => {
        const aVal = a.children[columnIndex].textContent.trim();
        const bVal = b.children[columnIndex].textContent.trim();
        
        // Essayer de convertir en nombre
        const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
        
        let comparison = 0;
        if (!isNaN(aNum) && !isNaN(bNum)) {
            comparison = aNum - bNum;
        } else {
            comparison = aVal.localeCompare(bVal);
        }
        
        return newOrder === 'asc' ? comparison : -comparison;
    });
    
    // Réappliquer les lignes triées
    rows.forEach(row => tbody.appendChild(row));
}

// Gestion des modales
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Animation d'ouverture
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
}

// Fermer la modale en cliquant sur l'overlay
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            closeModal(modal.id);
        }
    }
});

// Fermer la modale avec Échap
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// Gestion du loading
function showLoading(element) {
    if (!element) return;
    
    element.disabled = true;
    element.style.position = 'relative';
    element.style.color = 'transparent';
    
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        color: inherit;
    `;
    
    element.appendChild(spinner);
}

function hideLoading(element) {
    if (!element) return;
    
    element.disabled = false;
    element.style.color = '';
    
    const spinner = element.querySelector('.loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

// Animation de rotation pour le spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Gestion des notifications en temps réel (WebSocket)
function initializeNotifications() {
    // Placeholder pour les notifications en temps réel
    // Ici vous pourriez initialiser une connexion WebSocket
    
    // Simuler des notifications pour la démo
    setTimeout(() => {
        updateNotificationBadge(3);
    }, 2000);
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Gestion de la recherche en temps réel
function initializeSearch() {
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(input.value, input.dataset.search);
            }, 300);
        });
    });
}

function performSearch(query, target) {
    // Implémentation de la recherche selon le contexte
    console.log(`Recherche "${query}" dans ${target}`);
}

// Gestion des raccourcis clavier
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K pour ouvrir la recherche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('[data-search]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Échap pour fermer les dropdowns
    if (e.key === 'Escape') {
        closeAllDropdowns();
    }
});

// Fonctions utilitaires pour les données
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR');
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

function formatNumber(number) {
    return new Intl.NumberFormat('fr-FR').format(number);
}

// Gestion des uploads de fichiers
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', handleFileUpload);
    });
}

function handleFileUpload(e) {
    const input = e.target;
    const files = input.files;
    
    if (files.length > 0) {
        const file = files[0];
        
        // Vérifier la taille du fichier (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('error', 'Le fichier est trop volumineux (max 5MB)');
            input.value = '';
            return;
        }
        
        // Afficher le nom du fichier
        const fileName = input.parentNode.querySelector('.file-name');
        if (fileName) {
            fileName.textContent = file.name;
        }
    }
}

// Initialisation complète
function initializeApp() {
    initializeTables();
    initializeSearch();
    initializeFileUploads();
    initializeNotifications();
    
    console.log('Application Cover AR Admin initialisée');
}

// Attendre que le DOM soit chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}

// Fonctions globales pour l'utilisation dans les vues
window.toggleSidebar = toggleSidebar;
window.toggleNotifications = toggleNotifications;
window.toggleUserMenu = toggleUserMenu;
window.closeAlert = closeAlert;
window.showAlert = showAlert;
window.confirmDelete = confirmDelete;
window.confirmAction = confirmAction;
window.openModal = openModal;
window.closeModal = closeModal;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.formatCurrency = formatCurrency;
window.formatNumber = formatNumber;