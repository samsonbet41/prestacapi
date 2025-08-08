<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Withdrawal.php';
require_once '../classes/Language.php';

session_start();

try {
    $user = new User();
    $withdrawal = new Withdrawal();
    $lang = Language::getInstance();
    
    if (!$user->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
        exit;
    }
    
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    $eligibilityCheck = $withdrawal->canUserRequestWithdrawal($userId);
    if (!$eligibilityCheck['can_request']) {
        echo json_encode(['success' => false, 'message' => $eligibilityCheck['reason']]);
        exit;
    }
    
    $requiredFields = ['amount', 'bank_name', 'account_number', 'account_holder_name'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
            exit;
        }
    }
    
    $amount = floatval($_POST['amount']);
    $maxAmount = $eligibilityCheck['max_amount'];
    
    if ($amount < 10) {
        echo json_encode(['success' => false, 'message' => 'Montant minimum : 10€']);
        exit;
    }
    
    if ($amount > $maxAmount) {
        echo json_encode(['success' => false, 'message' => "Montant maximum disponible : {$maxAmount}€"]);
        exit;
    }
    
    $bankName = trim($_POST['bank_name']);
    $accountNumber = trim($_POST['account_number']);
    $accountHolderName = trim($_POST['account_holder_name']);
    $swiftCode = trim($_POST['swift_code'] ?? '');
    $iban = trim($_POST['iban'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (strlen($bankName) < 2) {
        echo json_encode(['success' => false, 'message' => 'Nom de banque invalide']);
        exit;
    }
    
    if (strlen($accountNumber) < 5) {
        echo json_encode(['success' => false, 'message' => 'Numéro de compte invalide']);
        exit;
    }
    
    if (strlen($accountHolderName) < 2) {
        echo json_encode(['success' => false, 'message' => 'Nom du titulaire invalide']);
        exit;
    }
    
    if (!empty($iban) && !$withdrawal->validateBankDetails(['iban' => $iban])) {
        echo json_encode(['success' => false, 'message' => 'Format IBAN invalide']);
        exit;
    }
    
    if (!empty($swiftCode) && !preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', strtoupper($swiftCode))) {
        echo json_encode(['success' => false, 'message' => 'Format SWIFT/BIC invalide']);
        exit;
    }
    
    $withdrawalData = [
        'amount' => $amount,
        'bank_name' => $bankName,
        'account_number' => $accountNumber,
        'account_holder_name' => $accountHolderName,
        'swift_code' => $swiftCode,
        'iban' => $iban,
        'notes' => $notes
    ];
    
    $result = $withdrawal->createWithdrawalRequest($userId, $withdrawalData);
    
    if ($result['success']) {
        $db = Database::getInstance();
        $db->insert('notifications', [
            'user_id' => $userId,
            'type' => 'general',
            'title' => 'Demande de retrait créée',
            'message' => "Votre demande de retrait de {$amount}€ a été soumise et sera traitée sous 24-48h.",
            'related_id' => $result['withdrawal_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Demande de retrait soumise avec succès',
            'withdrawal_id' => $result['withdrawal_id'],
            'amount' => $amount,
            'processing_time' => '24-48 heures'
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Erreur AJAX withdrawal-request: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue']);
}