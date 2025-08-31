<?php
header('Content-Type: application/json');
session_start();

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/LoanRequest.php';
require_once '../classes/Language.php';
require_once '../classes/Mailer.php';

$lang = Language::getInstance();
$user = new User();
$loanRequest = new LoanRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise']);
    exit;
}

try {
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    $data = [
        'amount' => floatval($_POST['amount'] ?? 0),
        'duration' => intval($_POST['duration'] ?? 0),
        'purpose' => trim($_POST['purpose'] ?? ''),
        'monthly_income' => floatval($_POST['monthly_income'] ?? 0),
        'monthly_expenses' => floatval($_POST['monthly_expenses'] ?? 0),
        'employment_status' => trim($_POST['employment_status'] ?? ''),
        'employment_duration' => intval($_POST['employment_duration'] ?? 0),
        'employer_name' => trim($_POST['employer_name'] ?? ''),
        'employer_phone' => trim($_POST['employer_phone'] ?? ''),
        'other_loans' => floatval($_POST['other_loans'] ?? 0),
        'collateral' => trim($_POST['collateral'] ?? ''),
        'co_signer_name' => trim($_POST['co_signer_name'] ?? ''),
        'co_signer_phone' => trim($_POST['co_signer_phone'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'consent_verification' => isset($_POST['consent_verification']),
        'consent_processing' => isset($_POST['consent_processing']),
        'consent_terms' => isset($_POST['consent_terms'])
    ];
    
    $errors = [];
    
    if ($data['amount'] < 500 || $data['amount'] > 50000) {
        $errors[] = 'Le montant doit être entre 500€ et 50 000€';
    }
    
    if ($data['duration'] < 6 || $data['duration'] > 60) {
        $errors[] = 'La durée doit être entre 6 et 60 mois';
    }
    
    if (empty($data['purpose']) || strlen($data['purpose']) < 3) {
        $errors[] = 'Objectif du prêt requis (minimum 3 caractères)';
    }
    
    if ($data['monthly_income'] <= 0) {
        $errors[] = 'Revenus mensuels requis';
    }
    
    if ($data['monthly_expenses'] < 0) {
        $errors[] = 'Charges mensuelles invalides';
    }
    
    if ($data['monthly_expenses'] >= $data['monthly_income']) {
        $errors[] = 'Les charges ne peuvent pas être supérieures aux revenus';
    }
    
    if (empty($data['employment_status'])) {
        $errors[] = 'Statut d\'emploi requis';
    }
    
    if ($data['employment_duration'] <= 0) {
        $errors[] = 'Durée d\'emploi requise';
    }
    
    if (!$data['consent_verification']) {
        $errors[] = 'Consentement pour vérification requis';
    }
    
    if (!$data['consent_processing']) {
        $errors[] = 'Consentement pour traitement requis';
    }
    
    if (!$data['consent_terms']) {
        $errors[] = 'Acceptation des conditions générales requise';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false, 
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $activeLoan = $db->fetchOne("
        SELECT id FROM loan_requests 
        WHERE user_id = ? AND status IN ('pending', 'under_review') 
        LIMIT 1
    ", [$userId]);
    
    if ($activeLoan) {
        echo json_encode([
            'success' => false, 
            'message' => 'Vous avez déjà une demande de prêt en cours'
        ]);
        exit;
    }
    
    $result = $loanRequest->createLoanRequest($userId, $data);
    
    if ($result['success']) {
        $eligibility = $loanRequest->calculateLoanEligibility($currentUser, $data);
        
        $db->update('loan_requests', [
            'notes' => 'Score d\'éligibilité: ' . $eligibility['score'] . '/100. Recommandation: ' . $eligibility['recommendation']
        ], 'id = ?', [$result['loan_id']]);
        
        $stats = $user->getDashboardStats($userId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Demande de prêt soumise avec succès ! Vous recevrez une réponse sous 24-48h.',
            'loan_id' => $result['loan_id'],
            'eligibility_score' => $eligibility['score'],
            'recommendation' => $eligibility['recommendation'],
            'stats' => $stats
        ]);
        
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Erreur demande prêt AJAX: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur interne est survenue'
    ]);
}