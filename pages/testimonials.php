<?php
$pageKey = 'testimonials';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
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
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <div class="container">
            <h1><?php echo $pageTitle; ?></h1>
            <p>Contenu à venir...</p>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>