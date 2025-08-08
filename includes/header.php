<?php
if (!isset($lang)) {
    require_once __DIR__ . '/../classes/Language.php';
    $lang = Language::getInstance();
}

if (!isset($user)) {
    require_once __DIR__ . '/../classes/User.php';
    $user = new User();
}

// Charger les slugs pour la traduction des URLs
$slugs = [];
if (file_exists(__DIR__ . '/../lang/slugs.php')) {
    $slugs = include __DIR__ . '/../lang/slugs.php';
}

$isLoggedIn = $user->isLoggedIn();
$currentUser = $isLoggedIn ? $user->getCurrentUser() : null;

// Déterminer la page courante à partir des variables globales du routeur
$currentPageKey = isset($currentPage) ? $currentPage : 'home';
?>

<header class="site-header" id="siteHeader">
    <div class="header-container">
        <div class="header-brand">
            <a href="/<?php echo $lang->getCurrentLanguage(); ?>/" class="logo-link">
                <div class="logo">
                    <img src="/images/logo.png" alt="PrestaCapi" class="logo-image">
                    <span class="logo-text">PrestaCapi</span>
                </div>
                <div class="tagline"><?php echo $lang->get('tagline'); ?></div>
            </a>
        </div>
        
        <nav class="main-navigation" id="mainNav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="/<?php echo $lang->getCurrentLanguage(); ?>/" class="nav-link <?php echo $currentPageKey === 'home' ? 'active' : ''; ?>">
                        <?php echo $lang->get('home'); ?>
                    </a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a href="<?php echo generateLocalizedUrl('dashboard'); ?>" class="nav-link <?php echo $currentPageKey === 'dashboard' ? 'active' : ''; ?>">
                            <?php echo $lang->get('dashboard'); ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="nav-link <?php echo $currentPageKey === 'loan_request' ? 'active' : ''; ?>">
                            <?php echo $lang->get('loan_request') ?: 'Demande de prêt'; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a href="<?php echo generateLocalizedUrl('about'); ?>" class="nav-link <?php echo $currentPageKey === 'about' ? 'active' : ''; ?>">
                        <?php echo $lang->get('about'); ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo generateLocalizedUrl('blog'); ?>" class="nav-link <?php echo $currentPageKey === 'blog' ? 'active' : ''; ?>">
                        <?php echo $lang->get('blog'); ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo generateLocalizedUrl('testimonials'); ?>" class="nav-link <?php echo $currentPageKey === 'testimonials' ? 'active' : ''; ?>">
                        <?php echo $lang->get('testimonials'); ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo generateLocalizedUrl('contact'); ?>" class="nav-link <?php echo $currentPageKey === 'contact' ? 'active' : ''; ?>">
                        <?php echo $lang->get('contact'); ?>
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="header-actions">
            <div class="language-selector-wrapper">
                <button class="language-toggle" onclick="toggleLanguageSelector()" aria-label="Changer de langue">
                    <img src="/images/flags/<?php echo $lang->getCountryCode(); ?>.svg" 
                         alt="<?php echo $lang->getLanguageName(); ?>" 
                         class="current-flag">
                    <span class="current-lang"><?php echo strtoupper($lang->getCurrentLanguage()); ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                
                <div class="language-dropdown" id="languageDropdown">
                    <?php foreach ($lang->getSupportedLanguages() as $langCode): ?>
                        <?php if ($langCode !== $lang->getCurrentLanguage()): ?>
                            <?php 
                            // Générer l'URL correcte pour cette langue
                            $languageUrl = generateLanguageUrl(
                                $langCode, 
                                $lang->getCurrentLanguage(), 
                                $_SERVER['REQUEST_URI'], 
                                $slugs
                            );
                            ?>
                            <a href="<?php echo $languageUrl; ?>" 
                               class="language-option" 
                               hreflang="<?php echo $langCode; ?>">
                                <img src="/images/flags/<?php echo $lang->getCountryCode($langCode); ?>.svg" 
                                     alt="<?php echo $lang->getLanguageName($langCode); ?>" 
                                     class="flag-icon">
                                <span class="language-name"><?php echo $lang->getLanguageName($langCode); ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($isLoggedIn): ?>
                <div class="user-menu-wrapper">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()" aria-label="Menu utilisateur">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($currentUser['first_name']); ?></span>
                            <span class="user-balance"><?php echo $lang->formatCurrency($currentUser['balance']); ?></span>
                        </div>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <div class="user-avatar-large">
                                <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <div class="user-full-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                <div class="user-balance-large"><?php echo $lang->formatCurrency($currentUser['balance']); ?></div>
                            </div>
                        </div>
                        
                        <div class="user-dropdown-body">
                            <a href="<?php echo generateLocalizedUrl('dashboard'); ?>" class="user-menu-item">
                                <span class="menu-icon">📊</span>
                                <?php echo $lang->get('dashboard'); ?>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('profile'); ?>" class="user-menu-item">
                                <span class="menu-icon">👤</span>
                                <?php echo $lang->get('profile'); ?>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="user-menu-item">
                                <span class="menu-icon">📝</span>
                                <?php echo $lang->get('loan_request') ?: 'Demande de prêt'; ?>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('withdrawal'); ?>" class="user-menu-item">
                                <span class="menu-icon">💸</span>
                                <?php echo $lang->get('withdrawal') ?: 'Retrait'; ?>
                            </a>
                            
                            <div class="user-menu-divider"></div>
                            
                            <a href="/ajax/logout.php" class="user-menu-item logout-item">
                                <span class="menu-icon">🚪</span>
                                <?php echo $lang->get('logout'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="notifications-wrapper">
                    <button class="notifications-toggle" onclick="toggleNotifications()" aria-label="Notifications">
                        <span class="notification-icon">🔔</span>
                        <span class="notification-badge" id="notificationBadge">3</span>
                    </button>
                    
                    <div class="notifications-dropdown" id="notificationsDropdown">
                        <div class="notifications-header">
                            <h3>Notifications</h3>
                            <button class="mark-all-read" onclick="markAllNotificationsRead()">Tout marquer lu</button>
                        </div>
                        
                        <div class="notifications-list" id="notificationsList">
                            <div class="loading">Chargement...</div>
                        </div>
                        
                        <div class="notifications-footer">
                            <a href="<?php echo generateLocalizedUrl('dashboard'); ?>#notifications">Voir toutes</a>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="auth-actions">
                    <a href="<?php echo generateLocalizedUrl('login'); ?>" class="btn-login">
                        <?php echo $lang->get('login'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('register'); ?>" class="btn btn-primary">
                        <?php echo $lang->get('register'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Menu mobile">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
    
    <?php if ($isLoggedIn): ?>
        <div class="header-quick-stats">
            <div class="header-container">
                <div class="quick-stats">
                    <div class="stat-item">
                        <span class="stat-label">Solde</span>
                        <span class="stat-value"><?php echo $lang->formatCurrency($currentUser['balance']); ?></span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">Demandes</span>
                        <span class="stat-value" id="totalLoans">-</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-label">En attente</span>
                        <span class="stat-value pending" id="pendingLoans">-</span>
                    </div>
                    
                    <div class="quick-action">
                        <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-sm">
                            Nouvelle demande
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</header>

<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>

<style>
.site-header {
    background: white;
    box-shadow: 0 2px 20px rgba(31, 59, 115, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 70px;
}

.header-brand {
    display: flex;
    align-items: center;
}

.logo-link {
    text-decoration: none;
    color: inherit;
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.logo-image {
    height: 40px;
    width: auto;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--primary-color);
}

.tagline {
    font-size: 0.75rem;
    color: #666;
    margin-left: 3rem;
}

.main-navigation {
    display: flex;
    align-items: center;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
    padding: 0;
}

.nav-link {
    text-decoration: none;
    color: var(--accent-2);
    font-weight: 500;
    padding: 0.5rem 0;
    position: relative;
    transition: color 0.3s ease;
}

.nav-link:hover,
.nav-link.active {
    color: var(--primary-color);
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--accent-1);
    border-radius: 1px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.language-selector-wrapper,
.user-menu-wrapper,
.notifications-wrapper {
    position: relative;
}

.language-toggle,
.user-menu-toggle,
.notifications-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border: none;
    background: none;
    cursor: pointer;
    border-radius: var(--border-radius);
    transition: background-color 0.3s ease;
}

.language-toggle:hover,
.user-menu-toggle:hover,
.notifications-toggle:hover {
    background-color: var(--secondary-color);
}

.current-flag,
.flag-icon {
    width: 20px;
    height: 15px;
    object-fit: cover;
    border-radius: 2px;
}

.current-lang {
    font-weight: 600;
    font-size: 0.875rem;
}

.dropdown-arrow {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.language-dropdown,
.user-dropdown,
.notifications-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    padding: 0.5rem;
    min-width: 200px;
    opacity: 0;
    transform: translateY(-10px);
    pointer-events: none;
    transition: all 0.3s ease;
    z-index: 1001;
}

.language-dropdown.show,
.user-dropdown.show,
.notifications-dropdown.show {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.language-option,
.user-menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    text-decoration: none;
    color: var(--accent-2);
    border-radius: var(--border-radius);
    transition: background-color 0.3s ease;
}

.language-option:hover,
.user-menu-item:hover {
    background-color: var(--secondary-color);
    color: var(--primary-color);
}

.user-avatar,
.user-avatar-large {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.user-avatar-large {
    width: 50px;
    height: 50px;
    font-size: 1rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--accent-2);
}

.user-balance,
.user-balance-large {
    font-size: 0.75rem;
    color: var(--accent-1);
    font-weight: 600;
}

.user-balance-large {
    font-size: 0.875rem;
}

.user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--secondary-color);
    margin-bottom: 0.5rem;
}

.user-full-name {
    font-weight: 600;
    color: var(--primary-color);
}

.user-email {
    font-size: 0.75rem;
    color: #666;
}

.user-menu-divider {
    height: 1px;
    background: var(--secondary-color);
    margin: 0.5rem 0;
}

.logout-item {
    color: var(--error-color);
}

.logout-item:hover {
    background-color: rgba(229, 57, 53, 0.1);
}

.notifications-toggle {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--error-color);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--secondary-color);
}

