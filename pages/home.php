<?php
$pageKey = 'home';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<?php

$testimonials = $db->fetchAll("
    SELECT * FROM testimonials 
    WHERE is_approved = 1 AND is_featured = 1 
    ORDER BY created_at DESC 
    LIMIT 6
");

$partners = $db->fetchAll("
    SELECT * FROM partners 
    WHERE is_active = 1 
    ORDER BY display_order, name 
    LIMIT 12
");

$blogPosts = $db->fetchAll("
    SELECT * FROM blog_posts 
    WHERE published = 1 AND language = ?
    ORDER BY created_at DESC 
    LIMIT 3
", [$lang->getCurrentLanguage()]);



?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="canonical" href="<?php echo $seo->generateCanonicalUrl($lang->pageUrl($pageKey)); ?>">
    
    <?php echo $seo->generateAlternateLinks(); ?>
    
    <?php echo $seo->generateOpenGraphTags(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php echo $seo->generateTwitterCard(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    
    <?php echo $seo->generateMetaTags(); ?>

    <?php echo $seo->generateStructuredData('webpage', ['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php // Optionnel: Ajouter un Breadcrumb si pertinent
    /*
    echo $seo->generateStructuredData('breadcrumb', ['items' => [
        ['name' => $lang->get('home'), 'url' => $lang->url('home')],
        ['name' => $pageTitle]
    ]]);
    */
    ?>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/animations.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
</head>
<body class="homepage">
    <?php include 'includes/header.php'; ?>
    
    <main class="main-content">
      
        <section class="hero-section">
            <div class="hero-background">
                <div class="hero-gradient"></div>
                <div class="hero-particles"></div>
            </div>
            
            <div class="container">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1 class="hero-title animate-fade-up">
                            <?php echo htmlspecialchars($lang->get('hero_title')); ?>
                        </h1>
                        <p class="hero-subtitle animate-fade-up delay-1">
                            <?php echo htmlspecialchars($lang->get('hero_subtitle')); ?>
                        </p>
                        
                        <div class="hero-features animate-fade-up delay-2">
                            <?php foreach ($lang->get('hero_features') as $index => $feature): ?>
                                <div class="hero-feature-item animate-slide-left delay-<?php echo $index + 3; ?>">
                                    <span class="feature-icon">✓</span>
                                    <span class="feature-text"><?php echo htmlspecialchars($feature); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="hero-actions animate-fade-up delay-4">
                            <?php if ($user->isLoggedIn()): ?>
                                <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="btn btn-primary btn-large">
                                    <?php echo $lang->get('hero_cta_primary'); ?>
                                </a>
                                <a href="<?php echo $lang->pageUrl('dashboard'); ?>" class="btn btn-secondary btn-large">
                                    <?php echo $lang->get('dashboard'); ?>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $lang->pageUrl('register'); ?>" class="btn btn-primary btn-large">
                                    <?php echo $lang->get('hero_cta_primary'); ?>
                                </a>
                                <a href="<?php echo $lang->pageUrl('login'); ?>" class="btn btn-secondary btn-large">
                                    <?php echo $lang->get('header_login'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="hero-visual animate-fade-left delay-2">
                        <div class="hero-card floating">
                            <div class="card-header">
                                <h3><?php echo $lang->get('loan_calculator_title'); ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="calculator-field">
                                    <label><?php echo $lang->get('loan_amount'); ?></label>
                                    <div class="input-group">
                                        <input type="range" id="loanAmount" min="500" max="50000" value="10000" step="500">
                                        <span class="amount-display">10 000€</span>
                                    </div>
                                </div>
                                <div class="calculator-field">
                                    <label><?php echo $lang->get('loan_duration'); ?></label>
                                    <div class="input-group">
                                        <input type="range" id="loanDuration" min="6" max="60" value="24" step="6">
                                        <span class="duration-display">24 <?php echo $lang->get('loan_duration_months'); ?></span>
                                    </div>
                                </div>
                                <div class="calculator-result">
                                    <div class="result-item">
                                        <span class="result-label"><?php echo $lang->get('loan_calculator_monthly_payment'); ?></span>
                                        <span class="result-value" id="monthlyPayment">456€</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-full" onclick="startApplication()">
                                    <?php echo $lang->get('hero_cta_primary'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="floating-elements">
                            <div class="floating-card card-1">
                                <div class="card-icon">💰</div>
                                <div class="card-text">
                                    <div class="card-title">€50,000</div>
                                    <div class="card-subtitle"><?php echo $lang->get('validation_max_amount', ['max' => '50,000']); ?></div>
                                </div>
                            </div>
                            
                            <div class="floating-card card-2">
                                <div class="card-icon">⚡</div>
                                <div class="card-text">
                                    <div class="card-title">24-48h</div>
                                    <div class="card-subtitle">Réponse rapide</div>
                                </div>
                            </div>
                            
                            <div class="floating-card card-3">
                                <div class="card-icon">🔒</div>
                                <div class="card-text">
                                    <div class="card-title">100%</div>
                                    <div class="card-subtitle">Sécurisé</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
     
        <section class="features-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2 class="section-title">Pourquoi choisir PrestaCapi ?</h2>
                    <p class="section-subtitle">Une expérience de financement repensée pour vous</p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <div class="icon-wrapper">⚡</div>
                        </div>
                        <h3 class="feature-title">Réponse ultra-rapide</h3>
                        <p class="feature-description">Recevez une réponse définitive sous 24-48h grâce à notre processus automatisé et notre réseau de partenaires.</p>
                        <div class="feature-stats">
                            <span class="stat-number">24h</span>
                            <span class="stat-label">Temps de réponse moyen</span>
                        </div>
                    </div>
                    
                    <div class="feature-card animate-on-scroll delay-1">
                        <div class="feature-icon">
                            <div class="icon-wrapper">🏦</div>
                        </div>
                        <h3 class="feature-title">Réseau de partenaires</h3>
                        <p class="feature-description">Plus de 50 partenaires financiers pour vous offrir les meilleures conditions selon votre profil.</p>
                        <div class="feature-stats">
                            <span class="stat-number">50+</span>
                            <span class="stat-label">Partenaires financiers</span>
                        </div>
                    </div>
                    
                    <div class="feature-card animate-on-scroll delay-2">
                        <div class="feature-icon">
                            <div class="icon-wrapper">📱</div>
                        </div>
                        <h3 class="feature-title">100% Digital</h3>
                        <p class="feature-description">Tout en ligne ! Dossier, documents, suivi, virement... Gérez votre prêt depuis votre smartphone.</p>
                        <div class="feature-stats">
                            <span class="stat-number">0</span>
                            <span class="stat-label">Déplacement nécessaire</span>
                        </div>
                    </div>
                    
                    <div class="feature-card animate-on-scroll delay-3">
                        <div class="feature-icon">
                            <div class="icon-wrapper">🔒</div>
                        </div>
                        <h3 class="feature-title">Sécurité maximale</h3>
                        <p class="feature-description">Vos données sont protégées par un chiffrement de niveau bancaire et nous respectons le RGPD.</p>
                        <div class="feature-stats">
                            <span class="stat-number">256</span>
                            <span class="stat-label">Bits de chiffrement</span>
                        </div>
                    </div>
                    
                    <div class="feature-card animate-on-scroll delay-4">
                        <div class="feature-icon">
                            <div class="icon-wrapper">💰</div>
                        </div>
                        <h3 class="feature-title">Montants flexibles</h3>
                        <p class="feature-description">De 500€ à 50 000€, sur 6 à 60 mois. Trouvez la solution qui correspond à votre projet.</p>
                        <div class="feature-stats">
                            <span class="stat-number">50k€</span>
                            <span class="stat-label">Montant maximum</span>
                        </div>
                    </div>
                    
                    <div class="feature-card animate-on-scroll delay-5">
                        <div class="feature-icon">
                            <div class="icon-wrapper">🎯</div>
                        </div>
                        <h3 class="feature-title">Taux personnalisés</h3>
                        <p class="feature-description">Notre IA analyse votre profil pour négocier les meilleurs taux auprès de nos partenaires.</p>
                        <div class="feature-stats">
                            <span class="stat-number">2.9%</span>
                            <span class="stat-label">Taux à partir de</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
      
        <section class="home-process-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2 class="section-title">Comment ça marche ?</h2>
                    <p class="section-subtitle">Un processus simple en 4 étapes</p>
                </div>
                
                <div class="home-process__steps">
                    <div class="home-process__step animate-on-scroll">
                        <div class="home-process__step-number">1</div>
                        <div class="step-content">
                            <h3 class="step-title">Créez votre compte</h3>
                            <p class="step-description">Inscription gratuite en 2 minutes. Renseignez vos informations de base.</p>
                        </div>
                        <div class="step-visual">
                            <div class="visual-icon">👤</div>
                        </div>
                    </div>
                    
                    <div class="process-arrow animate-on-scroll delay-1">→</div>
                    
                    <div class="home-process__step animate-on-scroll delay-1">
                        <div class="home-process__step-number">2</div>
                        <div class="step-content">
                            <h3 class="step-title">Faites votre demande</h3>
                            <p class="step-description">Formulaire intelligent qui s'adapte à votre profil. Uploadez vos documents.</p>
                        </div>
                        <div class="step-visual">
                            <div class="visual-icon">📄</div>
                        </div>
                    </div>
                    
                    <div class="process-arrow animate-on-scroll delay-2">→</div>
                    
                    <div class="home-process__step animate-on-scroll delay-2">
                        <div class="home-process__step-number">3</div>
                        <div class="step-content">
                            <h3 class="step-title">Recevez votre réponse</h3>
                            <p class="step-description">Analyse automatique + négociation avec nos partenaires. Réponse sous 24-48h.</p>
                        </div>
                        <div class="step-visual">
                            <div class="visual-icon">✅</div>
                        </div>
                    </div>
                    
                    <div class="process-arrow animate-on-scroll delay-3">→</div>
                    
                    <div class="home-process__step animate-on-scroll delay-3">
                        <div class="home-process__step-number">4</div>
                        <div class="step-content">
                            <h3 class="step-title">Recevez vos fonds</h3>
                            <p class="step-description">Demande de virement depuis votre espace. Réception sous 24-48h.</p>
                        </div>
                        <div class="step-visual">
                            <div class="visual-icon">💸</div>
                        </div>
                    </div>
                </div>
                
                <div class="process-cta text-center">
                    <a href="<?php echo $lang->pageUrl('register'); ?>" class="btn btn-primary btn-large">
                        Commencer maintenant
                    </a>
                    <p class="cta-note">Gratuit et sans engagement</p>
                </div>
            </div>
        </section>
        
   
        <?php if (!empty($testimonials)): ?>
        <section class="testimonials-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2 class="section-title"><?php echo $lang->get('testimonials_title'); ?></h2>
                    <p class="section-subtitle"><?php echo $lang->get('testimonials_subtitle'); ?></p>
                </div>
                
                <div class="testimonials-grid">
                    <?php foreach (array_slice($testimonials, 0, 6) as $index => $testimonial): ?>
                        <div class="testimonial-card animate-on-scroll delay-<?php echo $index % 3; ?>">
                            <div class="testimonial-content">
                                <div class="testimonial-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="testimonial-text"><?php echo htmlspecialchars($testimonial['content']); ?></p>
                            </div>
                            <div class="testimonial-author">
                                <div class="author-avatar">
                                    <?php echo strtoupper(substr($testimonial['name'], 0, 1)); ?>
                                </div>
                                <div class="author-info">
                                    <div class="author-name"><?php echo htmlspecialchars($testimonial['name']); ?></div>
                                    <div class="author-title"><?php echo $lang->get('testimonials_verified'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="testimonials-cta text-center">
                    <a href="<?php echo $lang->pageUrl('testimonials'); ?>" class="btn btn-outline">
                        Voir tous les témoignages
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
    
        <?php if (!empty($partners)): ?>
        <section class="partners-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2 class="section-title"><?php echo $lang->get('partners_title'); ?></h2>
                    <p class="section-subtitle"><?php echo $lang->get('partners_subtitle'); ?></p>
                </div>
                
                <div class="partners-carousel">
                    <div class="partners-track">
                        <?php foreach ($partners as $partner): ?>
                            <div class="partner-item">
                                <?php if (!empty($partner['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($partner['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($partner['name']); ?>"
                                         class="partner-logo">
                                <?php else: ?>
                                    <div class="partner-name"><?php echo htmlspecialchars($partner['name']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="partners-cta text-center">
                    <a href="<?php echo $lang->pageUrl('partners'); ?>" class="btn btn-outline">
                        Découvrir tous nos partenaires
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
 
        <?php if (!empty($blogPosts)): ?>
        <section class="blog-section">
            <div class="container">
                <div class="section-header text-center">
                    <h2 class="section-title"><?php echo $lang->get('blog_title'); ?></h2>
                    <p class="section-subtitle"><?php echo $lang->get('blog_subtitle'); ?></p>
                </div>
                
                <div class="blog-grid">
                    <?php foreach ($blogPosts as $index => $post): ?>
                        <article class="blog-card animate-on-scroll delay-<?php echo $index; ?>">
                            <?php if (!empty($post['featured_image'])): ?>
                                <div class="blog-image">
                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span class="blog-date"><?php echo $lang->formatDate($post['created_at']); ?></span>
                                    <span class="blog-author"><?php echo htmlspecialchars($post['author']); ?></span>
                                </div>
                                <h3 class="blog-title">
                                    <a href="<?php echo $lang->pageUrl('blog', $post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <p class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <a href="<?php echo $lang->pageUrl('blog', $post['slug']); ?>" class="blog-link">
                                    <?php echo $lang->get('blog_read_more'); ?> →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <div class="blog-cta text-center">
                    <a href="<?php echo $lang->pageUrl('blog'); ?>" class="btn btn-outline">
                        Voir tous les articles
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <section class="cta-section">
            <div class="container">
                <div class="cta-content text-center">
                    <h2 class="cta-title">Prêt à concrétiser votre projet ?</h2>
                    <p class="cta-subtitle">Rejoignez plus de 10 000 clients qui nous font confiance</p>
                    
                    <div class="cta-stats">
                        <div class="stat-item">
                            <div class="stat-number">10,000+</div>
                            <div class="stat-label">Clients satisfaits</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">€25M</div>
                            <div class="stat-label">Prêts accordés</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">24h</div>
                            <div class="stat-label">Temps de réponse</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">4.8/5</div>
                            <div class="stat-label">Note client</div>
                        </div>
                    </div>
                    
                    <div class="cta-actions">
                        <a href="<?php echo $lang->pageUrl('register'); ?>" class="btn btn-primary btn-large">
                            <?php echo $lang->get('hero_cta_primary'); ?>
                        </a>
                        <a href="<?php echo $lang->pageUrl('contact'); ?>" class="btn btn-outline btn-large">
                            <?php echo $lang->get('contact'); ?>
                        </a>
                    </div>
                    
                    <div class="cta-security">
                        <div class="security-badges">
                            <div class="badge">🔒 SSL 256-bit</div>
                            <div class="badge">🛡️ RGPD Conforme</div>
                            <div class="badge">🏦 Agréé ACPR</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="/js/main.js"></script>
    <script>
        function startApplication() {
            <?php if ($user->isLoggedIn()): ?>
                window.location.href = '<?php echo $lang->pageUrl('loan_request'); ?>';
            <?php else: ?>
                window.location.href = '<?php echo $lang->pageUrl('register'); ?>';
            <?php endif; ?>
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const loanAmountSlider = document.getElementById('loanAmount');
            const loanDurationSlider = document.getElementById('loanDuration');
            const amountDisplay = document.querySelector('.amount-display');
            const durationDisplay = document.querySelector('.duration-display');
            const monthlyPaymentDisplay = document.getElementById('monthlyPayment');
            
            if (loanAmountSlider && loanDurationSlider) {
                function updateCalculator() {
                    const amount = parseInt(loanAmountSlider.value);
                    const duration = parseInt(loanDurationSlider.value);
                    const rate = 0.05; 
                    
                    const monthlyRate = rate / 12;
                    const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, duration)) / 
                                        (Math.pow(1 + monthlyRate, duration) - 1);
                    
                    amountDisplay.textContent = new Intl.NumberFormat('fr-FR').format(amount) + '€';
                    durationDisplay.textContent = duration + ' <?php echo $lang->get('loan_duration_months'); ?>';
                    monthlyPaymentDisplay.textContent = Math.round(monthlyPayment) + '€';
                }
                
                loanAmountSlider.addEventListener('input', updateCalculator);
                loanDurationSlider.addEventListener('input', updateCalculator);
                
                updateCalculator();
            }
        });
    </script>
    
    <?php echo $seo->generateStructuredData('organization', []); ?>
</body>
</html>