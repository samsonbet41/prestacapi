<?php

class Database {
    
    private static $instance = null;
    private $conn;

    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $dbname = 'prestacapi';

    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch(PDOException $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            if ($stmt === false) {
                error_log("Erreur de préparation SQL: " . print_r($this->conn->errorInfo(), true));
                throw new Exception("Erreur de préparation de la requête");
            }
    
            $success = $stmt->execute($params);
            if ($success === false) {
                error_log("Erreur d'exécution SQL: " . print_r($stmt->errorInfo(), true));
                throw new Exception("Erreur d'exécution de la requête");
            }
    
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur PDO: " . $e->getMessage());
            error_log("Requête: " . $sql);
            error_log("Paramètres: " . print_r($params, true));
            throw new Exception("Erreur d'exécution de la requête: " . $e->getMessage());
        }
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur fetchAll: " . $e->getMessage());
            return [];
        }
    }

    public function count($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return (int) $stmt->fetchColumn();
    }

    public function insert($table, $data) {
        try {
            $fields = array_keys($data);
            $values = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO " . $table . " (" . implode(", ", $fields) . ") 
                    VALUES (" . implode(", ", $values) . ")";
            
            $this->query($sql, array_values($data));
            return $this->conn->lastInsertId();
        } catch(Exception $e) {
            error_log("Erreur insert: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        try {
            $fields = array_map(function($field) {
                return "$field = ?";
            }, array_keys($data));
            
            $sql = "UPDATE " . $table . " SET " . implode(", ", $fields) . " WHERE " . $where;
            
            $params = array_merge(array_values($data), $whereParams);
            $this->query($sql, $params);
        } catch(Exception $e) {
            error_log("Erreur update: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM " . $table . " WHERE " . $where;
            $this->query($sql, $params);
        } catch(Exception $e) {
            error_log("Erreur delete: " . $e->getMessage());
            throw $e;
        }
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }

    public function createTablesIfNotExists() {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            whatsapp VARCHAR(20),
            date_of_birth DATE,
            address TEXT,
            city VARCHAR(100),
            postal_code VARCHAR(20),
            country VARCHAR(100),
            balance DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            email_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS loan_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            duration INT NOT NULL,
            purpose TEXT,
            monthly_income DECIMAL(10,2),
            monthly_expenses DECIMAL(10,2),
            employment_status VARCHAR(100),
            employment_duration INT,
            employer_name VARCHAR(255),
            employer_phone VARCHAR(20),
            other_loans DECIMAL(10,2) DEFAULT 0.00,
            collateral TEXT,
            co_signer_name VARCHAR(255),
            co_signer_phone VARCHAR(20),
            status ENUM('pending', 'under_review', 'approved', 'rejected', 'disbursed') DEFAULT 'pending',
            approved_amount DECIMAL(10,2) NULL,
            approved_by INT NULL,
            approved_at TIMESTAMP NULL,
            rejection_reason TEXT NULL,
            partner_bank VARCHAR(255) NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES admin_users(id)
        );

        CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            loan_request_id INT NULL,
            document_type ENUM('identity', 'birth_certificate', 'income_proof', 'bank_statement', 'employment_certificate', 'other') NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT,
            mime_type VARCHAR(100),
            is_verified BOOLEAN DEFAULT FALSE,
            verified_by INT NULL,
            verified_at TIMESTAMP NULL,
            notes TEXT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE SET NULL,
            FOREIGN KEY (verified_by) REFERENCES admin_users(id)
        );

        CREATE TABLE IF NOT EXISTS withdrawals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            loan_request_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            bank_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(100) NOT NULL,
            account_holder_name VARCHAR(255) NOT NULL,
            swift_code VARCHAR(50),
            iban VARCHAR(100),
            notes TEXT,
            status ENUM('pending', 'approved', 'rejected', 'processed') DEFAULT 'pending',
            processed_by INT NULL,
            processed_at TIMESTAMP NULL,
            rejection_reason TEXT NULL,
            transaction_reference VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (loan_request_id) REFERENCES loan_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (processed_by) REFERENCES admin_users(id)
        );

        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS partners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type ENUM('bank', 'microfinance', 'lender', 'other') NOT NULL,
            logo VARCHAR(500),
            description TEXT,
            website VARCHAR(255),
            contact_email VARCHAR(255),
            contact_phone VARCHAR(20),
            is_active BOOLEAN DEFAULT TRUE,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            slug VARCHAR(500) UNIQUE NOT NULL,
            content LONGTEXT NOT NULL,
            excerpt TEXT,
            featured_image VARCHAR(500),
            author VARCHAR(255) DEFAULT 'PrestaCapi',
            published BOOLEAN DEFAULT FALSE,
            language ENUM('fr', 'en', 'es', 'de') DEFAULT 'fr',
            meta_title VARCHAR(255),
            meta_description VARCHAR(500),
            meta_keywords VARCHAR(500),
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255),
            rating INT CHECK (rating >= 1 AND rating <= 5),
            title VARCHAR(255),
            content TEXT NOT NULL,
            is_approved BOOLEAN DEFAULT FALSE,
            is_featured BOOLEAN DEFAULT FALSE,
            approved_by INT NULL,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (approved_by) REFERENCES admin_users(id)
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('loan_approved', 'loan_rejected', 'withdrawal_approved', 'withdrawal_rejected', 'document_verified', 'general') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            related_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            description VARCHAR(500),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
        ('site_name', 'PrestaCapi', 'Nom du site'),
        ('site_email', 'contact@prestacapi.com', 'Email principal du site'),
        ('max_loan_amount', '50000', 'Montant maximum des prêts'),
        ('min_loan_amount', '500', 'Montant minimum des prêts'),
        ('max_loan_duration', '60', 'Durée maximum des prêts (mois)'),
        ('min_loan_duration', '6', 'Durée minimum des prêts (mois)'),
        ('default_language', 'fr', 'Langue par défaut'),
        ('maintenance_mode', '0', 'Mode maintenance (0/1)'),
        ('registration_enabled', '1', 'Inscription activée (0/1)');

        CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            admin_id INT NULL,
            action VARCHAR(255) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
        );
        ";

        try {
            $this->conn->exec($sql);
            return true;
        } catch(PDOException $e) {
            error_log("Erreur création tables: " . $e->getMessage());
            return false;
        }
    }

    public function getUserStats() {
        $stats = [];
        
        $stats['total_users'] = $this->count("SELECT COUNT(*) FROM users");
        $stats['active_users'] = $this->count("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $stats['pending_loans'] = $this->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'pending'");
        $stats['approved_loans'] = $this->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'approved'");
        $stats['total_loan_amount'] = $this->fetchOne("SELECT SUM(approved_amount) as total FROM loan_requests WHERE status = 'approved'")['total'] ?? 0;
        $stats['pending_withdrawals'] = $this->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
        
        return $stats;
    }

    public function getUserBalance($userId) {
        return $this->fetchOne("SELECT balance FROM users WHERE id = ?", [$userId])['balance'] ?? 0;
    }

    public function updateUserBalance($userId, $amount) {
        return $this->update('users', ['balance' => $amount], 'id = ?', [$userId]);
    }

    public function getUserNotifications($userId, $limit = 10) {
        return $this->fetchAll("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?", 
            [$userId, $limit]
        );
    }

    public function getUnreadNotificationsCount($userId) {
        return $this->count("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE", [$userId]);
    }

    public function markNotificationAsRead($notificationId, $userId) {
        return $this->update('notifications', ['is_read' => true], 'id = ? AND user_id = ?', [$notificationId, $userId]);
    }

    public function logActivity($userId, $adminId, $action, $description = null, $ip = null, $userAgent = null) {
        return $this->insert('activity_logs', [
            'user_id' => $userId,
            'admin_id' => $adminId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip ?: $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}