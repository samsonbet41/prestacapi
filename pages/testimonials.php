<?php
$pageKey = 'testimonials';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

// Données des témoignages en dur
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
    ],
    [
        'name' => 'Sophie Girard',
        'content' => 'Le simulateur de prêt est très pratique pour avoir une première idée. Le contrat est clair et le service est fiable.',
        'rating' => 4,
        'verified' => true
    ],
    [
        'name' => 'David Garcia',
        'content' => 'Excellent service ! J\'ai consolidé mes crédits facilement. L\'équipe a été d\'un grand soutien tout au long du processus.',
        'rating' => 5,
        'verified' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/home.css"> </head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <section class="testimonials-section" style="background: white; min-height: 80vh;">
            <div class="container">
                <div class="section-header text-center">
                    <h1 class="section-title"><?php echo $pageTitle; ?></h1>
                    <p class="section-subtitle"><?php echo $lang->get('testimonials_subtitle'); ?></p>
                </div>

                <?php if (!empty($testimonials)): ?>
                <div class="testimonials-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="testimonial-card">
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
                <?php else: ?>
                <p class="text-center">Aucun témoignage à afficher pour le moment.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>