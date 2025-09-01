<?php
http_response_code(404);

require_once 'classes/Language.php';
require_once 'classes/SEO.php';

$lang = Language::getInstance();
$seo = new SEO();

$pageTitle = $lang->get('page_not_found');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <?php echo $lang->generateHreflang(); ?>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
    
    <style>
        .error-404-page {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-1) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .error-404-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="25" cy="25" r="2" fill="white"/><circle cx="75" cy="25" r="1.5" fill="white"/><circle cx="50" cy="75" r="1" fill="white"/><circle cx="25" cy="75" r="1.5" fill="white"/><circle cx="75" cy="75" r="2" fill="white"/></svg>') repeat;
            animation: float 20s ease-in-out infinite;
        }
        
        .error-404-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .error-404-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-xl);
            padding: 3rem 2rem;
            box-shadow: var(--shadow-xl);
        }
        
        .error-number {
            font-size: clamp(4rem, 15vw, 8rem);
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
            display: block;
        }
        
        .error-title {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .error-subtitle {
            font-size: 1.125rem;
            color: #666;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .error-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .error-search {
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        
        .error-search input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid var(--secondary-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            outline: none;
            transition: var(--transition);
        }
        
        .error-search input:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(0, 184, 217, 0.1);
        }
        
        .error-search button {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .error-search button:hover {
            transform: translateY(-50%) scale(1.05);
        }
        
        .error-suggestions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .suggestion-card {
            background: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-decoration: none;
            color: var(--accent-2);
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .suggestion-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-1);
        }
        
        .suggestion-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .suggestion-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .suggestion-description {
            font-size: 0.875rem;
            color: #666;
        }
        
        .error-help {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--secondary-color);
        }
        
        .help-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .help-contacts {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .help-contact {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent-1);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .help-contact:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .error-404-container {
                padding: 1rem;
            }
            
            .error-404-content {
                padding: 2rem 1.5rem;
            }
            
            .error-suggestions {
                grid-template-columns: 1fr;
            }
            
            .help-contacts {
                flex-direction: column;
                gap: 1rem;
            }
        }
        
        .animate-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0,-15px,0);
            }
            70% {
                transform: translate3d(0,-7px,0);
            }
            90% {
                transform: translate3d(0,-2px,0);
            }
        }
    </style>
</head>
<body class="error-404-page">
    <div class="error-404-background"></div>
    
    <div class="error-404-container">
        <div class="error-404-content">
            <div class="error-number animate-bounce">404</div>
            
            <h1 class="error-title"><?php echo $lang->get('page_not_found'); ?></h1>
            
            <p class="error-subtitle">
                <?php echo $lang->get('page_not_found_message'); ?>
            </p>
            
            <div class="error-actions">
                <div class="error-search">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="<?php echo $lang->get('search_placeholder'); ?>" 
                           onkeypress="handleSearch(event)">
                    <button onclick="performSearch()">🔍</button>
                </div>
                
                <a href="<?php echo $lang->url(); ?>" class="btn btn-primary btn-large">
                    <?php echo $lang->get('page_not_found_home'); ?>
                </a>
            </div>
            
            <div class="error-suggestions">
                <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="suggestion-card">
                    <span class="suggestion-icon">💰</span>
                    <div class="suggestion-title">Faire une demande</div>
                    <div class="suggestion-description">Démarrez votre demande de prêt</div>
                </a>
                
                <a href="<?php echo $lang->pageUrl('about'); ?>" class="suggestion-card">
                    <span class="suggestion-icon">ℹ️</span>
                    <div class="suggestion-title">À propos</div>
                    <div class="suggestion-description">Découvrez PrestaCapi</div>
                </a>
                
                <a href="<?php echo $lang->pageUrl('blog'); ?>" class="suggestion-card">
                    <span class="suggestion-icon">📚</span>
                    <div class="suggestion-title">Blog</div>
                    <div class="suggestion-description">Conseils financiers</div>
                </a>
                
                <a href="<?php echo $lang->pageUrl('contact'); ?>" class="suggestion-card">
                    <span class="suggestion-icon">📞</span>
                    <div class="suggestion-title">Contact</div>
                    <div class="suggestion-description">Besoin d'aide ?</div>
                </a>
            </div>
            
            <div class="error-help">
                <h3 class="help-title">Besoin d'aide ?</h3>
                <div class="help-contacts">
                    <a href="tel:+33745505207" class="help-contact">
                        <span>📞</span>
                        <span>+33 7 45 50 52 07</span>
                    </a>
                    <a href="mailto:support@prestacapi.com" class="help-contact">
                        <span>📧</span>
                        <span>support@prestacapi.com</span>
                    </a>
                    <a href="https://wa.me/33745505207" class="help-contact" target="_blank" rel="noopener">
                        <span>💬</span>
                        <span>WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function handleSearch(event) {
            if (event.key === 'Enter') {
                performSearch();
            }
        }
        
        function performSearch() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                const baseUrl = '<?php echo $lang->url(); ?>';
                window.location.href = baseUrl + '/search?q=' + encodeURIComponent(query);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const suggestedPages = [
                '<?php echo $lang->pageUrl('loan_request'); ?>',
                '<?php echo $lang->pageUrl('dashboard'); ?>',
                '<?php echo $lang->pageUrl('about'); ?>',
                '<?php echo $lang->pageUrl('contact'); ?>'
            ];
            
            const currentPath = window.location.pathname.toLowerCase();
            
            suggestedPages.forEach(page => {
                if (currentPath.includes(page.split('/').pop())) {
                    document.getElementById('searchInput').placeholder = 'Essayez: ' + page.split('/').pop();
                }
            });
            
            setTimeout(() => {
                document.getElementById('searchInput').focus();
            }, 1000);
        });
        
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
    </script>
</body>
</html>