<?php
if (!isset($lang)) {
    require_once __DIR__ . '/../classes/Language.php';
    $lang = Language::getInstance();
}

$slugs = [];
if (file_exists(__DIR__ . '/../lang/slugs.php')) {
    $slugs = include __DIR__ . '/../lang/slugs.php';
}
?>

<header class="auth-header">
    <div class="auth-header-container">
        <div class="auth-brand">
            <a href="/<?php echo $lang->getCurrentLanguage(); ?>/" class="auth-logo-link">
                <div class="auth-logo">
                    <img src="/images/logo.png" alt="PrestaCapi" class="auth-logo-image">
                    <span class="auth-logo-text">PrestaCapi</span>
                </div>
            </a>
        </div>
        
        <div class="auth-language">
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
    </div>
</header>

<script>
function toggleLanguageSelector() {
    const dropdown = document.getElementById('languageDropdown');
    const isShown = dropdown.classList.contains('show');
    
    closeAllDropdowns();
    
    if (!isShown) {
        dropdown.classList.add('show');
    }
}

function closeAllDropdowns() {
    const dropdowns = document.querySelectorAll('.language-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
    });
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.language-selector-wrapper')) {
        closeAllDropdowns();
    }
});
</script>