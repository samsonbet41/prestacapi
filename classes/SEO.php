<?php

class SEO {
    private $siteName = 'PrestaCapi';
    private $siteDescription = 'Votre partenaire financier de confiance depuis 2008. Prêts rapides et sécurisés jusqu\'à 50 000€.';
    private $siteUrl = 'https://prestacapi.com';
    private $defaultImage = '/images/og-prestacapi.jpg';
    private $twitterHandle = '@prestacapi';
    
    private $lang;
    
    public function __construct() {
        $this->lang = Language::getInstance();
    }
    
    public function generateTitle($pageTitle, $separator = ' - ') {
        if (empty($pageTitle)) {
            return $this->siteName;
        }
        
        return $pageTitle . $separator . $this->siteName;
    }
    
    public function generateDescription($description = null) {
        if (empty($description)) {
            return $this->siteDescription;
        }
        
        return htmlspecialchars(strip_tags($description));
    }
    
    public function generateCanonicalUrl($path = '') {
        $baseUrl = $this->lang->getBaseUrl();
        $currentLang = $this->lang->getCurrentLanguage();
        
        if (empty($path)) {
            $path = $_SERVER['REQUEST_URI'];
            $path = parse_url($path, PHP_URL_PATH);
        }
        
        return $baseUrl . '/' . $currentLang . '/' . ltrim($path, '/');
    }
    
    public function generateOpenGraphTags($data = []) {
        $defaults = [
            'title' => $this->siteName,
            'description' => $this->siteDescription,
            'image' => $this->siteUrl . $this->defaultImage,
            'url' => $this->generateCanonicalUrl(),
            'type' => 'website',
            'site_name' => $this->siteName
        ];
        
        $data = array_merge($defaults, $data);
        
        $tags = '';
        $tags .= '<meta property="og:title" content="' . htmlspecialchars($data['title']) . '">' . "\n";
        $tags .= '<meta property="og:description" content="' . htmlspecialchars($data['description']) . '">' . "\n";
        $tags .= '<meta property="og:image" content="' . htmlspecialchars($data['image']) . '">' . "\n";
        $tags .= '<meta property="og:url" content="' . htmlspecialchars($data['url']) . '">' . "\n";
        $tags .= '<meta property="og:type" content="' . htmlspecialchars($data['type']) . '">' . "\n";
        $tags .= '<meta property="og:site_name" content="' . htmlspecialchars($data['site_name']) . '">' . "\n";
        $tags .= '<meta property="og:locale" content="' . $this->getLocaleFromLang() . '">' . "\n";
        
        foreach ($this->lang->getSupportedLanguages() as $lang) {
            if ($lang !== $this->lang->getCurrentLanguage()) {
                $alternateUrl = str_replace('/' . $this->lang->getCurrentLanguage() . '/', '/' . $lang . '/', $data['url']);
                $locale = $this->getLocaleFromLang($lang);
                $tags .= '<meta property="og:locale:alternate" content="' . $locale . '">' . "\n";
            }
        }
        
        return $tags;
    }
    
    public function generateTwitterCard($data = []) {
        $defaults = [
            'card' => 'summary_large_image',
            'site' => $this->twitterHandle,
            'title' => $this->siteName,
            'description' => $this->siteDescription,
            'image' => $this->siteUrl . $this->defaultImage
        ];
        
        $data = array_merge($defaults, $data);
        
        $tags = '';
        $tags .= '<meta name="twitter:card" content="' . htmlspecialchars($data['card']) . '">' . "\n";
        $tags .= '<meta name="twitter:site" content="' . htmlspecialchars($data['site']) . '">' . "\n";
        $tags .= '<meta name="twitter:title" content="' . htmlspecialchars($data['title']) . '">' . "\n";
        $tags .= '<meta name="twitter:description" content="' . htmlspecialchars($data['description']) . '">' . "\n";
        $tags .= '<meta name="twitter:image" content="' . htmlspecialchars($data['image']) . '">' . "\n";
        
        return $tags;
    }
    
