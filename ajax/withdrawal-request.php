<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/app.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// AJOUT DES DÉPENDANCES MANQUANTES
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Withdrawal.php';
require_once '../classes/Mailer.php';
require_once '../classes/Language.php';

// session_start(); // This line was removed in the new string, which is a functional change, not an escaping issue.

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Jeton de sécurité invalide ou expiré. Veuillez rafraîchir la page.']);
    exit;
}

try {
    $user = new User();
    $withdrawal = new Withdrawal();
    $lang = Language::getInstance();
    
    if (!$user->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action.']);
        exit;
    }
    
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    // Vérifier si l'utilisateur peut faire un retrait
    $eligibilityCheck = $withdrawal->canUserRequestWithdrawal($userId);
    if (!$eligibilityCheck['can_request']) {
        echo json_encode(['success' => false, 'message' => $eligibilityCheck['reason']]);
        exit;
    }
    
    // Rassembler les données du formulaire
    $withdrawalData = [
        'amount' => $_POST['amount'] ?? 0,
        'bank_name' => $_POST['bank_name'] ?? '',
        'account_number' => $_POST['account_number'] ?? '',
        'account_holder_name' => $_POST['account_holder_name'] ?? '',
        'swift_code' => $_POST['swift_code'] ?? '',
        'iban' => $_POST['iban'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    // AMÉLIORATION DE LA LOGIQUE DE VALIDATION
    $errors = [];
    $amount = filter_var($withdrawalData['amount'], FILTER_VALIDATE_FLOAT);
    $maxAmount = $eligibilityCheck['max_amount'];

    if ($amount === false || $amount <= 0) {
        $errors['amount'] = 'Le montant est invalide.';
    } elseif ($amount < 10) {
        $errors['amount'] = 'Le montant minimum pour un retrait est de 10€.';
    } elseif ($amount > $maxAmount) {
        $errors['amount'] = "Le montant dépasse votre solde disponible ({$maxAmount}€).";
    }

    $bankDetailsErrors = $withdrawal->validateBankDetails($withdrawalData);
    if (!empty($bankDetailsErrors)) {
        // Mappage simplifié pour l'exemple
        if (in_array('Nom de banque requis', $bankDetailsErrors)) $errors['bank_name'] = 'Le nom de la banque est requis.';
        if (in_array('Numéro de compte requis', $bankDetailsErrors)) $errors['account_number'] = 'Le numéro de compte est requis.';
        if (in_array('Nom du titulaire requis', $bankDetailsErrors)) $errors['account_holder_name'] = 'Le nom du titulaire est requis.';
        if (in_array('Format IBAN invalide', $bankDetailsErrors)) $errors['iban'] = 'Le format de l\'IBAN est invalide.';
        if (in_array('Format SWIFT/BIC invalide', $bankDetailsErrors)) $errors['swift_code'] = 'Le format du code SWIFT/BIC est invalide.';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez corriger les erreurs dans le formulaire.',
            'errors' => $errors
        ]);
        exit;
    }

    // Si tout est valide, on crée la demande
    $result = $withdrawal->createWithdrawalRequest($userId, $withdrawalData);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur AJAX withdrawal-request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur technique est survenue. Veuillez réessayer plus tard.']);
}
