<?php

require_once 'classes/Database.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';
require_once 'classes/User.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$lang = Language::getInstance();
$seo = new SEO();
$user = new User();
$db = Database::getInstance();


$slugs = [];
if (file_exists('lang/slugs.php')) {
    $slugs = include 'lang/slugs.php';
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?'); 
$requestUri = rtrim($requestUri, '/'); 


if (empty($requestUri) || $requestUri === '') {
    header('Location: /fr/', 301);
    exit;
}


$urlParts = explode('/', trim($requestUri, '/'));
$requestedLang = isset($urlParts[0]) ? $urlParts[0] : 'fr';
$requestedPage = isset($urlParts[1]) ? $urlParts[1] : '';
$requestedSlug = isset($urlParts[2]) ? $urlParts[2] : '';

$supportedLangs = ['fr', 'en', 'es', 'de'];
if (!in_array($requestedLang, $supportedLangs)) {
    include '404.php';
    exit;
}

$lang->setCurrentLanguage($requestedLang);


function resolveSlugToKey($slug, $language, $slugs) {
    foreach ($slugs as $key => $translations) {
        if (isset($translations[$language]) && $translations[$language] === $slug) {
            return $key;
        }
    }
    return $slug; 
}


$resolvedPageKey = '';
if (!empty($requestedPage)) {
    $resolvedPageKey = resolveSlugToKey($requestedPage, $requestedLang, $slugs);
}



$routes = [
    '' => 'pages/home.php',                    
    'home' => 'pages/home.php',               
    'login' => 'pages/login.php',            
    'register' => 'pages/register.php',       
    'loan_request' => 'pages/loan-request.php', 
    'dashboard' => 'pages/dashboard.php',     
    'profile' => 'pages/profile.php',         
    'documents' => 'pages/documents.php',     
    'withdrawal' => 'pages/withdrawal.php',   
    'about' => 'pages/about.php',             
    'contact' => 'pages/contact.php',         
    'blog' => 'pages/blog.php',               
    'testimonials' => 'pages/testimonials.php', 
    'partners' => 'pages/partners.php',       
    'privacy' => 'pages/privacy.php',        
    'terms' => 'pages/terms.php',             
    'faq' => 'pages/faq.php',                 
    'services' => 'pages/services.php',       
    'personal_loans' => 'pages/personal-loans.php',     
    'business_loans' => 'pages/business-loans.php',     
    'mortgage' => 'pages/mortgage.php',       
    'car_loan' => 'pages/car-loan.php',       
    'how_it_works' => 'pages/how-it-works.php', 
    'calculator' => 'pages/calculator.php', 
    'rates' => 'pages/rates.php',             
    'help' => 'pages/help.php',              
    'support' => 'pages/support.php',         
    'security' => 'pages/security.php',      
    'education' => 'pages/education.php',     
    'eligibility' => 'pages/eligibility.php', 
    'requirements' => 'pages/requirements.php' 
];

$pageToInclude = null;


if ($resolvedPageKey === 'blog' && !empty($requestedSlug)) {
    $pageToInclude = 'pages/blog-post.php';
    
    $article = $db->fetchOne("
        SELECT * FROM blog_posts 
        WHERE slug = ? AND published = 1 AND language = ?
    ", [$requestedSlug, $requestedLang]);
    
    if (!$article) {
        include '404.php';
        exit;
    }
    
    $blogPost = $article;
    
} elseif (empty($requestedPage)) {
    $pageToInclude = $routes[''];
    
} elseif (isset($routes[$resolvedPageKey])) {
    $pageToInclude = $routes[$resolvedPageKey];
    
} else {
    include '404.php';
    exit;
}


$protectedPages = ['dashboard', 'profile', 'documents', 'withdrawal', 'loan_request'];
if (in_array($resolvedPageKey, $protectedPages) && !$user->isLoggedIn()) {
    $returnUrl = urlencode($requestUri);
    
    $loginSlug = isset($slugs['login'][$requestedLang]) ? $slugs['login'][$requestedLang] : 'login';
    header("Location: /{$requestedLang}/{$loginSlug}/?return=" . $returnUrl);
    exit;
}


$guestOnlyPages = ['login', 'register'];
if (in_array($resolvedPageKey, $guestOnlyPages) && $user->isLoggedIn()) {
    $dashboardSlug = isset($slugs['dashboard'][$requestedLang]) ? $slugs['dashboard'][$requestedLang] : 'dashboard';
    header("Location: /{$requestedLang}/{$dashboardSlug}/");
    exit;
}


if (file_exists($pageToInclude)) {
    $currentPage = $resolvedPageKey ?: 'home';
    $currentLang = $requestedLang;
    $currentSlug = $requestedSlug;
    $originalRequestedPage = $requestedPage;

    include $pageToInclude;
} else {
    error_log("Page file not found: " . $pageToInclude . " for resolved key: " . $resolvedPageKey);
    include '404.php';
}


function generateLocalizedUrl($pageKey, $language = null, $additionalSlug = null) {
    global $slugs, $requestedLang;
    
    $targetLang = $language ?: $requestedLang;
    $slug = isset($slugs[$pageKey][$targetLang]) ? $slugs[$pageKey][$targetLang] : $pageKey;
    
    $url = "/{$targetLang}/{$slug}/";
    if ($additionalSlug) {
        $url .= $additionalSlug . '/';
    }
    
    return $url;
}


function generateLanguageUrl($targetLang, $currentLang, $currentUri, $slugs) {
    $uri = strtok($currentUri, '?'); 
    $uri = trim($uri, '/');
    

    if (empty($uri)) {
        return "/{$targetLang}/";
    }
    

    $parts = explode('/', $uri);
    

    $supportedLangs = ['fr', 'en', 'es', 'de'];
    if (in_array($parts[0], $supportedLangs)) {

        $parts[0] = $targetLang;
        

        if (isset($parts[1]) && !empty($parts[1])) {
            $currentPageSlug = $parts[1];
            

            $pageKey = resolveSlugToKey($currentPageSlug, $currentLang, $slugs);
            

            if (isset($slugs[$pageKey][$targetLang])) {
                $parts[1] = $slugs[$pageKey][$targetLang];
            }
        }
        
        return '/' . implode('/', $parts) . '/';
    }
    

    return "/{$targetLang}/" . $uri . '/';
}


function getPageSlugs($pageKey) {
    global $slugs;
    return isset($slugs[$pageKey]) ? $slugs[$pageKey] : [];
}
?>