<?php
header('Content-Type: application/json');
session_start();

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Language.php';

$lang = Language::getInstance();
$user = new User();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'];
    
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email et mot de passe requis'
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Adresse email invalide'
        ]);
        exit;
    }
    
    $result = $user->login($email, $password, $rememberMe);
    
    if ($result['success']) {
        $db = Database::getInstance();
        $db->logActivity($_SESSION['user_id'], null, 'user_login_ajax', 'Connexion via AJAX', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur login AJAX: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur interne est survenue'
    ]);
}