<?php
if (!isset($lang)) {
    require_once __DIR__ . '/../classes/Language.php';
    $lang = Language::getInstance();
}

if (!isset($user)) {
    require_once __DIR__ . '/../classes/User.php';
    $user = new User();
}

$slugs = [];
if (file_exists(__DIR__ . '/../lang/slugs.php')) {
    $slugs = include __DIR__ . '/../lang/slugs.php';
}

require_once __DIR__ . '/../config/app.php';

$isLoggedIn = $user->isLoggedIn();
$currentUser = $isLoggedIn ? $user->getCurrentUser() : null;

$currentPageKey = isset($currentPage) ? $currentPage : 'home';
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/profile.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/documents.css">
    <link rel="stylesheet" href="/css/footer.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/loan-request.css">
    <link rel="stylesheet" href="/css/withdrawal.css">
    <link rel="stylesheet" href="/css/blog.css">
    <link rel="stylesheet" href="/css/about.css">
</head>

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
                            <?php echo $lang->get('loan_request'); ?>
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

                <?php if (!$isLoggedIn): ?>
                    <li class="nav-item mobile-auth-item auth-divider"></li>
                    <li class="nav-item mobile-auth-item">
                         <a href="<?php echo generateLocalizedUrl('login'); ?>" class="btn-login">
                            <?php echo $lang->get('login'); ?>
                        </a>
                    </li>
                    <li class="nav-item mobile-auth-item">
                        <a href="<?php echo generateLocalizedUrl('register'); ?>" class="btn btn-primary">
                            <?php echo $lang->get('register'); ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="header-actions">
            <?php if ($isLoggedIn): ?>
                <div class="user-menu-wrapper">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()" aria-label="<?php echo $lang->get('header_user_menu_label'); ?>">
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
                                <?php echo $lang->get('loan_request'); ?>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('withdrawal'); ?>" class="user-menu-item">
                                <span class="menu-icon">💸</span>
                                <?php echo $lang->get('withdrawal'); ?>
                            </a>
                            
                            <div class="user-menu-divider"></div>
                            
                            <a href="/ajax/logout.php" class="user-menu-item logout-item">
                                <span class="menu-icon">🚪</span>
                                <?php echo $lang->get('logout'); ?>
                            </a>
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
            
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="<?php echo $lang->get('header_mobile_menu_label'); ?>">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
    </div>
    
    <div class="lang-selector-bar"> <div class="language-selector-wrapper">
            <button class="language-toggle" onclick="toggleLanguageSelector()" aria-label="<?php echo $lang->get('header_change_language_label'); ?>">
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
    </div>
</header>

<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

<script>
// Le code JavaScript reste inchangé
function toggleLanguageSelector() {
    const dropdown = document.getElementById('languageDropdown');
    const isShown = dropdown.classList.contains('show');
    closeAllDropdowns();
    if (!isShown) dropdown.classList.add('show');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    const isShown = dropdown.classList.contains('show');
    closeAllDropdowns();
    if (!isShown) dropdown.classList.add('show');
}

function toggleMobileMenu() {
    const nav = document.getElementById('mainNav');
    const overlay = document.getElementById('mobileMenuOverlay');
    const toggleBtn = document.querySelector('.mobile-menu-toggle');
    const body = document.body;
    nav.classList.toggle('active');
    overlay.classList.toggle('active');
    toggleBtn.classList.toggle('active');
    body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
}

function closeMobileMenu() {
    const nav = document.getElementById('mainNav');
    if (nav.classList.contains('active')) {
        toggleMobileMenu();
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.language-dropdown, .user-dropdown').forEach(d => d.classList.remove('show'));
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.language-selector-wrapper, .user-menu-wrapper')) {
        closeAllDropdowns();
    }
});

window.addEventListener('scroll', function() {
    const header = document.getElementById('siteHeader');
    if (header) {
        header.classList.toggle('header-scrolled', window.scrollY > 50);
    }
});

<?php if ($isLoggedIn): ?>
document.addEventListener('DOMContentLoaded', function() {
    const balanceElement = document.querySelector('.user-info .user-balance');
    const balanceDropdownElement = document.querySelector('.user-dropdown-header .user-balance-large');
    
    if (balanceElement) {
        // Optionnel : Vous pourriez ajouter un effet de chargement ici
    }
});
<?php endif; ?>
</script>