<?php
require_once 'Database.php';

class Testimonial {
    private $db;
    private $lang;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->lang = Language::getInstance();
    }
    
    public function createTestimonial($data) {
        try {
            $errors = $this->validateTestimonialData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $testimonialData = [
                'user_id' => $data['user_id'] ?? null,
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'] ?? '')),
                'rating' => intval($data['rating']),
                'title' => trim($data['title'] ?? ''),
                'content' => trim($data['content']),
                'is_approved' => isset($data['is_approved']) ? (bool)$data['is_approved'] : false,
                'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : false
            ];
            
            $testimonialId = $this->db->insert('testimonials', $testimonialData);
            
            if ($testimonialId) {
                $this->db->logActivity($data['user_id'] ?? null, null, 'testimonial_created', "Nouveau témoignage créé par: {$testimonialData['name']}");
                
                if (!$testimonialData['is_approved']) {
                    $this->notifyAdminNewTestimonial($testimonialData);
                }
                
                return [
                    'success' => true,
                    'message' => 'Témoignage soumis avec succès',
                    'testimonial_id' => $testimonialId,
                    'requires_approval' => !$testimonialData['is_approved']
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création du témoignage'];
            
        } catch (Exception $e) {
            error_log("Erreur création témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création'];
        }
    }
    
    public function updateTestimonial($testimonialId, $data) {
        try {
            $existingTestimonial = $this->getTestimonialById($testimonialId);
            if (!$existingTestimonial) {
                return ['success' => false, 'message' => 'Témoignage non trouvé'];
            }
            
            $errors = $this->validateTestimonialData($data, $testimonialId);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $updateData = [
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'] ?? '')),
                'rating' => intval($data['rating']),
                'title' => trim($data['title'] ?? ''),
                'content' => trim($data['content']),
                'is_approved' => isset($data['is_approved']) ? (bool)$data['is_approved'] : $existingTestimonial['is_approved'],
                'is_featured' => isset($data['is_featured']) ? (bool)$data['is_featured'] : $existingTestimonial['is_featured']
            ];
            
            $this->db->update('testimonials', $updateData, 'id = ?', [$testimonialId]);
            $this->db->logActivity($existingTestimonial['user_id'], null, 'testimonial_updated', "Témoignage mis à jour: {$updateData['name']}");
            
            return ['success' => true, 'message' => 'Témoignage mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour'];
        }
    }
    
    public function deleteTestimonial($testimonialId) {
        try {
            $testimonial = $this->getTestimonialById($testimonialId);
            if (!$testimonial) {
                return ['success' => false, 'message' => 'Témoignage non trouvé'];
            }
            
            $this->db->delete('testimonials', 'id = ?', [$testimonialId]);
            $this->db->logActivity($testimonial['user_id'], null, 'testimonial_deleted', "Témoignage supprimé: {$testimonial['name']}");
            
            return ['success' => true, 'message' => 'Témoignage supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la suppression'];
        }
    }
    
    public function approveTestimonial($testimonialId, $adminId) {
        try {
            $testimonial = $this->getTestimonialById($testimonialId);
            if (!$testimonial) {
                return ['success' => false, 'message' => 'Témoignage non trouvé'];
            }
            
            if ($testimonial['is_approved']) {
                return ['success' => false, 'message' => 'Ce témoignage est déjà approuvé'];
            }
            
            $this->db->update('testimonials', [
                'is_approved' => true,
                'approved_by' => $adminId,
                'approved_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$testimonialId]);
            
            $this->db->logActivity($testimonial['user_id'], $adminId, 'testimonial_approved', "Témoignage approuvé: {$testimonial['name']}");
            
            if ($testimonial['user_id']) {
                $this->db->insert('notifications', [
                    'user_id' => $testimonial['user_id'],
                    'type' => 'general',
                    'title' => 'Témoignage approuvé',
                    'message' => 'Votre témoignage a été approuvé et est maintenant visible sur notre site. Merci pour votre confiance !',
                    'related_id' => $testimonialId
                ]);
            }
            
            return ['success' => true, 'message' => 'Témoignage approuvé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur approbation témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'approbation'];
        }
    }
    
    public function rejectTestimonial($testimonialId, $adminId, $reason = '') {
        try {
            $testimonial = $this->getTestimonialById($testimonialId);
            if (!$testimonial) {
                return ['success' => false, 'message' => 'Témoignage non trouvé'];
            }
            
            $this->db->delete('testimonials', 'id = ?', [$testimonialId]);
            $this->db->logActivity($testimonial['user_id'], $adminId, 'testimonial_rejected', "Témoignage rejeté: {$testimonial['name']} - Raison: $reason");
            
            if ($testimonial['user_id']) {
                $message = 'Votre témoignage n\'a pas pu être approuvé.';
                if (!empty($reason)) {
                    $message .= " Raison: $reason";
                }
                
                $this->db->insert('notifications', [
                    'user_id' => $testimonial['user_id'],
                    'type' => 'general',
                    'title' => 'Témoignage non approuvé',
                    'message' => $message,
                    'related_id' => $testimonialId
                ]);
            }
            
            return ['success' => true, 'message' => 'Témoignage rejeté'];
            
        } catch (Exception $e) {
            error_log("Erreur rejet témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors du rejet'];
        }
    }
    
    public function toggleFeaturedStatus($testimonialId) {
        try {
            $testimonial = $this->getTestimonialById($testimonialId);
            if (!$testimonial) {
                return ['success' => false, 'message' => 'Témoignage non trouvé'];
            }
            
            if (!$testimonial['is_approved']) {
                return ['success' => false, 'message' => 'Le témoignage doit être approuvé pour être mis en avant'];
            }
            
            $newFeaturedStatus = !$testimonial['is_featured'];
            $this->db->update('testimonials', ['is_featured' => $newFeaturedStatus], 'id = ?', [$testimonialId]);
            
            $statusText = $newFeaturedStatus ? 'mis en avant' : 'retiré de la mise en avant';
            $this->db->logActivity($testimonial['user_id'], null, 'testimonial_featured_changed', "Témoignage {$statusText}: {$testimonial['name']}");
            
            return [
                'success' => true,
                'message' => "Témoignage {$statusText} avec succès",
                'new_status' => $newFeaturedStatus
            ];
            
        } catch (Exception $e) {
            error_log("Erreur changement statut featured témoignage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue'];
        }
    }
    
    public function getTestimonialById($testimonialId) {
        return $this->db->fetchOne("
            SELECT t.*, u.first_name, u.last_name 
            FROM testimonials t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.id = ?
        ", [$testimonialId]);
    }
    
    public function getAllTestimonials($approved = null, $featured = null, $limit = null, $offset = 0) {
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM testimonials t 
                LEFT JOIN users u ON t.user_id = u.id";
        
        $conditions = [];
        $params = [];
        
        if ($approved !== null) {
            $conditions[] = "t.is_approved = ?";
            $params[] = (bool)$approved;
        }
        
        if ($featured !== null) {
            $conditions[] = "t.is_featured = ?";
            $params[] = (bool)$featured;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getApprovedTestimonials($limit = null, $featuredFirst = true) {
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM testimonials t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.is_approved = 1";
        
        if ($featuredFirst) {
            $sql .= " ORDER BY t.is_featured DESC, t.created_at DESC";
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }
        
        $params = [];
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getFeaturedTestimonials($limit = 6) {
        return $this->db->fetchAll("
            SELECT t.*, u.first_name, u.last_name 
            FROM testimonials t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE t.is_approved = 1 AND t.is_featured = 1 
            ORDER BY t.created_at DESC 
            LIMIT ?
        ", [$limit]);
    }
    
    public function getPendingTestimonials($limit = null) {
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM testimonials t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.is_approved = 0 
                ORDER BY t.created_at ASC";
        
        $params = [];
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getTestimonialsByRating($rating, $limit = null) {
        $sql = "SELECT t.*, u.first_name, u.last_name 
                FROM testimonials t 
                LEFT JOIN users u ON t.user_id = u.id 
                WHERE t.is_approved = 1 AND t.rating = ? 
                ORDER BY t.created_at DESC";
        
        $params = [$rating];
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getUserTestimonials($userId) {
        return $this->db->fetchAll("
            SELECT * FROM testimonials 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ", [$userId]);
    }
    
    public function searchTestimonials($query, $limit = 20) {
        return $this->db->fetchAll("
            SELECT t.*, u.first_name, u.last_name 
            FROM testimonials t 
            LEFT JOIN users u ON t.user_id = u.id 
            WHERE (t.name LIKE ? OR t.title LIKE ? OR t.content LIKE ?) AND t.is_approved = 1
            ORDER BY t.created_at DESC 
            LIMIT ?
        ", ["%$query%", "%$query%", "%$query%", $limit]);
    }
    
    public function getTestimonialStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count("SELECT COUNT(*) FROM testimonials");
        $stats['approved'] = $this->db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1");
        $stats['pending'] = $this->db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 0");
        $stats['featured'] = $this->db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1 AND is_featured = 1");
        
        $avgRating = $this->db->fetchOne("SELECT AVG(rating) as avg FROM testimonials WHERE is_approved = 1");
        $stats['average_rating'] = round($avgRating['avg'] ?? 0, 1);
        
        $ratingDistribution = $this->db->fetchAll("
            SELECT rating, COUNT(*) as count 
            FROM testimonials 
            WHERE is_approved = 1 
            GROUP BY rating 
            ORDER BY rating DESC
        ");
        
        $stats['rating_distribution'] = [];
        for ($i = 5; $i >= 1; $i--) {
            $stats['rating_distribution'][$i] = 0;
        }
        
        foreach ($ratingDistribution as $rating) {
            $stats['rating_distribution'][$rating['rating']] = $rating['count'];
        }
        
        $recentTestimonials = $this->db->fetchAll("
            SELECT name, rating, LEFT(content, 100) as excerpt, created_at 
            FROM testimonials 
            WHERE is_approved = 1 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stats['recent_testimonials'] = $recentTestimonials;
        
        return $stats;
    }
    
    public function generateTestimonialsHTML($testimonials, $layout = 'grid') {
        if (empty($testimonials)) {
            return '<div class="testimonials-empty">Aucun témoignage à afficher</div>';
        }
        
        $html = '<div class="testimonials-' . htmlspecialchars($layout) . '">';
        
        foreach ($testimonials as $testimonial) {
            $html .= '<div class="testimonial-card">';
            $html .= '<div class="testimonial-content">';
            
            $html .= '<div class="testimonial-rating">';
            for ($i = 1; $i <= 5; $i++) {
                $filled = $i <= $testimonial['rating'] ? 'filled' : '';
                $html .= '<span class="star ' . $filled . '">★</span>';
            }
            $html .= '</div>';
            
            if (!empty($testimonial['title'])) {
                $html .= '<h3 class="testimonial-title">' . htmlspecialchars($testimonial['title']) . '</h3>';
            }
            
            $html .= '<p class="testimonial-text">' . htmlspecialchars($testimonial['content']) . '</p>';
            $html .= '</div>';
            
            $html .= '<div class="testimonial-author">';
            $html .= '<div class="author-avatar">';
            $html .= strtoupper(substr($testimonial['name'], 0, 1));
            $html .= '</div>';
            $html .= '<div class="author-info">';
            $html .= '<div class="author-name">' . htmlspecialchars($testimonial['name']) . '</div>';
            $html .= '<div class="author-label">Client vérifié</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function generateRatingStarsHTML($rating, $maxRating = 5) {
        $html = '<div class="rating-stars">';
        
        for ($i = 1; $i <= $maxRating; $i++) {
            $filled = $i <= $rating ? 'filled' : '';
            $html .= '<span class="star ' . $filled . '">★</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function canUserSubmitTestimonial($userId) {
        if (!$userId) {
            return ['can_submit' => false, 'reason' => 'Utilisateur non connecté'];
        }
        
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ? AND status = 'active'", [$userId]);
        if (!$user) {
            return ['can_submit' => false, 'reason' => 'Utilisateur non trouvé ou inactif'];
        }
        
        $hasApprovedLoan = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ? AND status = 'approved'", [$userId]) > 0;
        if (!$hasApprovedLoan) {
            return ['can_submit' => false, 'reason' => 'Aucun prêt approuvé trouvé'];
        }
        
        $existingTestimonial = $this->db->fetchOne("SELECT id FROM testimonials WHERE user_id = ?", [$userId]);
        if ($existingTestimonial) {
            return ['can_submit' => false, 'reason' => 'Vous avez déjà soumis un témoignage'];
        }
        
        return ['can_submit' => true];
    }
    
    private function validateTestimonialData($data, $excludeId = null) {
        $errors = [];
        
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors[] = 'Le nom est requis (minimum 2 caractères)';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide';
        }
        
        if (empty($data['rating']) || !is_numeric($data['rating']) || intval($data['rating']) < 1 || intval($data['rating']) > 5) {
            $errors[] = 'Note invalide (doit être entre 1 et 5)';
        }
        
        if (empty($data['content']) || strlen(trim($data['content'])) < 10) {
            $errors[] = 'Le contenu du témoignage est requis (minimum 10 caractères)';
        }
        
        if (strlen(trim($data['content'])) > 1000) {
            $errors[] = 'Le contenu du témoignage ne doit pas dépasser 1000 caractères';
        }
        
        if (!empty($data['title']) && strlen(trim($data['title'])) > 100) {
            $errors[] = 'Le titre ne doit pas dépasser 100 caractères';
        }
        
        if ($this->containsInappropriateContent($data['content'] . ' ' . ($data['title'] ?? ''))) {
            $errors[] = 'Le contenu contient des termes inappropriés';
        }
        
        return $errors;
    }
    
    private function containsInappropriateContent($text) {
        $inappropriateWords = [
            'arnaque', 'escroquerie', 'voleur', 'merde', 'putain', 'connard', 'salaud', 'enculé'
        ];
        
        $text = strtolower($text);
        
        foreach ($inappropriateWords as $word) {
            if (strpos($text, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function notifyAdminNewTestimonial($testimonialData) {
        try {
            $mailer = new Mailer();
            
            $subject = 'Nouveau témoignage en attente de modération - PrestaCapi';
            $message = "
            <h2>Nouveau témoignage reçu</h2>
            <p><strong>Nom :</strong> {$testimonialData['name']}</p>
            <p><strong>Email :</strong> {$testimonialData['email']}</p>
            <p><strong>Note :</strong> {$testimonialData['rating']}/5</p>
            <p><strong>Titre :</strong> {$testimonialData['title']}</p>
            <p><strong>Contenu :</strong></p>
            <blockquote>{$testimonialData['content']}</blockquote>
            <p><a href='https://prestacapi.com/admin/testimonials/'>Modérer ce témoignage</a></p>
            ";
            
            $mailer->send('admin@prestacapi.com', $subject, $message);
            
        } catch (Exception $e) {
            error_log("Erreur notification admin témoignage: " . $e->getMessage());
        }
    }
    
    public function getAverageRatingDisplay() {
        $stats = $this->getTestimonialStats();
        $avgRating = $stats['average_rating'];
        
        $html = '<div class="average-rating-display">';
        $html .= '<div class="rating-number">' . $avgRating . '</div>';
        $html .= '<div class="rating-stars">';
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= floor($avgRating)) {
                $html .= '<span class="star filled">★</span>';
            } elseif ($i - 0.5 <= $avgRating) {
                $html .= '<span class="star half">★</span>';
            } else {
                $html .= '<span class="star">★</span>';
            }
        }
        
        $html .= '</div>';
        $html .= '<div class="rating-count">(' . $stats['approved'] . ' avis)</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    public function exportTestimonials($format = 'json', $approved = true) {
        $testimonials = $this->getAllTestimonials($approved);
        
        switch ($format) {
            case 'json':
                return json_encode($testimonials, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
            case 'csv':
                $csv = "ID,Nom,Email,Note,Titre,Contenu,Approuvé,En vedette,Date\n";
                foreach ($testimonials as $testimonial) {
                    $csv .= sprintf(
                        "%d,%s,%s,%d,%s,%s,%s,%s,%s\n",
                        $testimonial['id'],
                        '"' . str_replace('"', '""', $testimonial['name']) . '"',
                        $testimonial['email'],
                        $testimonial['rating'],
                        '"' . str_replace('"', '""', $testimonial['title']) . '"',
                        '"' . str_replace('"', '""', $testimonial['content']) . '"',
                        $testimonial['is_approved'] ? 'Oui' : 'Non',
                        $testimonial['is_featured'] ? 'Oui' : 'Non',
                        $testimonial['created_at']
                    );
                }
                return $csv;
                
            default:
                return $testimonials;
        }
    }
}