    public function generateStructuredData($type, $data = []) {
        $structuredData = [];
        
        switch ($type) {
            case 'organization':
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FinancialService',
                    'name' => $this->siteName,
                    'description' => $this->siteDescription,
                    'url' => $this->siteUrl,
                    'logo' => $this->siteUrl . '/images/logo.png',
                    'foundingDate' => '2008',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'addressCountry' => 'FR'
                    ],
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'telephone' => '+33-1-23-45-67-89',
                        'contactType' => 'Customer Service',
                        'availableLanguage' => ['French', 'English', 'Spanish', 'German']
                    ],
                    'sameAs' => [
                        'https://www.facebook.com/prestacapi',
                        'https://www.linkedin.com/company/prestacapi'
                    ]
                ];
                break;
                
            case 'webpage':
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $data['title'] ?? $this->siteName,
                    'description' => $data['description'] ?? $this->siteDescription,
                    'url' => $data['url'] ?? $this->generateCanonicalUrl(),
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => $this->siteName,
                        'url' => $this->siteUrl
                    ],
                    'inLanguage' => $this->lang->getCurrentLanguage(),
                    'dateModified' => date('c')
                ];
                break;
                
            case 'breadcrumb':
                if (!empty($data['items'])) {
                    $listItems = [];
                    foreach ($data['items'] as $position => $item) {
                        $listItems[] = [
                            '@type' => 'ListItem',
                            'position' => $position + 1,
                            'name' => $item['name'],
                            'item' => $item['url'] ?? null
                        ];
                    }
                    
                    $structuredData = [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => $listItems
                    ];
                }
                break;
                
            case 'faq':
                if (!empty($data['questions'])) {
                    $questions = [];
                    foreach ($data['questions'] as $faq) {
                        $questions[] = [
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => $faq['answer']
                            ]
                        ];
                    }
                    
                    $structuredData = [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => $questions
                    ];
                }
                break;
                
            case 'service':
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $data['name'] ?? 'Prêts Personnels',
                    'description' => $data['description'] ?? 'Service de prêts personnels rapides et sécurisés',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => $this->siteName,
                        'url' => $this->siteUrl
                    ],
                    'serviceType' => 'Financial Service',
                    'availableChannel' => [
                        '@type' => 'ServiceChannel',
                        'serviceUrl' => $this->siteUrl,
                        'servicePhone' => '+33-1-23-45-67-89'
                    ]
                ];
                break;
        }
        
        if (empty($structuredData)) {
            return '';
        }
        
        return '<script type="application/ld+json">' . json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    public function generateMetaTags($data = []) {
        $tags = '';
        
        if (!empty($data['keywords'])) {
            $tags .= '<meta name="keywords" content="' . htmlspecialchars($data['keywords']) . '">' . "\n";
        }
        
        if (!empty($data['author'])) {
            $tags .= '<meta name="author" content="' . htmlspecialchars($data['author']) . '">' . "\n";
        }
        
        if (!empty($data['robots'])) {
            $tags .= '<meta name="robots" content="' . htmlspecialchars($data['robots']) . '">' . "\n";
        } else {
            $tags .= '<meta name="robots" content="index, follow">' . "\n";
        }
        
        $tags .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $tags .= '<meta name="theme-color" content="#1F3B73">' . "\n";
        $tags .= '<meta name="msapplication-TileColor" content="#1F3B73">' . "\n";
        
        return $tags;
    }
    
    public function generateAlternateLinks() {
        $currentPath = $_SERVER['REQUEST_URI'];
        $currentPath = preg_replace('#^/(' . implode('|', $this->lang->getSupportedLanguages()) . ')/#', '', $currentPath);
        $currentPath = ltrim($currentPath, '/');
        
        $links = '';
        foreach ($this->lang->getSupportedLanguages() as $lang) {
            $url = $this->lang->url($currentPath, $lang);
            $links .= '<link rel="alternate" hreflang="' . $lang . '" href="' . htmlspecialchars($url) . '">' . "\n";
        }
        
        $defaultUrl = $this->lang->url($currentPath, 'fr');
        $links .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl) . '">' . "\n";
        
        return $links;
    }
    
    public function generateSitemap($urls = []) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            
            if (!empty($url['lastmod'])) {
                $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod']) . '</lastmod>' . "\n";
            }
            
            if (!empty($url['changefreq'])) {
                $xml .= '    <changefreq>' . htmlspecialchars($url['changefreq']) . '</changefreq>' . "\n";
            }
            
            if (!empty($url['priority'])) {
                $xml .= '    <priority>' . htmlspecialchars($url['priority']) . '</priority>' . "\n";
            }
            
            if (!empty($url['alternates'])) {
                foreach ($url['alternates'] as $alternate) {
                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($alternate['hreflang']) . '" href="' . htmlspecialchars($alternate['href']) . '"/>' . "\n";
                }
            }
            
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    public function generateRobotsTxt($customRules = []) {
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /ajax/\n";
        $robots .= "Disallow: /classes/\n";
        $robots .= "Disallow: /config/\n";
        $robots .= "Disallow: /includes/\n";
        $robots .= "Disallow: /lang/\n";
        $robots .= "Disallow: /uploads/documents/\n";
        $robots .= "Disallow: /vendor/\n";
        $robots .= "Allow: /css/\n";
        $robots .= "Allow: /js/\n";
        $robots .= "Allow: /images/\n";
        $robots .= "\n";
        
        if (!empty($customRules)) {
            foreach ($customRules as $rule) {
                $robots .= $rule . "\n";
            }
            $robots .= "\n";
        }
        
        $robots .= "Sitemap: " . $this->siteUrl . "/sitemap.xml\n";
        
        return $robots;
    }
    
    private function getLocaleFromLang($lang = null) {
        $lang = $lang ?: $this->lang->getCurrentLanguage();
        
        $locales = [
            'fr' => 'fr_FR',
            'en' => 'en_US',
            'es' => 'es_ES',
            'de' => 'de_DE'
        ];
        
        return $locales[$lang] ?? 'fr_FR';
    }
    
    public function optimizeImages($imagePath, $quality = 85, $maxWidth = 1920, $maxHeight = 1080) {
        if (!file_exists($imagePath)) {
            return false;
        }
        
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        if ($width <= $maxWidth && $height <= $maxHeight && $type === IMAGETYPE_WEBP) {
            return true;
        }
        
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($sourceImage);
            $sourceImage = $newImage;
        }
        
        $webpPath = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . pathinfo($imagePath, PATHINFO_FILENAME) . '.webp';
        
        if (function_exists('imagewebp')) {
            imagewebp($sourceImage, $webpPath, $quality);
        } else {
            imagejpeg($sourceImage, $imagePath, $quality);
        }
        
        imagedestroy($sourceImage);
        
        return true;
    }
    
    public function generatePreloadTags($resources = []) {
        $tags = '';
        
        foreach ($resources as $resource) {
            $tag = '<link rel="preload" href="' . htmlspecialchars($resource['href']) . '"';
            
            if (!empty($resource['as'])) {
                $tag .= ' as="' . htmlspecialchars($resource['as']) . '"';
            }
            
            if (!empty($resource['type'])) {
                $tag .= ' type="' . htmlspecialchars($resource['type']) . '"';
            }
            
            if (!empty($resource['crossorigin'])) {
                $tag .= ' crossorigin="' . htmlspecialchars($resource['crossorigin']) . '"';
            }
            
            $tag .= '>' . "\n";
            $tags .= $tag;
        }
        
        return $tags;
    }
    
    public function generateCSPHeader($directives = []) {
        $defaultDirectives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", 'https://cdnjs.cloudflare.com'],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
            'font-src' => ["'self'", 'https://fonts.gstatic.com'],
            'img-src' => ["'self'", 'data:', 'https:'],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"]
        ];
        
        $directives = array_merge($defaultDirectives, $directives);
        
        $csp = '';
        foreach ($directives as $directive => $sources) {
            $csp .= $directive . ' ' . implode(' ', $sources) . '; ';
        }
        
        return trim($csp);
    }
}