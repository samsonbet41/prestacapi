<?php
$pageKey = 'home';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

// Données des témoignages et partenaires maintenant en dur
$testimonials = [
    [
        'name' => 'Alice Martin',
        'content' => 'Processus incroyablement simple et rapide. J\'ai reçu une offre en moins de 24 heures et les fonds étaient sur mon compte 2 jours plus tard. Je recommande vivement !',
        'rating' => 5,
        'verified' => true
    ],
    [
        'name' => 'Julien Dubois',
        'content' => 'Service client très réactif et professionnel. Ils ont su répondre à toutes mes questions avec clarté. Le taux obtenu était très compétitif.',
        'rating' => 5,
        'verified' => true
    ],
    [
        'name' => 'Claire Petit',
        'content' => 'Enfin une plateforme de prêt 100% en ligne qui tient ses promesses. La simulation est précise et il n\'y a pas de frais cachés. Très satisfaite.',
        'rating' => 4,
        'verified' => true
    ],
    [
        'name' => 'Lucas Bernard',
        'content' => 'J\'ai pu financer l\'achat de ma nouvelle voiture grâce à PrestaCapi. Le suivi du dossier depuis l\'espace client est un vrai plus.',
        'rating' => 5,
        'verified' => true
    ],
    [
        'name' => 'Émilie Laurent',
        'content' => 'La plateforme est intuitive et la soumission des documents est très facile. Une expérience sans stress, bien loin des banques traditionnelles.',
        'rating' => 5,
        'verified' => true
    ],
    [
        'name' => 'Thomas Moreau',
        'content' => 'Bonne expérience dans l\'ensemble. Le délai de réponse était un peu plus long que prévu (48h), mais le résultat a été à la hauteur de mes attentes.',
        'rating' => 4,
        'verified' => true
    ]
];

$partners = [
    ['name' => 'BNP Paribas', 'logo' => '/images/partners/bnp-paribas.webp'],
    ['name' => 'Société Générale', 'logo' => '/images/partners/societe-generale.webp'],
    ['name' => 'Crédit Agricole', 'logo' => '/images/partners/credit-agricole.svg'],
    ['name' => 'BPCE', 'logo' => '/images/partners/bpce.png'],
    ['name' => 'Crédit Mutuel', 'logo' => '/images/partners/credit-mutuel.webp'],
    ['name' => 'La Banque Postale', 'logo' => '/images/partners/la-banque-postale.png'],
    ['name' => 'HSBC', 'logo' => '/images/partners/hsbc.png'],
    ['name' => 'ING', 'logo' => '/images/partners/ing.png'],
];


// Appel BDD pour les articles de blog (conservé)
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
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css">
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
                        <div class="feature-icon"><div class="icon-wrapper">⚡</div></div>
                        <h3 class="feature-title">Réponse ultra-rapide</h3>
                        <p class="feature-description">Recevez une réponse définitive sous 24-48h grâce à notre processus automatisé et notre réseau de partenaires.</p>
                    </div>
                    <div class="feature-card animate-on-scroll delay-1">
                        <div class="feature-icon"><div class="icon-wrapper">🏦</div></div>
                        <h3 class="feature-title">Réseau de partenaires</h3>
                        <p class="feature-description">Plus de 50 partenaires financiers pour vous offrir les meilleures conditions selon votre profil.</p>
                    </div>
                    <div class="feature-card animate-on-scroll delay-2">
                        <div class="feature-icon"><div class="icon-wrapper">📱</div></div>
                        <h3 class="feature-title">100% Digital</h3>
                        <p class="feature-description">Tout en ligne ! Dossier, documents, suivi, virement... Gérez votre prêt depuis votre smartphone.</p>
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
                        <h3 class="step-title">Créez votre compte</h3>
                        <p class="step-description">Inscription gratuite en 2 minutes.</p>
                    </div>
                    <div class="process-arrow animate-on-scroll delay-1">→</div>
                    <div class="home-process__step animate-on-scroll delay-1">
                        <div class="home-process__step-number">2</div>
                        <h3 class="step-title">Faites votre demande</h3>
                        <p class="step-description">Formulaire intelligent et upload de documents.</p>
                    </div>
                    <div class="process-arrow animate-on-scroll delay-2">→</div>
                    <div class="home-process__step animate-on-scroll delay-2">
                        <div class="home-process__step-number">3</div>
                        <h3 class="step-title">Recevez votre réponse</h3>
                        <p class="step-description">Analyse et négociation. Réponse sous 24-48h.</p>
                    </div>
                    <div class="process-arrow animate-on-scroll delay-3">→</div>
                    <div class="home-process__step animate-on-scroll delay-3">
                        <div class="home-process__step-number">4</div>
                        <h3 class="step-title">Recevez vos fonds</h3>
                        <p class="step-description">Virement sur votre compte sous 24-48h.</p>
                    </div>
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
                                <p class="testimonial-text">"<?php echo htmlspecialchars($testimonial['content']); ?>"</p>
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
                        <?php foreach (array_merge($partners, $partners) as $partner): // Dupliquer pour un défilement infini ?>
                            <div class="partner-item">
                                <img src="<?php echo htmlspecialchars($partner['logo']); ?>" 
                                     alt="<?php echo htmlspecialchars($partner['name']); ?>"
                                     class="partner-logo">
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                            <div class="blog-content">
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
            </div>
        </section>
        <?php endif; ?>
        
        <section class="cta-section">
            <div class="container">
                <div class="cta-content text-center">
                    <h2 class="cta-title">Prêt à concrétiser votre projet ?</h2>
                    <p class="cta-subtitle">Rejoignez plus de 10 000 clients qui nous font confiance</p>
                    <div class="cta-actions">
                        <a href="<?php echo $lang->pageUrl('register'); ?>" class="btn btn-primary btn-large">
                            <?php echo $lang->get('hero_cta_primary'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function startApplication() {
            const amount = document.getElementById('loanAmount').value;
            const duration = document.getElementById('loanDuration').value;

            const loanRequestUrl = `<?php echo $lang->pageUrl('loan_request'); ?>?amount=${amount}&duration=${duration}`;
            window.location.href = loanRequestUrl;
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
</body>
</html>