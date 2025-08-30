<?php
header('Content-Type: application/json');

// Vérifie que la méthode de requête est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Méthode non autorisée
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Document.php';

session_start();

try {
    // Vérifie si l'utilisateur est connecté
    $user = new User();
    if (!$user->isLoggedIn()) {
        http_response_code(401); // Non autorisé
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer cette action']);
        exit;
    }
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];

    // Récupère les données JSON envoyées par le JavaScript
    $data = json_decode(file_get_contents('php://input'), true);

    // Vérifie que l'ID du document est bien présent
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['document_id'])) {
        http_response_code(400); // Mauvaise requête
        echo json_encode(['success' => false, 'message' => 'ID de document manquant ou invalide']);
        exit;
    }

    $documentId = intval($data['document_id']);

    // Appelle la méthode pour supprimer le document
    $document = new Document();
    // Le second paramètre $userId garantit que l'utilisateur ne peut supprimer que ses propres documents
    $result = $document->deleteDocument($documentId, $userId);

    // Renvoie le résultat de l'opération
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Erreur AJAX delete-document: " . $e->getMessage());
    http_response_code(500); // Erreur interne du serveur
    echo json_encode(['success' => false, 'message' => 'Une erreur interne est survenue lors de la suppression']);
}