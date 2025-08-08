<?php
require_once 'Database.php';

class Partner {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createPartner($data) {
        try {
            $errors = $this->validatePartnerData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $partnerData = [
                'name' => trim($data['name']),
                'type' => $data['type'],
                'logo' => $data['logo'] ?? '',
                'description' => trim($data['description'] ?? ''),
                'website' => $data['website'] ?? '',
                'contact_email' => $data['contact_email'] ?? '',
                'contact_phone' => $data['contact_phone'] ?? '',
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                'display_order' => intval($data['display_order'] ?? 0)
            ];
            
            $partnerId = $this->db->insert('partners', $partnerData);
            
            if ($partnerId) {
                $this->db->logActivity(null, null, 'partner_created', "Nouveau partenaire ajouté: {$partnerData['name']}");
                return [
                    'success' => true,
                    'message' => 'Partenaire ajouté avec succès',
                    'partner_id' => $partnerId
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création du partenaire'];
            
        } catch (Exception $e) {
            error_log("Erreur création partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création'];
        }
    }
    
    public function updatePartner($partnerId, $data) {
        try {
            $existingPartner = $this->getPartnerById($partnerId);
            if (!$existingPartner) {
                return ['success' => false, 'message' => 'Partenaire non trouvé'];
            }
            
            $errors = $this->validatePartnerData($data, $partnerId);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $updateData = [
                'name' => trim($data['name']),
                'type' => $data['type'],
                'logo' => $data['logo'] ?? $existingPartner['logo'],
                'description' => trim($data['description'] ?? ''),
                'website' => $data['website'] ?? '',
                'contact_email' => $data['contact_email'] ?? '',
                'contact_phone' => $data['contact_phone'] ?? '',
                'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : $existingPartner['is_active'],
                'display_order' => intval($data['display_order'] ?? $existingPartner['display_order'])
            ];
            
            $this->db->update('partners', $updateData, 'id = ?', [$partnerId]);
            $this->db->logActivity(null, null, 'partner_updated', "Partenaire mis à jour: {$updateData['name']}");
            
            return ['success' => true, 'message' => 'Partenaire mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour'];
        }
    }
    
    public function deletePartner($partnerId) {
        try {
            $partner = $this->getPartnerById($partnerId);
            if (!$partner) {
                return ['success' => false, 'message' => 'Partenaire non trouvé'];
            }
            
            if (!empty($partner['logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $partner['logo'])) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $partner['logo']);
            }
            
            $this->db->delete('partners', 'id = ?', [$partnerId]);
            $this->db->logActivity(null, null, 'partner_deleted', "Partenaire supprimé: {$partner['name']}");
            
            return ['success' => true, 'message' => 'Partenaire supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la suppression'];
        }
    }
    
    public function getPartnerById($partnerId) {
        return $this->db->fetchOne("SELECT * FROM partners WHERE id = ?", [$partnerId]);
    }
    
    public function getAllPartners($activeOnly = false, $type = null) {
        $sql = "SELECT * FROM partners";
        $params = [];
        $conditions = [];
        
        if ($activeOnly) {
            $conditions[] = "is_active = 1";
        }
        
        if ($type) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY display_order ASC, name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getActivePartners($limit = null, $type = null) {
        $sql = "SELECT * FROM partners WHERE is_active = 1";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY display_order ASC, name ASC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getPartnersByType($type) {
        return $this->db->fetchAll("
            SELECT * FROM partners 
            WHERE type = ? AND is_active = 1 
            ORDER BY display_order ASC, name ASC
        ", [$type]);
    }
    
    public function getFeaturedPartners($limit = 12) {
        return $this->db->fetchAll("
            SELECT * FROM partners 
            WHERE is_active = 1 AND logo IS NOT NULL AND logo != '' 
            ORDER BY display_order ASC, name ASC 
            LIMIT ?
        ", [$limit]);
    }
    
    public function searchPartners($query, $limit = 20) {
        return $this->db->fetchAll("
            SELECT * FROM partners 
            WHERE (name LIKE ? OR description LIKE ? OR website LIKE ?) 
            ORDER BY name ASC 
            LIMIT ?
        ", ["%$query%", "%$query%", "%$query%", $limit]);
    }
    
    public function togglePartnerStatus($partnerId) {
        try {
            $partner = $this->getPartnerById($partnerId);
            if (!$partner) {
                return ['success' => false, 'message' => 'Partenaire non trouvé'];
            }
            
            $newStatus = !$partner['is_active'];
            $this->db->update('partners', ['is_active' => $newStatus], 'id = ?', [$partnerId]);
            
            $statusText = $newStatus ? 'activé' : 'désactivé';
            $this->db->logActivity(null, null, 'partner_status_changed', "Partenaire {$statusText}: {$partner['name']}");
            
            return [
                'success' => true,
                'message' => "Partenaire {$statusText} avec succès",
                'new_status' => $newStatus
            ];
            
        } catch (Exception $e) {
            error_log("Erreur changement statut partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue'];
        }
    }
    
    public function updateDisplayOrder($partnerId, $newOrder) {
        try {
            $this->db->update('partners', ['display_order' => intval($newOrder)], 'id = ?', [$partnerId]);
            $this->db->logActivity(null, null, 'partner_order_updated', "Ordre d'affichage mis à jour pour le partenaire ID: $partnerId");
            
            return ['success' => true, 'message' => 'Ordre mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour ordre partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue'];
        }
    }
    
    public function getPartnerStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count("SELECT COUNT(*) FROM partners");
        $stats['active'] = $this->db->count("SELECT COUNT(*) FROM partners WHERE is_active = 1");
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        $typeStats = $this->db->fetchAll("
            SELECT type, COUNT(*) as count 
            FROM partners 
            WHERE is_active = 1 
            GROUP BY type 
            ORDER BY count DESC
        ");
        
        $stats['by_type'] = [];
        foreach ($typeStats as $type) {
            $stats['by_type'][$type['type']] = $type['count'];
        }
        
        $stats['with_logo'] = $this->db->count("SELECT COUNT(*) FROM partners WHERE is_active = 1 AND logo IS NOT NULL AND logo != ''");
        $stats['with_website'] = $this->db->count("SELECT COUNT(*) FROM partners WHERE is_active = 1 AND website IS NOT NULL AND website != ''");
        
        return $stats;
    }
    
    public function uploadLogo($file, $partnerId = null) {
        try {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/images/partners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $maxSize = 2097152;
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
            }
            
            if ($file['size'] > $maxSize) {
                return ['success' => false, 'message' => 'Fichier trop volumineux (max 2MB)'];
            }
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                return ['success' => false, 'message' => 'Type de fichier non autorisé'];
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'partner_' . ($partnerId ?: 'new') . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            $webPath = '/images/partners/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'message' => 'Erreur lors de l\'enregistrement'];
            }
            
            if ($mimeType !== 'image/svg+xml') {
                $this->optimizeLogo($targetPath);
            }
            
            return [
                'success' => true,
                'message' => 'Logo uploadé avec succès',
                'file_path' => $webPath,
                'file_name' => $filename
            ];
            
        } catch (Exception $e) {
            error_log("Erreur upload logo partenaire: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
        }
    }
    
    public function generateCarouselHTML($partners, $infinite = true) {
        if (empty($partners)) {
            return '<div class="partners-empty">Aucun partenaire à afficher</div>';
        }
        
        $html = '<div class="partners-carousel' . ($infinite ? ' infinite' : '') . '">';
        $html .= '<div class="partners-track">';
        
        $partnersToShow = $infinite ? array_merge($partners, $partners) : $partners;
        
        foreach ($partnersToShow as $partner) {
            $html .= '<div class="partner-item">';
            
            if (!empty($partner['logo'])) {
                $html .= '<img src="' . htmlspecialchars($partner['logo']) . '" ';
                $html .= 'alt="' . htmlspecialchars($partner['name']) . '" ';
                $html .= 'class="partner-logo" ';
                $html .= 'loading="lazy">';
            } else {
                $html .= '<div class="partner-name">' . htmlspecialchars($partner['name']) . '</div>';
            }
            
            if (!empty($partner['website'])) {
                $html = str_replace('<div class="partner-item">', '<a href="' . htmlspecialchars($partner['website']) . '" target="_blank" rel="noopener" class="partner-item partner-link">', $html);
                $html .= '</a>';
            } else {
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    public function generatePartnerGrid($partners, $columns = 4) {
        if (empty($partners)) {
            return '<div class="partners-empty">Aucun partenaire à afficher</div>';
        }
        
        $html = '<div class="partners-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
        
        foreach ($partners as $partner) {
            $html .= '<div class="partner-card">';
            
            if (!empty($partner['logo'])) {
                $html .= '<div class="partner-logo-container">';
                $html .= '<img src="' . htmlspecialchars($partner['logo']) . '" ';
                $html .= 'alt="' . htmlspecialchars($partner['name']) . '" ';
                $html .= 'class="partner-logo" loading="lazy">';
                $html .= '</div>';
            }
            
            $html .= '<div class="partner-info">';
            $html .= '<h3 class="partner-name">' . htmlspecialchars($partner['name']) . '</h3>';
            
            if (!empty($partner['description'])) {
                $html .= '<p class="partner-description">' . htmlspecialchars($partner['description']) . '</p>';
            }
            
            $html .= '<div class="partner-type">';
            $html .= '<span class="type-badge type-' . htmlspecialchars($partner['type']) . '">';
            $html .= htmlspecialchars($this->getTypeDisplayName($partner['type']));
            $html .= '</span>';
            $html .= '</div>';
            
            if (!empty($partner['website'])) {
                $html .= '<a href="' . htmlspecialchars($partner['website']) . '" target="_blank" rel="noopener" class="partner-website">Visiter le site</a>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function getTypeDisplayName($type) {
        $types = [
            'bank' => 'Banque',
            'microfinance' => 'Microfinance',
            'lender' => 'Prêteur',
            'other' => 'Autre'
        ];
        
        return $types[$type] ?? $type;
    }
    
    public function getAvailableTypes() {
        return [
            'bank' => 'Banque',
            'microfinance' => 'Microfinance',
            'lender' => 'Prêteur privé',
            'other' => 'Autre'
        ];
    }
    
    private function validatePartnerData($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors[] = 'Le nom du partenaire est requis (minimum 2 caractères)';
        }
        
        if (empty($data['type']) || !array_key_exists($data['type'], $this->getAvailableTypes())) {
            $errors[] = 'Type de partenaire invalide';
        }
        
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL du site web invalide';
        }
        
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide';
        }
        
        if (!empty($data['contact_phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['contact_phone'])) {
            $errors[] = 'Numéro de téléphone invalide';
        }
        
        if (isset($data['display_order']) && (!is_numeric($data['display_order']) || intval($data['display_order']) < 0)) {
            $errors[] = 'Ordre d\'affichage invalide';
        }
        
        $existingPartner = $this->db->fetchOne("SELECT id FROM partners WHERE name = ?", [trim($data['name'])]);
        if ($existingPartner && (!$excludeId || $existingPartner['id'] != $excludeId)) {
            $errors[] = 'Un partenaire avec ce nom existe déjà';
        }
        
        return $errors;
    }
    
    private function optimizeLogo($logoPath) {
        if (!file_exists($logoPath)) {
            return false;
        }
        
        $imageInfo = getimagesize($logoPath);
        if (!$imageInfo) {
            return false;
        }
        
        list($width, $height, $type) = $imageInfo;
        
        $maxWidth = 300;
        $maxHeight = 150;
        
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($logoPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($logoPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($logoPath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        } else {
            $white = imagecolorallocate($newImage, 255, 255, 255);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
        }
        
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $logoPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $logoPath, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $logoPath);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return true;
    }
    
    public function exportPartners($format = 'json') {
        $partners = $this->getAllPartners();
        
        switch ($format) {
            case 'json':
                return json_encode($partners, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                $csv = "ID,Nom,Type,Site web,Email,Téléphone,Actif,Ordre\n";
                foreach ($partners as $partner) {
                    $csv .= sprintf(
                        "%d,%s,%s,%s,%s,%s,%s,%d\n",
                        $partner['id'],
                        '"' . str_replace('"', '""', $partner['name']) . '"',
                        $partner['type'],
                        $partner['website'],
                        $partner['contact_email'],
                        $partner['contact_phone'],
                        $partner['is_active'] ? 'Oui' : 'Non',
                        $partner['display_order']
                    );
                }
                return $csv;
                
            default:
                return $partners;
        }
    }
    
    public function importPartners($data, $format = 'json') {
        try {
            $partners = [];
            
            switch ($format) {
                case 'json':
                    $partners = json_decode($data, true);
                    break;
                    
                case 'csv':
                    $lines = explode("\n", $data);
                    $headers = str_getcsv(array_shift($lines));
                    
                    foreach ($lines as $line) {
                        if (empty(trim($line))) continue;
                        $values = str_getcsv($line);
                        $partners[] = array_combine($headers, $values);
                    }
                    break;
                    
                default:
                    return ['success' => false, 'message' => 'Format non supporté'];
            }
            
            if (!is_array($partners)) {
                return ['success' => false, 'message' => 'Format de données invalide'];
            }
            
            $imported = 0;
            $errors = [];
            
            foreach ($partners as $partnerData) {
                $result = $this->createPartner($partnerData);
                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = "Erreur pour {$partnerData['name']}: {$result['message']}";
                }
            }
            
            return [
                'success' => true,
                'message' => "$imported partenaire(s) importé(s)",
                'imported' => $imported,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            error_log("Erreur import partenaires: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'importation'];
        }
    }
}