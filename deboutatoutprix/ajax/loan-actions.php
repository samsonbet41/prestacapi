<?php

// Initialisation et sécurité
require_once '../includes/auth-admin.php';
require_once '../../classes/LoanRequest.php';
require_once '../../classes/Language.php';

// Seuls les admins avec la permission peuvent exécuter ce script
requirePermission('manage_loans');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Action non valide ou données manquantes.'];
$loanRequest = new LoanRequest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminId = $currentAdmin['id'];
    $result = null;

    // Pour les mises à jour de statut simples (envoyées en JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['action']) && $input['action'] === 'update_status') {
        $loanId = intval($input['loan_id'] ?? 0);
        $status = trim($input['status'] ?? '');
        
        if ($loanId > 0 && !empty($status)) {
            // Appel de votre méthode existante
            $result = $loanRequest->updateLoanRequestStatus($loanId, $status, $adminId, "Statut mis à jour par l'administrateur.");
        }
    }
    // Pour les soumissions de formulaires (Approbation / Rejet)
    else {
        $loanId = intval($_POST['loan_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Si c'est une approbation
        if (isset($_POST['approved_amount'])) {
            $approvedAmount = floatval($_POST['approved_amount']);
            $partnerBank = trim($_POST['partner_bank'] ?? 'PrestaCapi');
            
            if ($loanId > 0 && $approvedAmount > 0) {
                // Appel de votre méthode existante avec tous les paramètres d'approbation
                $result = $loanRequest->updateLoanRequestStatus($loanId, 'approved', $adminId, $notes, $approvedAmount, $partnerBank);
            }
        }
        // Si c'est un rejet
        elseif (isset($_POST['rejection_reason'])) {
            $reason = trim($_POST['rejection_reason'] . ' - ' . $notes);

            if ($loanId > 0 && !empty($reason)) {
                // Appel de votre méthode existante avec le motif du rejet
                $result = $loanRequest->updateLoanRequestStatus($loanId, 'rejected', $adminId, $reason);
            }
        }
    }

    if ($result) {
        $response = $result; // Utilise directement la réponse de votre méthode
    }
}

echo json_encode($response);
exit;