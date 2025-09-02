<?php
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/Language.php';
require_once __DIR__ . '/User.php';

class Document {
    private $db;
    private $uploadDir;
    private $maxFileSize = 5242880;
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/documents/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        $this->createDirectoryStructure();
    }
    
    private function createDirectoryStructure() {
        $subdirs = ['identity', 'income', 'bank', 'employment', 'birth', 'other'];
        
        foreach ($subdirs as $subdir) {
            $path = $this->uploadDir . $subdir . '/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
    
    public function uploadDocument($userId, $file, $documentType, $loanRequestId = null) {
        try {
            if (!$this->isValidDocumentType($documentType)) {
                return ['success' => false, 'message' => 'Type de document invalide'];
            }
            
            $validation = $this->validateFile($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            $user = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            $existingDoc = $this->db->fetchOne("
                SELECT id FROM documents 
                WHERE user_id = ? AND document_type = ? AND loan_request_id IS NULL
            ", [$userId, $documentType]);
            
            $fileName = $this->generateFileName($userId, $documentType, pathinfo($file['name'], PATHINFO_EXTENSION));
            $targetDir = $this->uploadDir . $this->getDocumentTypeFolder($documentType) . '/';
            $targetPath = $targetDir . $fileName;
            $webPath = 'uploads/documents/' . $this->getDocumentTypeFolder($documentType) . '/' . $fileName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier'];
            }
            
            if ($existingDoc) {
                $oldDoc = $this->db->fetchOne("SELECT file_path FROM documents WHERE id = ?", [$existingDoc['id']]);
                if ($oldDoc && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $oldDoc['file_path'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $oldDoc['file_path']);
                }
                
                $this->db->update('documents', [
                    'file_name' => $fileName,
                    'file_path' => $webPath,
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'is_verified' => false,
                    'verified_by' => null,
                    'verified_at' => null,
                    'uploaded_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$existingDoc['id']]);
                
                $documentId = $existingDoc['id'];
                $action = 'document_updated';
                
            } else {
                $documentData = [
                    'user_id' => $userId,
                    'loan_request_id' => $loanRequestId,
                    'document_type' => $documentType,
                    'file_name' => $fileName,
                    'file_path' => $webPath,
                    'file_size' => $file['size'],
                    'mime_type' => $file['type']
                ];
                
                $documentId = $this->db->insert('documents', $documentData);
                $action = 'document_uploaded';
            }
            
            $this->db->logActivity($userId, null, $action, "Document $documentType uploadé: $fileName");
            
            try {
                $lang = Language::getInstance();
                $userFullData = $this->db->fetchOne("SELECT first_name, email FROM users WHERE id = ?", [$userId]);

                $mailer = new Mailer();

                $mailer->sendAdminNotification('document_uploaded', [
                    'user_id' => $userId,
                    'document_type' => $this->getDocumentTypeName($documentType),
                    'file_name' => $fileName,
                    'file_path' => $targetPath,
                    'original_name' => $file['name']
                ]);

                if ($userFullData) {
                    $mailer->sendDocumentReceivedEmail(
                        $userFullData,
                        $this->getDocumentTypeName($documentType),
                        $fileName,
                        $lang->getCurrentLanguage() 
                    );
                }

            } catch (Exception $e) {
                error_log("Erreur Mailer dans uploadDocument: " . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'document_id' => $documentId,
                'file_path' => $webPath
            ];
            
        } catch (Exception $e) {
            error_log("Erreur upload document: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'upload du document'];
        }
    }
    
    public function getUserDocuments($userId, $groupByType = true) {
        $documents = $this->db->fetchAll("
            SELECT * FROM documents 
            WHERE user_id = ? 
            ORDER BY document_type, uploaded_at DESC
        ", [$userId]);
        
        if (!$groupByType) {
            return $documents;
        }
        
        $grouped = [];
        foreach ($documents as $doc) {
            $grouped[$doc['document_type']][] = $doc;
        }
        
        return $grouped;
    }
    
    public function getDocumentById($documentId, $userId = null) {
        $sql = "SELECT d.*, u.first_name, u.last_name 
                FROM documents d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.id = ?";
        $params = [$documentId];
        
        if ($userId) {
            $sql .= " AND d.user_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    public function verifyDocument($documentId, $adminId, $isVerified = true, $notes = '') {
        try {
            $document = $this->getDocumentById($documentId);
            if (!$document) {
                return ['success' => false, 'message' => 'Document non trouvé'];
            }

            // Si le document est déjà dans l'état souhaité, on évite une opération inutile.
            if ($document['is_verified'] == ($isVerified ? 1 : 0)) {
                return ['success' => true, 'message' => 'Le document est déjà dans cet état.'];
            }
            
            $updateData = [
                'is_verified' => $isVerified ? 1 : 0,
                'verified_by' => $adminId,
                'verified_at' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ];

            // On récupère le nombre de lignes réellement modifiées grâce à notre nouvelle méthode update.
            $affectedRows = $this->db->update('documents', $updateData, 'id = ?', [$documentId]);

            // On ne confirme le succès QUE si une ligne a été modifiée.
            if ($affectedRows > 0) {
                $this->db->logActivity($document['user_id'], $adminId, 'document_verified', 
                    "Document {$document['document_type']} " . ($isVerified ? 'vérifié' : 'rejeté'));

                $notification_title = $isVerified ? 'Document vérifié' : 'Document rejeté';
                $notification_message = $isVerified 
                    ? "Votre document ({$this->getDocumentTypeName($document['document_type'])}) a été vérifié."
                    : "Votre document ({$this->getDocumentTypeName($document['document_type'])}) a été rejeté. Motif: " . $notes;
                
                $this->db->insert('notifications', [
                    'user_id' => $document['user_id'],
                    'type' => $isVerified ? 'document_verified' : 'document_rejected',
                    'title' => $notification_title,
                    'message' => $notification_message,
                    'related_id' => $documentId
                ]);

                try {
                    $user_obj = new User(); // Assurez-vous que la classe User est disponible
                    $userData = $user_obj->getUserById($document['user_id']);
                    
                    if ($userData) {
                        $mailer = new Mailer();
                        // La langue de l'utilisateur est stockée dans la base de données
                        $userLanguage = $userData['language'] ?? 'fr';
                        $documentTypeName = $this->getDocumentTypeName($document['document_type']);

                        if ($isVerified) {
                            $mailer->sendDocumentVerifiedEmail($userData, $documentTypeName, $userLanguage);
                        } else {
                            $mailer->sendDocumentRejectedEmail($userData, $documentTypeName, $notes, $userLanguage);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erreur Mailer dans verifyDocument: " . $e->getMessage());
                }
                
                return ['success' => true, 'message' => 'Statut du document mis à jour avec succès'];
            } else {
                // Si aucune ligne n'a été modifiée, on renvoie une erreur.
                return ['success' => false, 'message' => 'La mise à jour en base de données a échoué (0 ligne modifiée).'];
            }

        } catch (Exception $e) {
            error_log("Erreur vérification document: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique du serveur: ' . $e->getMessage()];
        }
    }
        
    public function deleteDocument($documentId, $userId = null) {
        try {
            $sql = "SELECT * FROM documents WHERE id = ?";
            $params = [$documentId];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $document = $this->db->fetchOne($sql, $params);
            if (!$document) {
                return ['success' => false, 'message' => 'Document non trouvé'];
            }
            
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $document['file_path'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $document['file_path']);
            }
            
            $this->db->delete('documents', 'id = ?', [$documentId]);
            
            $this->db->logActivity($document['user_id'], null, 'document_deleted', 
                "Document {$document['document_type']} supprimé: {$document['file_name']}");
            
            return ['success' => true, 'message' => 'Document supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression document: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    public function getUserDocumentStatus($userId) {
        $requiredDocs = ['identity', 'income_proof', 'bank_statement'];
        $userDocs = $this->getUserDocuments($userId, true);
        
        $status = [
            'total_required' => count($requiredDocs),
            'uploaded' => 0,
            'verified' => 0,
            'missing' => [],
            'pending_verification' => [],
            'rejected' => [],
            'completion_percentage' => 0
        ];
        
        foreach ($requiredDocs as $docType) {
            if (isset($userDocs[$docType]) && !empty($userDocs[$docType])) {
                $status['uploaded']++;
                $latestDoc = $userDocs[$docType][0];
                
                if ($latestDoc['is_verified']) {
                    $status['verified']++;
                } else {
                    $status['pending_verification'][] = $docType;
                }
            } else {
                $status['missing'][] = $docType;
            }
        }
        
        $status['completion_percentage'] = round(($status['verified'] / $status['total_required']) * 100);
        
        return $status;
    }
    
    public function getAllDocuments($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT d.*, u.first_name, u.last_name, u.email 
                FROM documents d 
                JOIN users u ON d.user_id = u.id";
        
        $params = [];
        
        if ($status === 'pending') {
            $sql .= " WHERE d.is_verified = 0";
        } elseif ($status === 'verified') {
            $sql .= " WHERE d.is_verified = 1";
        }
        
        $sql .= " ORDER BY d.uploaded_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getDocumentStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count("SELECT COUNT(*) FROM documents");
        $stats['pending'] = $this->db->count("SELECT COUNT(*) FROM documents WHERE is_verified = 0");
        $stats['verified'] = $this->db->count("SELECT COUNT(*) FROM documents WHERE is_verified = 1");
        
        $typeStats = $this->db->fetchAll("
            SELECT document_type, COUNT(*) as count 
            FROM documents 
            GROUP BY document_type 
            ORDER BY count DESC
        ");
        
        $stats['by_type'] = [];
        foreach ($typeStats as $type) {
            $stats['by_type'][$type['document_type']] = $type['count'];
        }
        
        return $stats;
    }
    
    private function validateFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier'];
        }
        
        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'Fichier trop volumineux (maximum 5MB)'];
        }
        
        if ($file['size'] === 0) {
            return ['success' => false, 'message' => 'Fichier vide'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou PDF'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'message' => 'Extension de fichier non autorisée'];
        }
        
        if ($mimeType === 'application/pdf') {
            if (!$this->isPdfValid($file['tmp_name'])) {
                return ['success' => false, 'message' => 'Fichier PDF corrompu ou invalide'];
            }
        }
        
        return ['success' => true, 'message' => 'Fichier valide'];
    }
    
    private function isPdfValid($filePath) {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 4);
        fclose($handle);
        
        return $header === '%PDF';
    }
    
    private function isValidDocumentType($type) {
        $validTypes = ['identity', 'birth_certificate', 'income_proof', 'bank_statement', 'employment_certificate', 'other'];
        return in_array($type, $validTypes);
    }
    
    private function getDocumentTypeFolder($type) {
        $folders = [
            'identity' => 'identity',
            'birth_certificate' => 'birth',
            'income_proof' => 'income',
            'bank_statement' => 'bank',
            'employment_certificate' => 'employment',
            'other' => 'other'
        ];
        
        return $folders[$type] ?? 'other';
    }
    
    private function generateFileName($userId, $documentType, $extension) {
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "user_{$userId}_{$documentType}_{$timestamp}_{$random}.{$extension}";
    }
    
    public function getDocumentTypeName($type) {
        $lang = Language::getInstance();
        $key = 'document_type_' . $type;
        return $lang->get($key, ucfirst(str_replace('_', ' ', $type)));
    }
    
    public function getDocumentTypeDescription($type) {
        $lang = Language::getInstance();
        $key = 'document_type_' . $type . '_desc';
        return $lang->get($key, '');
    }
    
    public function getMissingDocuments($userId, $loanAmount = 0) {
        $userDocs = $this->getUserDocuments($userId, true);
        $requiredDocs = ['identity', 'income_proof', 'bank_statement'];
        
        if ($loanAmount >= 10000) {
            $requiredDocs[] = 'employment_certificate';
        }
        
        if ($loanAmount >= 20000) {
            $requiredDocs[] = 'birth_certificate';
        }
        
        $missing = [];
        
        foreach ($requiredDocs as $docType) {
            if (!isset($userDocs[$docType]) || empty($userDocs[$docType])) {
                $missing[] = [
                    'type' => $docType,
                    'name' => $this->getDocumentTypeName($docType),
                    'description' => $this->getDocumentTypeDescription($docType)
                ];
            } else {
                $latestDoc = $userDocs[$docType][0];
                if (!$latestDoc['is_verified']) {
                    $missing[] = [
                        'type' => $docType,
                        'name' => $this->getDocumentTypeName($docType),
                        'description' => 'Document en cours de vérification',
                        'status' => 'pending'
                    ];
                }
            }
        }
        
        return $missing;
    }
    
    public function compressImage($sourcePath, $quality = 80) {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        $maxWidth = 1920;
        $maxHeight = 1080;
        
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($newImage, $sourcePath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($newImage, $sourcePath, 8);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($newImage, $sourcePath);
                    break;
            }
            
            imagedestroy($newImage);
        } else {
            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($source, $sourcePath, $quality);
                    break;
            }
        }
        
        imagedestroy($source);
        return true;
    }
}