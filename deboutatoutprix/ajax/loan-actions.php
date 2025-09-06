<?php
// Bonne pratique : Démarrer un buffer de sortie pour capturer toute sortie non désirée
ob_start();

// Initialisation et sécurité
require_once '../includes/auth-admin.php';
require_once '../../classes/LoanRequest.php';
// Language est nécessaire car les méthodes dans LoanRequest l'utilisent pour les emails
require_once '../../classes/Language.php'; 

// Seuls les admins avec la permission peuvent exécuter ce script
requirePermission('manage_loans');

// --- 1. Initialisation de la réponse par défaut ---
$response = ['success' => false, 'message' => 'Action non valide ou données manquantes.'];
$loanRequest = new LoanRequest();
$adminId = $currentAdmin['id'];
$result = null;

// --- 2. Détection unifiée de la source des données (JSON ou Formulaire) ---
$input = [];
// Vérifie si la requête est de type JSON (envoyée par fetch avec un body JSON)
if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} 
// Sinon, on assume que ce sont des données de formulaire (envoyées par un <form> ou FormData)
else {
    $input = $_POST;
}

// Si aucune donnée n'a été trouvée, on arrête
if (empty($input)) {
    ob_end_clean(); // Nettoie le buffer avant d'envoyer la réponse JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// --- 3. Routeur d'actions ---
$action = $input['action'] ?? '';
$loanId = intval($input['loan_id'] ?? 0);

switch ($action) {

    // Action pour envoyer le rappel de documents (via JSON)
    case 'send_document_reminder':
        if ($loanId > 0) {
            $result = $loanRequest->sendDocumentReminderNotification($loanId);
        }
        break;

    // Action pour la mise à jour simple de statut (via JSON)
    case 'update_status':
        $status = trim($input['status'] ?? '');
        if ($loanId > 0 && !empty($status)) {
            $result = $loanRequest->updateLoanRequestStatus($loanId, $status, $adminId, "Statut mis à jour par l'administrateur.");
        }
        break;

    // Action pour l'approbation d'un prêt (via Formulaire/FormData)
    case 'approve':
        $approvedAmount = floatval($input['approved_amount'] ?? 0);
        $partnerBank = trim($input['partner_bank'] ?? 'PrestaCapi');
        $notes = trim($input['notes'] ?? '');
        
        if ($loanId > 0 && $approvedAmount > 0) {
            $result = $loanRequest->updateLoanRequestStatus($loanId, 'approved', $adminId, $notes, $approvedAmount, $partnerBank);
        } else {
            $response['message'] = 'ID de prêt ou montant approuvé invalide.';
        }
        break;
        
    // Action pour le rejet d'un prêt (via Formulaire/FormData)
    case 'reject':
        $reason = trim($input['rejection_reason'] ?? '');
        $notes = trim($input['notes'] ?? '');
        // On combine le motif prédéfini et les notes pour un message complet
        $fullReason = trim($reason . ' - ' . $notes);

        if ($loanId > 0 && !empty($fullReason)) {
            $result = $loanRequest->updateLoanRequestStatus($loanId, 'rejected', $adminId, $fullReason);
        } else {
            $response['message'] = 'ID de prêt ou motif de rejet invalide.';
        }
        break;

    // Actions en lot (bulk actions) - (votre logique existante ici)
    case 'bulk_review':
    case 'bulk_approve':
    case 'bulk_reject':
        // Mettez ici votre logique de traitement pour les actions en lot si nécessaire
        break;

    default:
        // Aucune action correspondante, le message par défaut sera envoyé.
        break;
}

// --- 4. Envoi de la réponse finale ---
if ($result !== null) {
    $response = $result;
}

// Bonne pratique : Nettoyer le buffer pour s'assurer que seule la réponse JSON est envoyée
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;