.notifications-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.mark-all-read {
    background: none;
    border: none;
    color: var(--accent-1);
    font-size: 0.75rem;
    cursor: pointer;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 0.5rem;
}

.notifications-footer {
    padding: 1rem;
    text-align: center;
    border-top: 1px solid var(--secondary-color);
}

.notifications-footer a {
    color: var(--accent-1);
    text-decoration: none;
    font-size: 0.875rem;
}

.auth-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-login {
    text-decoration: none;
    color: var(--primary-color);
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    transition: all 0.3s ease;
}

.btn-login:hover {
    background-color: var(--secondary-color);
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
}

.hamburger-line {
    width: 24px;
    height: 2px;
    background: var(--accent-2);
    transition: all 0.3s ease;
}

.mobile-menu-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.header-quick-stats {
    background: var(--secondary-color);
    border-top: 1px solid rgba(31, 59, 115, 0.1);
}

.quick-stats {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 0.75rem 0;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-label {
    font-size: 0.75rem;
    color: #666;
}

.stat-value {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.875rem;
}

.stat-value.pending {
    color: var(--warning-color);
}

.quick-action {
    margin-left: auto;
}

@media (max-width: 1024px) {
    .tagline {
        display: none;
    }
    
    .nav-menu {
        gap: 1.5rem;
    }
    
    .user-info {
        display: none;
    }
    
    .header-quick-stats {
        display: none;
    }
}

@media (max-width: 768px) {
    .main-navigation {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .language-selector-wrapper .current-lang {
        display: none;
    }
    
    .header-actions {
        gap: 0.5rem;
    }
}

.header-scrolled {
    box-shadow: 0 4px 20px rgba(31, 59, 115, 0.15);
}

.header-scrolled .logo-text {
    font-size: 1.25rem;
}

.loading {
    text-align: center;
    padding: 2rem;
    color: #666;
}
</style>

<script>
function toggleLanguageSelector() {
    const dropdown = document.getElementById('languageDropdown');
    const isShown = dropdown.classList.contains('show');
    
    closeAllDropdowns();
    
    if (!isShown) {
        dropdown.classList.add('show');
    }
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    const isShown = dropdown.classList.contains('show');
    
    closeAllDropdowns();
    
    if (!isShown) {
        dropdown.classList.add('show');
    }
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    const isShown = dropdown.classList.contains('show');
    
    closeAllDropdowns();
    
    if (!isShown) {
        dropdown.classList.add('show');
        loadNotifications();
    }
}

function toggleMobileMenu() {
    // Implémentation du menu mobile
    const overlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    
    if (overlay.style.display === 'block') {
        overlay.style.display = 'none';
        body.style.overflow = '';
    } else {
        overlay.style.display = 'block';
        body.style.overflow = 'hidden';
    }
}

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.language-dropdown, .user-dropdown, .notifications-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

function loadNotifications() {
    const notificationsList = document.getElementById('notificationsList');
    
    fetch('/ajax/get-notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notificationsList.innerHTML = data.html;
                updateNotificationBadge(data.unread_count);
            } else {
                notificationsList.innerHTML = '<div class="empty-state">Aucune notification</div>';
            }
        })
        .catch(error => {
            notificationsList.innerHTML = '<div class="error">Erreur de chargement</div>';
        });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function markAllNotificationsRead() {
    fetch('/ajax/mark-all-notifications-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.language-selector-wrapper, .user-menu-wrapper, .notifications-wrapper')) {
        closeAllDropdowns();
    }
});

window.addEventListener('scroll', function() {
    const header = document.getElementById('siteHeader');
    if (window.scrollY > 50) {
        header.classList.add('header-scrolled');
    } else {
        header.classList.remove('header-scrolled');
    }
});

<?php if ($isLoggedIn): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadQuickStats();
});

function loadQuickStats() {
    fetch('/ajax/get-dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalLoans').textContent = data.stats.total_loans || '0';
                document.getElementById('pendingLoans').textContent = data.stats.pending_loans || '0';
            }
        });
}
<?php endif; ?>
</script>