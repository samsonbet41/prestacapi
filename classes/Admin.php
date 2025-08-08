<?php
require_once 'Database.php';

class Admin {
    private $db;
    private $currentAdmin = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['admin_id'])) {
            $this->loadCurrentAdmin();
        }
    }
    
    public function login($username, $password) {
        try {
            $admin = $this->db->fetchOne("SELECT * FROM admin_users WHERE (username = ? OR email = ?)", [$username, $username]);
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                sleep(1);
                return ['success' => false, 'message' => 'Identifiants incorrects'];
            }
            
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_login_time'] = time();
            
            $this->db->update('admin_users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$admin['id']]);
            $this->db->logActivity(null, $admin['id'], 'admin_login', 'Connexion administrateur');
            
            $this->currentAdmin = $admin;
            
            return ['success' => true, 'message' => 'Connexion réussie'];
            
        } catch (Exception $e) {
            error_log("Erreur connexion admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la connexion'];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->db->logActivity(null, $_SESSION['admin_id'], 'admin_logout', 'Déconnexion administrateur');
        }
        
        session_destroy();
        $this->currentAdmin = null;
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']) && $this->currentAdmin !== null;
    }
    
    public function getCurrentAdmin() {
        return $this->currentAdmin;
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $this->currentAdmin['role'];
        $permissions = $this->getRolePermissions($role);
        
        return in_array($permission, $permissions) || in_array('all', $permissions);
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /admin/login.php');
            exit;
        }
    }
    
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.1 403 Forbidden');
            die('Accès refusé');
        }
    }
    
    public function createAdmin($data) {
        try {
            if (!$this->hasPermission('manage_admins')) {
                return ['success' => false, 'message' => 'Permission insuffisante'];
            }
            
            $errors = $this->validateAdminData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            if ($this->usernameExists($data['username']) || $this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'];
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $adminData = [
                'username' => strtolower(trim($data['username'])),
                'email' => strtolower(trim($data['email'])),
                'password' => $hashedPassword,
                'full_name' => trim($data['full_name']),
                'role' => $data['role']
            ];
            
            $adminId = $this->db->insert('admin_users', $adminData);
            
            if ($adminId) {
                $this->db->logActivity(null, $this->currentAdmin['id'], 'admin_created', "Nouvel administrateur créé: {$adminData['username']}");
                return [
                    'success' => true,
                    'message' => 'Administrateur créé avec succès',
                    'admin_id' => $adminId
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
            
        } catch (Exception $e) {
            error_log("Erreur création admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création'];
        }
    }
    
    public function updateAdmin($adminId, $data) {
        try {
            if (!$this->hasPermission('manage_admins') && $adminId != $this->currentAdmin['id']) {
                return ['success' => false, 'message' => 'Permission insuffisante'];
            }
            
            $existingAdmin = $this->getAdminById($adminId);
            if (!$existingAdmin) {
                return ['success' => false, 'message' => 'Administrateur non trouvé'];
            }
            
            $updateData = [
                'full_name' => trim($data['full_name'])
            ];
            
            if (!empty($data['email']) && $data['email'] !== $existingAdmin['email']) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return ['success' => false, 'message' => 'Email invalide'];
                }
                
                if ($this->emailExists($data['email'], $adminId)) {
                    return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
                }
                
                $updateData['email'] = strtolower(trim($data['email']));
            }
            
            if ($this->hasPermission('manage_admins') && isset($data['role']) && $data['role'] !== $existingAdmin['role']) {
                if (!in_array($data['role'], $this->getAvailableRoles())) {
                    return ['success' => false, 'message' => 'Rôle invalide'];
                }
                $updateData['role'] = $data['role'];
            }
            
            $this->db->update('admin_users', $updateData, 'id = ?', [$adminId]);
            $this->db->logActivity(null, $this->currentAdmin['id'], 'admin_updated', "Administrateur mis à jour: {$existingAdmin['username']}");
            
            if ($adminId == $this->currentAdmin['id']) {
                $this->loadCurrentAdmin();
            }
            
            return ['success' => true, 'message' => 'Administrateur mis à jour avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour'];
        }
    }
    
    public function changePassword($adminId, $currentPassword, $newPassword) {
        try {
            if ($adminId != $this->currentAdmin['id'] && !$this->hasPermission('manage_admins')) {
                return ['success' => false, 'message' => 'Permission insuffisante'];
            }
            
            $admin = $this->getAdminById($adminId);
            if (!$admin) {
                return ['success' => false, 'message' => 'Administrateur non trouvé'];
            }
            
            if ($adminId == $this->currentAdmin['id'] && !password_verify($currentPassword, $admin['password'])) {
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect'];
            }
            
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->update('admin_users', ['password' => $hashedPassword], 'id = ?', [$adminId]);
            $this->db->logActivity(null, $this->currentAdmin['id'], 'admin_password_changed', "Mot de passe modifié pour: {$admin['username']}");
            
            return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur changement mot de passe admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors du changement de mot de passe'];
        }
    }
    
    public function deleteAdmin($adminId) {
        try {
            if (!$this->hasPermission('manage_admins')) {
                return ['success' => false, 'message' => 'Permission insuffisante'];
            }
            
            if ($adminId == $this->currentAdmin['id']) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte'];
            }
            
            $admin = $this->getAdminById($adminId);
            if (!$admin) {
                return ['success' => false, 'message' => 'Administrateur non trouvé'];
            }
            
            if ($admin['role'] === 'super_admin') {
                $superAdminCount = $this->db->count("SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin'");
                if ($superAdminCount <= 1) {
                    return ['success' => false, 'message' => 'Impossible de supprimer le dernier super administrateur'];
                }
            }
            
            $this->db->delete('admin_users', 'id = ?', [$adminId]);
            $this->db->logActivity(null, $this->currentAdmin['id'], 'admin_deleted', "Administrateur supprimé: {$admin['username']}");
            
            return ['success' => true, 'message' => 'Administrateur supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la suppression'];
        }
    }
    
    public function getAdminById($adminId) {
        return $this->db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$adminId]);
    }
    
    public function getAllAdmins() {
        return $this->db->fetchAll("SELECT id, username, email, full_name, role, last_login, created_at FROM admin_users ORDER BY created_at DESC");
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        $stats['users'] = [
            'total' => $this->db->count("SELECT COUNT(*) FROM users"),
            'active' => $this->db->count("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'new_today' => $this->db->count("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")
        ];
        
        $stats['loans'] = [
            'total' => $this->db->count("SELECT COUNT(*) FROM loan_requests"),
            'pending' => $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'pending'"),
            'approved' => $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'approved'"),
            'total_amount' => $this->db->fetchOne("SELECT SUM(approved_amount) as total FROM loan_requests WHERE status = 'approved'")['total'] ?? 0
        ];
        
        $stats['withdrawals'] = [
            'total' => $this->db->count("SELECT COUNT(*) FROM withdrawals"),
            'pending' => $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'"),
            'processed' => $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'processed'"),
            'total_amount' => $this->db->fetchOne("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'processed'")['total'] ?? 0
        ];
        
        $stats['documents'] = [
            'total' => $this->db->count("SELECT COUNT(*) FROM documents"),
            'pending' => $this->db->count("SELECT COUNT(*) FROM documents WHERE is_verified = 0"),
            'verified' => $this->db->count("SELECT COUNT(*) FROM documents WHERE is_verified = 1")
        ];
        
        $stats['testimonials'] = [
            'total' => $this->db->count("SELECT COUNT(*) FROM testimonials"),
            'pending' => $this->db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 0"),
            'approved' => $this->db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1")
        ];
        
        $stats['recent_activity'] = $this->db->fetchAll("
            SELECT action, description, created_at 
            FROM activity_logs 
            WHERE admin_id IS NOT NULL 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        return $stats;
    }
    
    public function getMonthlyStats($year = null) {
        $year = $year ?: date('Y');
        
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = [
                'month' => $month,
                'users' => $this->db->count("SELECT COUNT(*) FROM users WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?", [$year, $month]),
                'loans' => $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?", [$year, $month]),
                'approved_loans' => $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE YEAR(approved_at) = ? AND MONTH(approved_at) = ? AND status = 'approved'", [$year, $month]),
                'withdrawals' => $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?", [$year, $month])
            ];
            
            $loanAmount = $this->db->fetchOne("SELECT SUM(approved_amount) as total FROM loan_requests WHERE YEAR(approved_at) = ? AND MONTH(approved_at) = ? AND status = 'approved'", [$year, $month]);
            $monthlyData[$month]['loan_amount'] = $loanAmount['total'] ?? 0;
            
            $withdrawalAmount = $this->db->fetchOne("SELECT SUM(amount) as total FROM withdrawals WHERE YEAR(processed_at) = ? AND MONTH(processed_at) = ? AND status = 'processed'", [$year, $month]);
            $monthlyData[$month]['withdrawal_amount'] = $withdrawalAmount['total'] ?? 0;
        }
        
        return $monthlyData;
    }
    
    public function getSystemInfo() {
        $info = [];
        
        $info['php_version'] = PHP_VERSION;
        $info['mysql_version'] = $this->db->fetchOne("SELECT VERSION() as version")['version'];
        $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $info['disk_usage'] = $this->getDiskUsage();
        $info['memory_usage'] = $this->getMemoryUsage();
        $info['database_size'] = $this->getDatabaseSize();
        
        return $info;
    }
    
    public function getActivityLogs($limit = 50, $offset = 0, $adminOnly = false) {
        $sql = "SELECT al.*, u.first_name, u.last_name, au.username as admin_username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                LEFT JOIN admin_users au ON al.admin_id = au.id";
        
        $params = [];
        
        if ($adminOnly) {
            $sql .= " WHERE al.admin_id IS NOT NULL";
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function clearOldLogs($days = 90) {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
            $deletedCount = $this->db->query("DELETE FROM activity_logs WHERE created_at < ?", [$cutoffDate])->rowCount();
            
            $this->db->logActivity(null, $this->currentAdmin['id'], 'logs_cleaned', "Suppression de $deletedCount anciens logs");
            
            return ['success' => true, 'message' => "$deletedCount logs supprimés"];
            
        } catch (Exception $e) {
            error_log("Erreur nettoyage logs: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors du nettoyage'];
        }
    }
    
    public function exportData($type, $format = 'json') {
        try {
            if (!$this->hasPermission('export_data')) {
                return ['success' => false, 'message' => 'Permission insuffisante'];
            }
            
            $data = [];
            
            switch ($type) {
                case 'users':
                    $data = $this->db->fetchAll("SELECT id, email, first_name, last_name, phone, created_at, status FROM users");
                    break;
                    
                case 'loans':
                    $data = $this->db->fetchAll("SELECT lr.*, u.email FROM loan_requests lr JOIN users u ON lr.user_id = u.id");
                    break;
                    
                case 'withdrawals':
                    $data = $this->db->fetchAll("SELECT w.*, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id");
                    break;
                    
                case 'testimonials':
                    $data = $this->db->fetchAll("SELECT * FROM testimonials WHERE is_approved = 1");
                    break;
                    
                default:
                    return ['success' => false, 'message' => 'Type de données invalide'];
            }
            
            $this->db->logActivity(null, $this->currentAdmin['id'], 'data_exported', "Export de données: $type ($format)");
            
            return [
                'success' => true,
                'data' => $data,
                'format' => $format,
                'filename' => "prestacapi_{$type}_" . date('Y-m-d_H-i-s') . ".$format"
            ];
            
        } catch (Exception $e) {
            error_log("Erreur export données: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'export'];
        }
    }
    
    private function loadCurrentAdmin() {
        if (isset($_SESSION['admin_id'])) {
            $this->currentAdmin = $this->db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$_SESSION['admin_id']]);
            
            if (!$this->currentAdmin) {
                session_destroy();
                $this->currentAdmin = null;
            }
        }
    }
    
    private function validateAdminData($data) {
        $errors = [];
        
        if (empty($data['username']) || strlen(trim($data['username'])) < 3) {
            $errors[] = 'Nom d\'utilisateur requis (minimum 3 caractères)';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'Nom d\'utilisateur invalide (lettres, chiffres et _ uniquement)';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Mot de passe requis (minimum 8 caractères)';
        }
        
        if (empty($data['full_name']) || strlen(trim($data['full_name'])) < 2) {
            $errors[] = 'Nom complet requis (minimum 2 caractères)';
        }
        
        if (empty($data['role']) || !in_array($data['role'], $this->getAvailableRoles())) {
            $errors[] = 'Rôle invalide';
        }
        
        return $errors;
    }
    
    private function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM admin_users WHERE username = ?";
        $params = [strtolower(trim($username))];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->count($sql, $params) > 0;
    }
    
    private function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM admin_users WHERE email = ?";
        $params = [strtolower(trim($email))];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->db->count($sql, $params) > 0;
    }
    
    private function getRolePermissions($role) {
        $permissions = [
            'super_admin' => ['all'],
            'admin' => [
                'view_dashboard', 'manage_users', 'manage_loans', 'manage_withdrawals',
                'manage_documents', 'manage_testimonials', 'manage_partners',
                'manage_blog', 'view_reports', 'export_data'
            ],
            'moderator' => [
                'view_dashboard', 'manage_documents', 'manage_testimonials',
                'view_users', 'view_loans', 'view_withdrawals'
            ]
        ];
        
        return $permissions[$role] ?? [];
    }
    
    private function getAvailableRoles() {
        return ['super_admin', 'admin', 'moderator'];
    }
    
    private function getDiskUsage() {
        $bytes = disk_free_space($_SERVER['DOCUMENT_ROOT']);
        $total = disk_total_space($_SERVER['DOCUMENT_ROOT']);
        
        return [
            'free' => $this->formatBytes($bytes),
            'total' => $this->formatBytes($total),
            'used_percent' => round((($total - $bytes) / $total) * 100, 1)
        ];
    }
    
    private function getMemoryUsage() {
        return [
            'current' => $this->formatBytes(memory_get_usage(true)),
            'peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    private function getDatabaseSize() {
        $result = $this->db->fetchOne("
            SELECT SUM(data_length + index_length) as size 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        return $this->formatBytes($result['size'] ?? 0);
    }
    
    private function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    public function createDefaultAdmin() {
        try {
            $existingAdmin = $this->db->fetchOne("SELECT id FROM admin_users WHERE role = 'super_admin' LIMIT 1");
            if ($existingAdmin) {
                return ['success' => false, 'message' => 'Un super administrateur existe déjà'];
            }
            
            $defaultPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            $adminData = [
                'username' => 'admin',
                'email' => 'admin@prestacapi.com',
                'password' => $hashedPassword,
                'full_name' => 'Administrateur Principal',
                'role' => 'super_admin'
            ];
            
            $adminId = $this->db->insert('admin_users', $adminData);
            
            if ($adminId) {
                return [
                    'success' => true,
                    'message' => 'Administrateur par défaut créé',
                    'username' => 'admin',
                    'password' => $defaultPassword
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
            
        } catch (Exception $e) {
            error_log("Erreur création admin par défaut: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création'];
        }
    }
}