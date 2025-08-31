<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/Document.php';
header('Content-Type: application/json');

// Vérifier si l'admin a la permission
if (!hasPermission('manage_documents')) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['document_id']) || !isset($data['is_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

$documentId = intval($data['document_id']);
$isVerified = boolval($data['is_verified']);
$notes = trim($data['notes'] ?? '');
$adminId = $currentAdmin['id']; // Vient de auth-admin.php

$doc_obj = new Document();
$result = $doc_obj->verifyDocument($documentId, $adminId, $isVerified, $notes);

echo json_encode($result);