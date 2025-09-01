<?php
class User {
    private $db;
    private $currentUser = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        if (isset($_SESSION['user_id'])) {
            $this->loadCurrentUser();
        }
    }
    
    public function register($data, $languageCode) {
        try {
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Cette adresse email existe déjà'];
            }
            
            $errors = $this->validateRegistrationData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $verificationToken = $this->generateToken();
            
            $userData = [
                'email' => strtolower(trim($data['email'])),
                'password' => $hashedPassword,
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'phone' => trim($data['phone'] ?? ''),
                'whatsapp' => trim($data['whatsapp'] ?? ''),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => trim($data['address'] ?? ''),
                'city' => trim($data['city'] ?? ''),
                'postal_code' => trim($data['postal_code'] ?? ''),
                'country' => trim($data['country'] ?? ''),
                'status' => 'active'
            ];
            
            $userId = $this->db->insert('users', $userData);
            
            if ($userId) {
                $this->db->logActivity($userId, null, 'user_registered', 'Nouvel utilisateur inscrit');
                
                $mailer = new Mailer();
                $mailer->sendWelcomeEmail($userData['email'], $userData['first_name'], $languageCode);
                
                return [
                    'success' => true, 
                    'message' => 'Inscription réussie ! Vous pouvez maintenant vous connecter.',
                    'user_id' => $userId
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
            
        } catch (Exception $e) {
            error_log("Erreur inscription: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de l\'inscription'];
        }
    }
    
    public function login($email, $password, $rememberMe = false) {
        try {
            $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [strtolower(trim($email))]);
            
            if (!$user || !password_verify($password, $user['password'])) {
                sleep(1);
                return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['login_time'] = time();
            
            if ($rememberMe) {
                $token = $this->generateToken();
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
                $this->db->update('users', ['remember_token' => $token], 'id = ?', [$user['id']]);
            }
            
            $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            $this->db->logActivity($user['id'], null, 'user_login', 'Connexion utilisateur');
            
            $this->currentUser = $user;
            
            return ['success' => true, 'message' => 'Connexion réussie'];
            
        } catch (Exception $e) {
            error_log("Erreur connexion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la connexion'];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->db->logActivity($_SESSION['user_id'], null, 'user_logout', 'Déconnexion utilisateur');
        }
        
        session_destroy();
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        $this->currentUser = null;
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && $this->currentUser !== null;
    }
    
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Récupère un utilisateur par son ID.
     * @param int $userId L'ID de l'utilisateur à récupérer.
     * @return array|null Les données de l'utilisateur ou null si non trouvé.
     */
    public function getUserById($userId) {
        if (!$userId) {
            return null;
        }
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    }
    
    public function updateProfile($userId, $data) {
        try {
            $errors = $this->validateProfileData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $allowedFields = [
                'first_name', 'last_name', 'phone', 'whatsapp', 
                'date_of_birth', 'address', 'city', 'postal_code', 'country'
            ];
            
            $updateData = [];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = trim($data[$field]);
                }
            }
            
            if (empty($updateData)) {
                return ['success' => false, 'message' => 'Aucune donnée à mettre à jour'];
            }
            
            $this->db->update('users', $updateData, 'id = ?', [$userId]);
            $this->db->logActivity($userId, null, 'profile_updated', 'Profil mis à jour');
            
            if ($userId == $this->currentUser['id']) {
                $this->loadCurrentUser();
            }
            
            return ['success' => true, 'message' => 'Profil mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour profil: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du profil'];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect'];
            }
            
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            $this->db->logActivity($userId, null, 'password_changed', 'Mot de passe modifié');
            
            return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur changement mot de passe: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du changement de mot de passe'];
        }
    }
    
    public function requestPasswordReset($email) {
        try {
            $user = $this->db->fetchOne("SELECT id FROM users WHERE email = ? AND status = 'active'", [strtolower(trim($email))]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Aucun compte associé à cette adresse email'];
            }
            
            $token = $this->generateToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $this->db->insert('password_resets', [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            
            $mailer = new Mailer();
            $mailer->sendPasswordResetEmail($email, $token);
            
            return ['success' => true, 'message' => 'Instructions de réinitialisation envoyées par email'];
            
        } catch (Exception $e) {
            error_log("Erreur demande reset password: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la demande de réinitialisation'];
        }
    }
    
    public function resetPassword($token, $newPassword) {
        try {
            $reset = $this->db->fetchOne("
                SELECT email FROM password_resets 
                WHERE token = ? AND expires_at > NOW() AND used = FALSE
            ", [$token]);
            
            if (!$reset) {
                return ['success' => false, 'message' => 'Token invalide ou expiré'];
            }
            
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caractères'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->beginTransaction();
            
            $this->db->update('users', ['password' => $hashedPassword], 'email = ?', [$reset['email']]);
            $this->db->update('password_resets', ['used' => true], 'token = ?', [$token]);
            
            $user = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$reset['email']]);
            $this->db->logActivity($user['id'], null, 'password_reset', 'Mot de passe réinitialisé');
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Mot de passe réinitialisé avec succès'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur reset password: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la réinitialisation'];
        }
    }
    
    public function getUserDocuments($userId) {
        return $this->db->fetchAll("
            SELECT * FROM documents 
            WHERE user_id = ? 
            ORDER BY document_type, uploaded_at DESC
        ", [$userId]);
    }
    
    public function getUserLoanRequests($userId) {
        return $this->db->fetchAll("
            SELECT * FROM loan_requests 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ", [$userId]);
    }
    
    public function getUserWithdrawals($userId) {
        return $this->db->fetchAll("
            SELECT w.*, lr.approved_amount, lr.partner_bank 
            FROM withdrawals w
            JOIN loan_requests lr ON w.loan_request_id = lr.id
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC
        ", [$userId]);
    }
    
    public function getUserNotifications($userId, $limit = 10) {
        return $this->db->getUserNotifications($userId, $limit);
    }
    
    public function getUnreadNotificationsCount($userId) {
        return $this->db->getUnreadNotificationsCount($userId);
    }
    
    public function markNotificationAsRead($notificationId, $userId) {
        return $this->db->markNotificationAsRead($notificationId, $userId);
    }
    
    public function getUserBalance($userId) {
        return $this->db->getUserBalance($userId);
    }
    
    public function getDashboardStats($userId) {
        $stats = [];
        
        $stats['balance'] = $this->getUserBalance($userId);
        $stats['total_loans'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ?", [$userId]);
        $stats['approved_loans'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ? AND status = 'approved'", [$userId]);
        $stats['pending_loans'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ? AND status IN ('pending', 'under_review')", [$userId]);
        $stats['total_withdrawals'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE user_id = ?", [$userId]);
        $stats['pending_withdrawals'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status = 'pending'", [$userId]);
        $stats['unread_notifications'] = $this->getUnreadNotificationsCount($userId);
        
        $totalApprovedAmount = $this->db->fetchOne("
            SELECT SUM(approved_amount) as total 
            FROM loan_requests 
            WHERE user_id = ? AND status = 'approved'
        ", [$userId]);
        $stats['total_approved_amount'] = $totalApprovedAmount['total'] ?? 0;
        
        return $stats;
    }
    
    private function loadCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->db->fetchOne("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['user_id']]);
            
            if (!$this->currentUser) {
                session_destroy();
                $this->currentUser = null;
            }
        }
    }
    
    private function emailExists($email) {
        return $this->db->count("SELECT COUNT(*) FROM users WHERE email = ?", [strtolower(trim($email))]) > 0;
    }
    
    private function validateRegistrationData($data) {
        $errors = [];
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        
        if (empty($data['first_name']) || strlen(trim($data['first_name'])) < 2) {
            $errors[] = 'Le prénom est obligatoire (minimum 2 caractères)';
        }
        
        if (empty($data['last_name']) || strlen(trim($data['last_name'])) < 2) {
            $errors[] = 'Le nom de famille est obligatoire (minimum 2 caractères)';
        }
        
        if (!empty($data['phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['phone'])) {
            $errors[] = 'Numéro de téléphone invalide';
        }
        
        if (!empty($data['whatsapp']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['whatsapp'])) {
            $errors[] = 'Numéro WhatsApp invalide';
        }
        
        return $errors;
    }
    
    private function validateProfileData($data) {
        $errors = [];
        
        if (isset($data['first_name']) && (empty($data['first_name']) || strlen(trim($data['first_name'])) < 2)) {
            $errors[] = 'Le prénom doit contenir au moins 2 caractères';
        }
        
        if (isset($data['last_name']) && (empty($data['last_name']) || strlen(trim($data['last_name'])) < 2)) {
            $errors[] = 'Le nom de famille doit contenir au moins 2 caractères';
        }
        
        if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['phone'])) {
            $errors[] = 'Numéro de téléphone invalide';
        }
        
        if (isset($data['whatsapp']) && !empty($data['whatsapp']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['whatsapp'])) {
            $errors[] = 'Numéro WhatsApp invalide';
        }
        
        if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
            $birthDate = new DateTime($data['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            
            if ($age < 18) {
                $errors[] = 'Vous devez être majeur(e) pour utiliser ce service';
            }
            
            if ($age > 100) {
                $errors[] = 'Date de naissance invalide';
            }
        }
        
        return $errors;
    }
    
    private function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
}