<?php
class Language {
    
    private static $instance = null;
    private $currentLanguage = 'fr';
    private $supportedLanguages = ['fr', 'en', 'es', 'de'];
    private $defaultLanguage = 'fr';
    private $translations = [];
    private $slugs = [];
    private $isLoaded = false;
    
    private function __construct() {
        $this->detectLanguage();
        $this->loadTranslations();
        $this->loadSlugs();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function detectLanguage() {
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->supportedLanguages)) {
            $this->currentLanguage = $_GET['lang'];
            $this->setLanguageCookie($_GET['lang']);
            return;
        }
        
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (preg_match('#^/(' . implode('|', $this->supportedLanguages) . ')/#', $path, $matches)) {
            $this->currentLanguage = $matches[1];
            $this->setLanguageCookie($matches[1]);
            return;
        }
        
        if (isset($_COOKIE['preferred_language']) && in_array($_COOKIE['preferred_language'], $this->supportedLanguages)) {
            $this->currentLanguage = $_COOKIE['preferred_language'];
            return;
        }
        
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = $this->parseBrowserLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($browserLang && in_array($browserLang, $this->supportedLanguages)) {
                $this->currentLanguage = $browserLang;
                $this->setLanguageCookie($browserLang);
                return;
            }
        }
        
        $this->currentLanguage = $this->defaultLanguage;
    }
    
    private function parseBrowserLanguage($acceptLanguage) {
        preg_match_all('/([a-z]{2})(?:-[A-Z]{2})?(?:;q=([0-9.]+))?/', $acceptLanguage, $matches);
        
        if (empty($matches[1])) return null;
        
        $languages = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $lang = $matches[1][$i];
            $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' ? (float)$matches[2][$i] : 1.0;
            $languages[$lang] = $quality;
        }
        
        arsort($languages);
        
        foreach ($languages as $lang => $quality) {
            if (in_array($lang, $this->supportedLanguages)) {
                return $lang;
            }
        }
        
        return null;
    }
    
    private function setLanguageCookie($language) {
        if (!headers_sent()) {
            setcookie('preferred_language', $language, time() + (86400 * 365), '/');
        }
    }
    
    private function loadTranslations() {
        foreach ($this->supportedLanguages as $lang) {
            $translationFile = __DIR__ . "/../lang/{$lang}.php";
            if (file_exists($translationFile)) {
                $this->translations[$lang] = include $translationFile;
            } else {
                $this->translations[$lang] = [];
                error_log("Fichier de traduction manquant: $translationFile");
            }
        }
        $this->isLoaded = true;
    }
    
    private function loadSlugs() {
        $slugFile = __DIR__ . "/../lang/slugs.php";
        if (file_exists($slugFile)) {
            $this->slugs = include $slugFile;
        } else {
            $this->slugs = [];
            error_log("Fichier de slugs manquant: $slugFile");
        }
    }
    
    public function get($key, $params = [], $lang = null) {
        $language = $lang ?: $this->currentLanguage;
        
        if (!isset($this->translations[$language][$key])) {
            if ($language !== $this->defaultLanguage && isset($this->translations[$this->defaultLanguage][$key])) {
                $translation = $this->translations[$this->defaultLanguage][$key];
            } else {
                $translation = $key;
                error_log("Traduction manquante pour clé '$key' en langue '$language'");
            }
        } else {
            $translation = $this->translations[$language][$key];
        }
        
        if (!empty($params) && is_array($params)) {
            foreach ($params as $placeholder => $value) {
                $translation = str_replace('{' . $placeholder . '}', $value, $translation);
            }
        }
        
        return $translation;
    }
    
    public function getSlug($key, $lang = null) {
        $language = $lang ?: $this->currentLanguage;
        
        if (isset($this->slugs[$key][$language])) {
            return $this->slugs[$key][$language];
        }
        
        if (isset($this->slugs[$key][$this->defaultLanguage])) {
            return $this->slugs[$key][$this->defaultLanguage];
        }
        
        return $key;
    }
    
    public function url($path = '', $lang = null, $params = []) {
        $language = $lang ?: $this->currentLanguage;
        $baseUrl = $this->getBaseUrl();
        
        $path = trim($path, '/');
        
        $url = $baseUrl . '/' . $language . '/';
        if (!empty($path)) {
            $url .= $path . '/';
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    public function pageUrl($type, $slug = '', $lang = null) {
        $language = $lang ?: $this->currentLanguage;
        
        switch ($type) {
            case 'login':
                $loginSlug = $this->getLoginSlug($language);
                return $this->url($loginSlug, $language);
                
            case 'register':
                $registerSlug = $this->getRegisterSlug($language);
                return $this->url($registerSlug, $language);
                
            case 'dashboard':
                $dashboardSlug = $this->getDashboardSlug($language);
                return $this->url($dashboardSlug, $language);
                
            case 'loan_request':
                $loanSlug = $this->getLoanRequestSlug($language);
                return $this->url($loanSlug, $language);
                
            case 'withdrawal':
                $withdrawalSlug = $this->getWithdrawalSlug($language);
                return $this->url($withdrawalSlug, $language);
                
            case 'profile':
                $profileSlug = $this->getProfileSlug($language);
                return $this->url($profileSlug, $language);

            case 'documents':
                $documentsSlug = $this->getSlug('documents', $language);
                return $this->url($documentsSlug, $language);
                
            case 'blog':
                if (empty($slug)) {
                    return $this->url('blog', $language);
                }
                return $this->url('blog/' . $slug, $language);
                
            case 'contact':
                $contactSlug = $this->getSlug('contact', $language);
                return $this->url($contactSlug, $language);
                
            case 'about':
                $aboutSlug = $this->getSlug('about', $language);
                return $this->url($aboutSlug, $language);
                
            case 'testimonials':
                $testimonialsSlug = $this->getSlug('testimonials', $language);
                return $this->url($testimonialsSlug, $language);
                
            case 'partners':
                $partnersSlug = $this->getSlug('partners', $language);
                return $this->url($partnersSlug, $language);
                
            default:
                return $this->url($slug, $language);
        }
    }
    
    private function getLoginSlug($language) {
        $slugs = [
            'fr' => 'connexion',
            'en' => 'login',
            'es' => 'acceso',
            'de' => 'anmelden'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    private function getRegisterSlug($language) {
        $slugs = [
            'fr' => 'inscription',
            'en' => 'register',
            'es' => 'registro',
            'de' => 'registrieren'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    private function getDashboardSlug($language) {
        $slugs = [
            'fr' => 'tableau-de-bord',
            'en' => 'dashboard',
            'es' => 'panel',
            'de' => 'dashboard'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    private function getLoanRequestSlug($language) {
        $slugs = [
            'fr' => 'demande-pret',
            'en' => 'loan-request',
            'es' => 'solicitud-prestamo',
            'de' => 'kredit-antrag'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    private function getWithdrawalSlug($language) {
        $slugs = [
            'fr' => 'retrait',
            'en' => 'withdrawal',
            'es' => 'retiro',
            'de' => 'abhebung'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    private function getProfileSlug($language) {
        $slugs = [
            'fr' => 'profil',
            'en' => 'profile',
            'es' => 'perfil',
            'de' => 'profil'
        ];
        return $slugs[$language] ?? $slugs['fr'];
    }
    
    public function getImagePath($imagePath) {
        $imagePath = ltrim($imagePath, '/');
        return '/' . $imagePath;
    }
    
    public function getAssetUrl($assetPath) {
        $baseUrl = $this->getBaseUrl();
        $assetPath = ltrim($assetPath, '/');
        return $baseUrl . '/' . $assetPath;
    }
    
    public function generateHreflang($path = '') {
        $hreflangTags = '';
        $baseUrl = $this->getBaseUrl();
        
        foreach ($this->supportedLanguages as $lang) {
            $url = $baseUrl . '/' . $lang . '/';
            if (!empty($path)) {
                $url .= ltrim($path, '/') . '/';
            }
            $hreflangTags .= '<link rel="alternate" hreflang="' . $lang . '" href="' . htmlspecialchars($url) . '">' . "\n";
        }
        
        $defaultUrl = $baseUrl . '/' . $this->defaultLanguage . '/';
        if (!empty($path)) {
            $defaultUrl .= ltrim($path, '/') . '/';
        }
        $hreflangTags .= '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($defaultUrl) . '">' . "\n";
        
        return $hreflangTags;
    }
    
    public function getCanonicalUrl($path = '') {
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . '/' . $this->currentLanguage . '/';
        
        if (!empty($path)) {
            $url .= ltrim($path, '/') . '/';
        }
        
        return $url;
    }
    
    public function getBaseUrl() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'];
    }
    
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    public function setCurrentLanguage($language) {
        if (in_array($language, $this->supportedLanguages)) {
            $this->currentLanguage = $language;
            $this->setLanguageCookie($language);
        }
    }
    
    public function getSupportedLanguages() {
        return $this->supportedLanguages;
    }
    
    public function isLanguageSupported($language) {
        return in_array($language, $this->supportedLanguages);
    }
    
    public function getLanguageName($lang = null) {
        $language = $lang ?: $this->currentLanguage;
        
        $names = [
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español',
            'de' => 'Deutsch'
        ];
        
        return $names[$language] ?? $language;
    }
    
    public function getCountryCode($lang = null) {
        $language = $lang ?: $this->currentLanguage;
        
        $countries = [
            'fr' => 'fr',
            'en' => 'gb',
            'es' => 'es',
            'de' => 'de'
        ];
        
        return $countries[$language] ?? $language;
    }
    
    public function generateLanguageSelector() {
        $currentPath = $_SERVER['REQUEST_URI'];
        $currentPath = preg_replace('#^/(' . implode('|', $this->supportedLanguages) . ')/#', '', $currentPath);
        $currentPath = ltrim($currentPath, '/');
        
        $html = '<div class="language-selector">';
        
        foreach ($this->supportedLanguages as $lang) {
            $url = $this->url($currentPath, $lang);
            $isActive = ($lang === $this->currentLanguage) ? ' active' : '';
            $countryCode = $this->getCountryCode($lang);
            $languageName = $this->getLanguageName($lang);
            
            $html .= '<a href="' . htmlspecialchars($url) . '" class="lang-link' . $isActive . '" hreflang="' . $lang . '">';
            $html .= '<img src="' . $this->getImagePath('images/flags/' . $countryCode . '.svg') . '" alt="' . $languageName . '" width="20" height="15"> ';
            $html .= '<span>' . $languageName . '</span>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function redirectToCorrectLanguage() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if (!preg_match('#^/(' . implode('|', $this->supportedLanguages) . ')/#', $path)) {
            $cleanPath = ltrim($path, '/');
            $redirectUrl = $this->url($cleanPath);
            
            if (!headers_sent()) {
                header('Location: ' . $redirectUrl, true, 302);
                exit;
            }
        }
    }
    
    public function formatDate($date, $format = null) {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if (!$format) {
            switch ($this->currentLanguage) {
                case 'fr':
                    $format = 'd/m/Y';
                    break;
                case 'en':
                    $format = 'M j, Y';
                    break;
                case 'es':
                    $format = 'd/m/Y';
                    break;
                case 'de':
                    $format = 'd.m.Y';
                    break;
                default:
                    $format = 'Y-m-d';
            }
        }
        
        return date($format, $timestamp);
    }
    
    public function formatPrice($price, $currency = 'EUR') {
        switch ($this->currentLanguage) {
            case 'fr':
                return number_format($price, 2, ',', ' ') . ' ' . $currency;
            case 'en':
                return $currency . ' ' . number_format($price, 2, '.', ',');
            case 'es':
                return number_format($price, 2, ',', '.') . ' ' . $currency;
            case 'de':
                return number_format($price, 2, ',', '.') . ' ' . $currency;
            default:
                return number_format($price, 2) . ' ' . $currency;
        }
    }
    
    public function formatCurrency($amount, $showCurrency = true) {
        $formatted = number_format($amount, 2, ',', ' ');
        
        if ($showCurrency) {
            switch ($this->currentLanguage) {
                case 'en':
                    return '€' . str_replace(' ', ',', str_replace(',', '.', $formatted));
                default:
                    return $formatted . ' €';
            }
        }
        
        return $formatted;
    }
    
    public function getMonthNames() {
        $months = [
            'fr' => ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'es' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
            'de' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember']
        ];
        
        return $months[$this->currentLanguage] ?? $months['fr'];
    }
    
    public function getDayNames() {
        $days = [
            'fr' => ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            'en' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'es' => ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
            'de' => ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag']
        ];
        
        return $days[$this->currentLanguage] ?? $days['fr'];
    }
    
    public function getTimeAgo($timestamp) {
        $now = time();
        $diff = $now - strtotime($timestamp);
        
        $periods = [
            'fr' => [
                'year' => 'an|ans',
                'month' => 'mois|mois', 
                'day' => 'jour|jours',
                'hour' => 'heure|heures',
                'minute' => 'minute|minutes',
                'second' => 'seconde|secondes'
            ],
            'en' => [
                'year' => 'year|years',
                'month' => 'month|months',
                'day' => 'day|days', 
                'hour' => 'hour|hours',
                'minute' => 'minute|minutes',
                'second' => 'second|seconds'
            ],
            'es' => [
                'year' => 'año|años',
                'month' => 'mes|meses',
                'day' => 'día|días',
                'hour' => 'hora|horas', 
                'minute' => 'minuto|minutos',
                'second' => 'segundo|segundos'
            ],
            'de' => [
                'year' => 'Jahr|Jahre',
                'month' => 'Monat|Monate',
                'day' => 'Tag|Tage',
                'hour' => 'Stunde|Stunden',
                'minute' => 'Minute|Minuten', 
                'second' => 'Sekunde|Sekunden'
            ]
        ];
        
        $currentPeriods = $periods[$this->currentLanguage] ?? $periods['fr'];
        
        $lengths = [31536000 => 'year', 2628000 => 'month', 86400 => 'day', 3600 => 'hour', 60 => 'minute', 1 => 'second'];
        
        foreach ($lengths as $seconds => $period) {
            if ($diff >= $seconds) {
                $val = floor($diff / $seconds);
                $periodText = explode('|', $currentPeriods[$period]);
                $unit = $val == 1 ? $periodText[0] : $periodText[1];
                
                $prefix = [
                    'fr' => 'Il y a',
                    'en' => '',
                    'es' => 'Hace',
                    'de' => 'vor'
                ][$this->currentLanguage] ?? 'Il y a';
                
                $suffix = [
                    'fr' => '',
                    'en' => 'ago',
                    'es' => '',
                    'de' => ''
                ][$this->currentLanguage] ?? '';
                
                return trim($prefix . ' ' . $val . ' ' . $unit . ' ' . $suffix);
            }
        }
        
        return $this->get('just_now');
    }
    
    public function debug() {
        return [
            'current_language' => $this->currentLanguage,
            'supported_languages' => $this->supportedLanguages,
            'default_language' => $this->defaultLanguage,
            'translations_loaded' => count($this->translations),
            'slugs_loaded' => count($this->slugs),
            'url_path' => $_SERVER['REQUEST_URI'],
            'browser_lang' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'N/A',
            'cookie_lang' => $_COOKIE['preferred_language'] ?? 'N/A'
        ];
    }
}