<?php use App\Helpers\Session; ?>

<header class="admin-header">
    <div class="header-content">
        <div class="header-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="header-title">
                <h1>Cover AR</h1>
                <span class="subtitle">Interface d'administration</span>
            </div>
        </div>

        <div class="header-right">
            <div class="header-actions">
                <!-- Notifications -->
                <div class="header-item">
                    <button class="notification-btn" onclick="toggleNotifications()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                        </div>
                        <div class="notification-list">
                            <div class="notification-item">
                                <div class="notification-content">
                                    <div class="notification-title">Nouvel abonnement</div>
                                    <div class="notification-text">Un nouveau client s'est abonné</div>
                                    <div class="notification-time">Il y a 2 heures</div>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-content">
                                    <div class="notification-title">Abonnement expirant</div>
                                    <div class="notification-text">3 abonnements expirent cette semaine</div>
                                    <div class="notification-time">Il y a 1 jour</div>
                                </div>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="#" class="notification-link">Voir toutes les notifications</a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="header-item">
                    <div class="user-menu">
                        <button class="user-btn" onclick="toggleUserMenu()">
                            <div class="user-avatar">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?= Session::getUserName() ?></div>
                                <div class="user-role"><?= Session::getUserType() ?></div>
                            </div>
                            <svg class="chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6,9 12,15 18,9"></polyline>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <div class="user-dropdown-info">
                                    <div class="user-dropdown-name"><?= Session::getUserName() ?></div>
                                    <div class="user-dropdown-email"><?= Session::get('user_email') ?></div>
                                </div>
                            </div>
                            <div class="user-dropdown-menu">
                                <a href="/profile" class="user-dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Mon profil
                                </a>
                                <a href="/settings" class="user-dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="3"></circle>
                                        <path d="M12 1v6m0 6v6"></path>
                                        <path d="M21 12h-6m-6 0H3"></path>
                                    </svg>
                                    Paramètres
                                </a>
                                <hr class="user-dropdown-divider">
                                <a href="/logout" class="user-dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16,17 21,12 16,7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    Se déconnecter
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>