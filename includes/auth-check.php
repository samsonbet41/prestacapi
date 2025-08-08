<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Language.php';

$user = new User();
$lang = Language::getInstance();

if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    $currentLang = $lang->getCurrentLanguage();
    $loginUrl = $lang->pageUrl('login');
    
    header("Location: $loginUrl");
    exit;
}

$currentUser = $user->getCurrentUser();

if (!$currentUser || $currentUser['status'] !== 'active') {
    $user->logout();
    
    $loginUrl = $lang->pageUrl('login');
    header("Location: $loginUrl");
    exit;
}

function requireMinimumProfile($redirectTo = null) {
    global $currentUser, $lang;
    
    $required_fields = ['first_name', 'last_name', 'phone', 'date_of_birth'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($currentUser[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['profile_incomplete_message'] = 'Veuillez compléter votre profil avant de continuer.';
        $_SESSION['missing_fields'] = $missing_fields;
        
        $profileUrl = $redirectTo ?: $lang->pageUrl('profile');
        header("Location: $profileUrl");
        exit;
    }
}

function requireDocuments($requiredDocs = ['identity', 'income_proof']) {
    global $currentUser, $lang;
    
    require_once __DIR__ . '/../classes/Document.php';
    $document = new Document();
    
    $userDocs = $document->getUserDocuments($currentUser['id'], true);
    $missingDocs = [];
    
    foreach ($requiredDocs as $docType) {
        $hasVerifiedDoc = false;
        
        if (isset($userDocs[$docType])) {
            foreach ($userDocs[$docType] as $doc) {
                if ($doc['is_verified']) {
                    $hasVerifiedDoc = true;
                    break;
                }
            }
        }
        
        if (!$hasVerifiedDoc) {
            $missingDocs[] = $docType;
        }
    }
    
    if (!empty($missingDocs)) {
        $_SESSION['documents_required_message'] = 'Documents requis non vérifiés. Veuillez les fournir.';
        $_SESSION['missing_documents'] = $missingDocs;
        
        $documentsUrl = $lang->pageUrl('documents');
        header("Location: $documentsUrl");
        exit;
    }
}

function getUserBalance() {
    global $currentUser;
    
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    
    return $db->getUserBalance($currentUser['id']);
}

function hasActiveSession() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['login_time']) && 
           (time() - $_SESSION['login_time']) < 86400;
}

function refreshSession() {
    if (hasActiveSession()) {
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

function checkSessionTimeout($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            global $user, $lang;
            
            $user->logout();
            $_SESSION['session_timeout_message'] = 'Votre session a expiré. Veuillez vous reconnecter.';
            
            $loginUrl = $lang->pageUrl('login');
            header("Location: $loginUrl");
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
}

function isEmailVerified() {
    global $currentUser;
    return isset($currentUser['email_verified']) && $currentUser['email_verified'];
}

function requireEmailVerification() {
    if (!isEmailVerified()) {
        global $lang;
        
        $_SESSION['email_verification_required'] = 'Veuillez vérifier votre adresse email avant de continuer.';
        $verificationUrl = $lang->url('verify-email');
        header("Location: $verificationUrl");
        exit;
    }
}

function logSecurityEvent($event, $details = '') {
    global $currentUser;
    
    require_once __DIR__ . '/../classes/Database.php';
    $db = Database::getInstance();
    
    $db->logActivity(
        $currentUser['id'], 
        null, 
        $event, 
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    );
}

function detectSuspiciousActivity() {
    $suspicious = false;
    $reasons = [];
    
    if (isset($_SESSION['failed_attempts']) && $_SESSION['failed_attempts'] >= 3) {
        $suspicious = true;
        $reasons[] = 'Multiple failed attempts';
    }
    
    if (isset($_SESSION['login_locations'])) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $lastLocations = $_SESSION['login_locations'];
        
        if (count($lastLocations) > 0 && !in_array($currentIP, $lastLocations)) {
            $suspicious = true;
            $reasons[] = 'New location detected';
        }
    }
    
    if ($suspicious) {
        logSecurityEvent('suspicious_activity_detected', implode(', ', $reasons));
        
        $_SESSION['security_alert'] = 'Activité suspecte détectée. Veuillez vérifier votre compte.';
    }
    
    return $suspicious;
}

function trackUserLocation() {
    $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!isset($_SESSION['login_locations'])) {
        $_SESSION['login_locations'] = [];
    }
    
    if (!in_array($currentIP, $_SESSION['login_locations'])) {
        $_SESSION['login_locations'][] = $currentIP;
        
        if (count($_SESSION['login_locations']) > 5) {
            $_SESSION['login_locations'] = array_slice($_SESSION['login_locations'], -5);
        }
    }
}

function initSecuritySession() {
    trackUserLocation();
    detectSuspiciousActivity();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
    }
}

checkSessionTimeout();
refreshSession();
initSecuritySession();