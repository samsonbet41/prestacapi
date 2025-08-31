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
            
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount(); 
            
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