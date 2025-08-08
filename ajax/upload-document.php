<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Document.php';
require_once '../classes/Language.php';

session_start();

try {
    $user = new User();
    $document = new Document();
    $lang = Language::getInstance();
    
    if (!$user->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
        exit;
    }
    
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    if (empty($_POST['document_type'])) {
        echo json_encode(['success' => false, 'message' => 'Type de document requis']);
        exit;
    }
    
    if (empty($_FILES['document_file'])) {
        echo json_encode(['success' => false, 'message' => 'Aucun fichier sélectionné']);
        exit;
    }
    
    $documentType = $_POST['document_type'];
    $file = $_FILES['document_file'];
    $loanRequestId = !empty($_POST['loan_request_id']) ? intval($_POST['loan_request_id']) : null;
    
    $validTypes = ['identity', 'birth_certificate', 'income_proof', 'bank_statement', 'employment_certificate', 'other'];
    if (!in_array($documentType, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Type de document invalide']);
        exit;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier est trop volumineux',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille autorisée',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier',
            UPLOAD_ERR_EXTENSION => 'Upload stoppé par une extension'
        ];
        
        $message = $errorMessages[$file['error']] ?? 'Erreur d\'upload inconnue';
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    
    if ($file['size'] > 5242880) {
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (maximum 5MB)']);
        exit;
    }
    
    $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou PDF']);
        exit;
    }
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Extension de fichier non autorisée']);
        exit;
    }
    
    if ($mimeType === 'application/pdf') {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            
            if ($header !== '%PDF') {
                echo json_encode(['success' => false, 'message' => 'Fichier PDF corrompu']);
                exit;
            }
        }
    }
    
    $result = $document->uploadDocument($userId, $file, $documentType, $loanRequestId);
    
    if ($result['success']) {
        $documentTypeName = $document->getDocumentTypeName($documentType);
        
        echo json_encode([
            'success' => true,
            'message' => "Document '{$documentTypeName}' uploadé avec succès",
            'document_id' => $result['document_id'],
            'file_path' => $result['file_path'],
            'document_type' => $documentType,
            'document_type_name' => $documentTypeName,
            'verification_status' => 'pending'
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Erreur AJAX upload-document: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'upload']);
}