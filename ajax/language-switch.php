<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Language.php';

session_start();

try {
    $lang = Language::getInstance();
    
    if (empty($_POST['language'])) {
        echo json_encode(['success' => false, 'message' => 'Langue non spécifiée']);
        exit;
    }
    
    $newLanguage = $_POST['language'];
    
    if (!$lang->isLanguageSupported($newLanguage)) {
        echo json_encode(['success' => false, 'message' => 'Langue non supportée']);
        exit;
    }
    
    $currentPath = $_POST['current_path'] ?? '/';
    $currentPath = parse_url($currentPath, PHP_URL_PATH);
    
    $currentPath = preg_replace('#^/(' . implode('|', $lang->getSupportedLanguages()) . ')/#', '', $currentPath);
    $currentPath = ltrim($currentPath, '/');
    
    $lang->setCurrentLanguage($newLanguage);
    
    $newUrl = $lang->url($currentPath, $newLanguage);
    
    $languageName = $lang->getLanguageName($newLanguage);
    
    if (!headers_sent()) {
        setcookie('preferred_language', $newLanguage, time() + (86400 * 365), '/', '', true, true);
    }
    
    if (isset($_SESSION['user_id'])) {
        require_once '../classes/Database.php';
        $db = Database::getInstance();
        
        try {
            $preferences = $db->fetchOne("SELECT preferences FROM users WHERE id = ?", [$_SESSION['user_id']])['preferences'] ?? '{}';
            $userPrefs = json_decode($preferences, true) ?: [];
            $userPrefs['preferred_language'] = $newLanguage;
            
            $db->update('users', ['preferences' => json_encode($userPrefs)], 'id = ?', [$_SESSION['user_id']]);
            $db->logActivity($_SESSION['user_id'], null, 'language_changed', "Langue changée vers: $languageName");
        } catch (Exception $e) {
            error_log("Erreur sauvegarde langue utilisateur: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Langue changée vers $languageName",
        'language' => $newLanguage,
        'language_name' => $languageName,
        'redirect_url' => $newUrl,
        'current_language' => $newLanguage
    ]);
    
} catch (Exception $e) {
    error_log("Erreur AJAX language-switch: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors du changement de langue']);
}