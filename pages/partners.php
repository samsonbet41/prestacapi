<?php
$pageKey = 'partners';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

// Données des partenaires en dur
$partners = [
    ['name' => 'BNP Paribas', 'logo' => '/images/partners/bnp-paribas.webp'],
    ['name' => 'Société Générale', 'logo' => '/images/partners/societe-generale.webp'],
    ['name' => 'Crédit Agricole', 'logo' => '/images/partners/credit-agricole.svg'],
    ['name' => 'BPCE', 'logo' => '/images/partners/bpce.png'],
    ['name' => 'Crédit Mutuel', 'logo' => '/images/partners/credit-mutuel.webp'],
    ['name' => 'La Banque Postale', 'logo' => '/images/partners/la-banque-postale.png'],
    ['name' => 'HSBC', 'logo' => '/images/partners/hsbc.png'],
    ['name' => 'ING', 'logo' => '/images/partners/ing.png'],
    ['name' => 'Boursorama', 'logo' => '/images/partners/boursorama.png'],
    ['name' => 'Fortuneo', 'logo' => '/images/partners/fortuneo.webp'],
    ['name' => 'Cetelem', 'logo' => '/images/partners/cetelem.png'],
    ['name' => 'Cofidis', 'logo' => '/images/partners/cofidis.png'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .partners-page-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--spacing-xl);
            margin-top: var(--spacing-2xl);
        }
        .partner-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            min-height: 150px;
        }
        .partner-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .partner-card img {
            max-width: 150px;
            max-height: 60px;
            object-fit: contain;
        }
        .partner-card .partner-name {
            margin-top: var(--spacing-base);
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="container" style="padding-top: var(--spacing-2xl); padding-bottom: var(--spacing-2xl); min-height: 80vh;">
            <div class="section-header text-center">
                <h1 class="section-title"><?php echo $pageTitle; ?></h1>
                <p class="section-subtitle"><?php echo $lang->get('partners_subtitle'); ?></p>
            </div>

            <?php if (!empty($partners)): ?>
            <div class="partners-page-grid">
                <?php foreach ($partners as $partner): ?>
                    <div class="partner-card">
                        <img src="<?php echo htmlspecialchars($partner['logo']); ?>" alt="Logo <?php echo htmlspecialchars($partner['name']); ?>">
                        <div class="partner-name"><?php echo htmlspecialchars($partner['name']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-center">Aucun partenaire à afficher pour le moment.</p>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>