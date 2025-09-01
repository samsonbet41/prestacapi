<?php
header('Content-Type: application/json');
session_start();

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Language.php';
require_once '../classes/Mailer.php';

$lang = Language::getInstance();
$user = new User();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'phone' => trim($_POST['phone'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'terms' => isset($_POST['terms'])
    ];
    
    $errors = [];
    
    if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
        $errors[] = 'Prénom requis (minimum 2 caractères)';
    }
    
    if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
        $errors[] = 'Nom de famille requis (minimum 2 caractères)';
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email valide requise';
    }
    
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors[] = 'Mot de passe requis (minimum 8 caractères)';
    }
    
    if ($data['password'] !== $data['password_confirm']) {
        $errors[] = 'Les mots de passe ne correspondent pas';
    }
    
    if (!$data['terms']) {
        $errors[] = 'Vous devez accepter les conditions d\'utilisation';
    }
    
    if (!empty($data['phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['phone'])) {
        $errors[] = 'Format de téléphone invalide';
    }
    
    if (!empty($data['whatsapp']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['whatsapp'])) {
        $errors[] = 'Format WhatsApp invalide';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false, 
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
    
    if ($existingUser) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cette adresse email est déjà utilisée'
        ]);
        exit;
    }
    $currentLang = $lang->getCurrentLanguage();
    
    $result = $user->register($data, $currentLang);
    
    if ($result['success']) {
        $db->logActivity($result['user_id'], null, 'user_register_ajax', 'Inscription via AJAX', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        
        $mailer = new Mailer();
        $mailer->sendAdminNotification('new_user', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email']
        ]);
    }
        
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur inscription AJAX: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur interne est survenue'
    ]);
}