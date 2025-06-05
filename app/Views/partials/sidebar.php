<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($path) {
    global $currentPath;
    return $currentPath === $path ? 'active' : '';
}
?>

<aside class="admin-sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="/dashboard" class="nav-link <?= isActive('/dashboard') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9"></rect>
                        <rect x="14" y="3" width="7" height="5"></rect>
                        <rect x="14" y="12" width="7" height="9"></rect>
                        <rect x="3" y="16" width="7" height="5"></rect>
                    </svg>
                    <span class="nav-text">Tableau de bord</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/clients" class="nav-link <?= isActive('/clients') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="nav-text">Clients</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/users" class="nav-link <?= isActive('/users') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span class="nav-text">Utilisateurs</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/categories" class="nav-link <?= isActive('/categories') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="nav-text">Catégories</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/materials" class="nav-link <?= isActive('/materials') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <path d="M7 15h0M12 15h0M17 15h0"></path>
                    </svg>
                    <span class="nav-text">Matériel</span>
                </a>
            </li>

            <li class="nav-section">
                <span class="nav-section-title">Abonnements</span>
            </li>

            <li class="nav-item">
                <a href="/subscription-plans" class="nav-link <?= isActive('/subscription-plans') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span class="nav-text">Formules</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/subscriptions" class="nav-link <?= isActive('/subscriptions') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <span class="nav-text">Abonnements clients</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/invoices" class="nav-link <?= isActive('/invoices') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10,9 9,9 8,9"></polyline>
                    </svg>
                    <span class="nav-text">Factures</span>
                </a>
            </li>

            <li class="nav-section">
                <span class="nav-section-title">Système</span>
            </li>

            <li class="nav-item">
                <a href="/settings" class="nav-link <?= isActive('/settings') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 6v6"></path>
                        <path d="M21 12h-6m-6 0H3"></path>
                    </svg>
                    <span class="nav-text">Paramètres</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="/logs" class="nav-link <?= isActive('/logs') ?>">
                    <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14,2 14,8 20,8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span class="nav-text">Logs</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>