<?php

class Withdrawal {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createWithdrawalRequest($userId, $data) {
        try {
            $errors = $this->validateWithdrawalData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            $requestedAmount = floatval($data['amount']);
            $userBalance = floatval($user['balance']);
            
            if ($requestedAmount > $userBalance) {
                return ['success' => false, 'message' => 'Montant supérieur au solde disponible'];
            }
            
            if ($requestedAmount <= 0) {
                return ['success' => false, 'message' => 'Montant invalide'];
            }
            
            $activeLoanRequest = $this->db->fetchOne("
                SELECT id FROM loan_requests 
                WHERE user_id = ? AND status = 'approved' AND approved_amount > 0
                ORDER BY approved_at DESC 
                LIMIT 1
            ", [$userId]);
            
            if (!$activeLoanRequest) {
                return ['success' => false, 'message' => 'Aucun prêt approuvé trouvé'];
            }
            
            $pendingWithdrawal = $this->db->fetchOne("
                SELECT id FROM withdrawals 
                WHERE user_id = ? AND status = 'pending' 
                LIMIT 1
            ", [$userId]);
            
            if ($pendingWithdrawal) {
                return ['success' => false, 'message' => 'Vous avez déjà une demande de retrait en cours'];
            }
            
            $withdrawalData = [
                'user_id' => $userId,
                'loan_request_id' => $activeLoanRequest['id'],
                'amount' => $requestedAmount,
                'bank_name' => trim($data['bank_name']),
                'account_number' => trim($data['account_number']),
                'account_holder_name' => trim($data['account_holder_name']),
                'swift_code' => trim($data['swift_code'] ?? ''),
                'iban' => trim($data['iban'] ?? ''),
                'notes' => trim($data['notes'] ?? ''),
                'status' => 'pending'
            ];
            
            $withdrawalId = $this->db->insert('withdrawals', $withdrawalData);
            
            if ($withdrawalId) {
                $this->db->logActivity($userId, null, 'withdrawal_requested', 'Demande de retrait créée: ' . $requestedAmount . '€');
                
                $this->createNotification(
                    $userId, 
                    'general', 
                    'Demande de retrait envoyée', 
                    'Votre demande de retrait de ' . number_format($requestedAmount, 2, ',', ' ') . '€ a été envoyée et sera traitée sous 24-48h.',
                    $withdrawalId
                );
                
                $mailer = new Mailer();
                $withdrawalData['id'] = $withdrawalId;
                $mailer->sendWithdrawalConfirmation($user, $withdrawalData);
                $mailer->sendAdminNotification('new_withdrawal', array_merge($withdrawalData, [
                    'user_name' => $user['first_name'] . ' ' . $user['last_name']
                ]));
                
                return [
                    'success' => true, 
                    'message' => 'Demande de retrait envoyée avec succès',
                    'withdrawal_id' => $withdrawalId
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création de la demande'];
            
        } catch (Exception $e) {
            error_log("Erreur création demande retrait: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création de la demande'];
        }
    }
    
    public function getWithdrawalById($id, $userId = null) {
        $sql = "SELECT w.*, lr.approved_amount, lr.partner_bank, 
                       u.first_name, u.last_name, u.email, u.phone 
                FROM withdrawals w 
                JOIN loan_requests lr ON w.loan_request_id = lr.id
                JOIN users u ON w.user_id = u.id 
                WHERE w.id = ?";
        $params = [$id];
        
        if ($userId) {
            $sql .= " AND w.user_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    public function getUserWithdrawals($userId, $limit = null) {
        $sql = "SELECT w.*, lr.approved_amount, lr.partner_bank 
                FROM withdrawals w 
                JOIN loan_requests lr ON w.loan_request_id = lr.id
                WHERE w.user_id = ? 
                ORDER BY w.created_at DESC";
        $params = [$userId];
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getAllWithdrawals($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT w.*, lr.approved_amount, lr.partner_bank,
                       u.first_name, u.last_name, u.email, u.phone,
                       CASE WHEN w.status = 'pending' THEN 1
                            WHEN w.status = 'approved' THEN 2
                            WHEN w.status = 'processed' THEN 3
                            WHEN w.status = 'rejected' THEN 4
                            ELSE 5 END as status_priority
                FROM withdrawals w 
                JOIN loan_requests lr ON w.loan_request_id = lr.id
                JOIN users u ON w.user_id = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE w.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY status_priority, w.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateWithdrawalStatus($withdrawalId, $status, $adminId, $notes = null, $transactionReference = null) {
        try {
            $this->db->beginTransaction();
            
            $withdrawal = $this->getWithdrawalById($withdrawalId);
            if (!$withdrawal) {
                throw new Exception('Demande de retrait non trouvée');
            }
            
            $updateData = [
                'status' => $status,
                'processed_by' => $adminId,
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            if ($status === 'approved') {
                $updateData['notes'] = $notes ?: 'Demande approuvée et en cours de traitement';
                
                $this->createNotification(
                    $withdrawal['user_id'], 
                    'withdrawal_approved', 
                    'Retrait approuvé', 
                    "Votre demande de retrait de " . number_format($withdrawal['amount'], 2, ',', ' ') . "€ a été approuvée. Le virement sera effectué sous 24-48h.",
                    $withdrawalId
                );
                
            } elseif ($status === 'processed') {
                if ($transactionReference) {
                    $updateData['transaction_reference'] = $transactionReference;
                }
                $updateData['notes'] = $notes ?: 'Virement effectué';
                
                $newBalance = floatval($withdrawal['balance']) - floatval($withdrawal['amount']);
                $this->db->update('users', ['balance' => max(0, $newBalance)], 'id = ?', [$withdrawal['user_id']]);
                
                $this->createNotification(
                    $withdrawal['user_id'], 
                    'withdrawal_approved', 
                    'Retrait traité', 
                    "Votre virement de " . number_format($withdrawal['amount'], 2, ',', ' ') . "€ a été effectué. Vous devriez le recevoir sous 24-48h selon votre banque." . 
                    ($transactionReference ? " Référence: $transactionReference" : ""),
                    $withdrawalId
                );
                
            } elseif ($status === 'rejected') {
                if (!$notes) {
                    throw new Exception('Motif de refus requis');
                }
                
                $updateData['rejection_reason'] = $notes;
                
                $this->createNotification(
                    $withdrawal['user_id'], 
                    'withdrawal_rejected', 
                    'Retrait refusé', 
                    "Votre demande de retrait a été refusée. Motif: " . $notes,
                    $withdrawalId
                );
            }
            
            $this->db->update('withdrawals', $updateData, 'id = ?', [$withdrawalId]);
            
            $this->db->logActivity($withdrawal['user_id'], $adminId, 'withdrawal_status_updated', "Statut du retrait #$withdrawalId modifié vers: $status");
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Statut mis à jour avec succès'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur mise à jour statut retrait: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getWithdrawalStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count("SELECT COUNT(*) FROM withdrawals");
        $stats['pending'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'");
        $stats['approved'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'approved'");
        $stats['processed'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'processed'");
        $stats['rejected'] = $this->db->count("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'");
        
        $totalProcessed = $this->db->fetchOne("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'processed'");
        $stats['total_processed_amount'] = $totalProcessed['total'] ?? 0;
        
        $avgWithdrawal = $this->db->fetchOne("SELECT AVG(amount) as avg FROM withdrawals WHERE status = 'processed'");
        $stats['avg_withdrawal_amount'] = $avgWithdrawal['avg'] ?? 0;
        
        $processingTime = $this->db->fetchOne("
            SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, processed_at)) as avg_hours
            FROM withdrawals 
            WHERE status = 'processed' AND processed_at IS NOT NULL
        ");
        $stats['avg_processing_time_hours'] = round($processingTime['avg_hours'] ?? 0, 1);
        
        return $stats;
    }
    
    public function getMonthlyWithdrawalStats($year = null) {
        $year = $year ?: date('Y');
        
        return $this->db->fetchAll("
            SELECT 
                MONTH(created_at) as month,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed_requests,
                SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END) as total_amount
            FROM withdrawals 
            WHERE YEAR(created_at) = ?
            GROUP BY MONTH(created_at)
            ORDER BY month
        ", [$year]);
    }
    
    public function searchWithdrawals($query, $status = null, $limit = 20) {
        $sql = "SELECT w.*, lr.approved_amount, lr.partner_bank,
                       u.first_name, u.last_name, u.email, u.phone 
                FROM withdrawals w 
                JOIN loan_requests lr ON w.loan_request_id = lr.id
                JOIN users u ON w.user_id = u.id 
                WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR w.id LIKE ? OR w.transaction_reference LIKE ?)";
        
        $searchTerm = "%$query%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        if ($status) {
            $sql .= " AND w.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY w.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getWithdrawableAmount($userId) {
        $user = $this->db->fetchOne("SELECT balance FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            return 0;
        }
        
        $pendingWithdrawals = $this->db->fetchOne("
            SELECT SUM(amount) as total 
            FROM withdrawals 
            WHERE user_id = ? AND status IN ('pending', 'approved')
        ", [$userId]);
        
        $balance = floatval($user['balance']);
        $pending = floatval($pendingWithdrawals['total'] ?? 0);
        
        return max(0, $balance - $pending);
    }
    
    public function getUserWithdrawalHistory($userId) {
        return $this->db->fetchAll("
            SELECT w.*, lr.partner_bank,
                   CASE 
                       WHEN w.status = 'processed' THEN w.processed_at
                       ELSE w.created_at 
                   END as display_date
            FROM withdrawals w 
            JOIN loan_requests lr ON w.loan_request_id = lr.id
            WHERE w.user_id = ? 
            ORDER BY w.created_at DESC
        ", [$userId]);
    }
    
    public function canUserRequestWithdrawal($userId) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ? AND status = 'active'", [$userId]);
        if (!$user) {
            return ['can_request' => false, 'reason' => 'Utilisateur non trouvé ou inactif'];
        }
        
        $approvedLoan = $this->db->fetchOne("
            SELECT id FROM loan_requests 
            WHERE user_id = ? AND status = 'approved' AND approved_amount > 0
            ORDER BY approved_at DESC 
            LIMIT 1
        ", [$userId]);
        
        if (!$approvedLoan) {
            return ['can_request' => false, 'reason' => 'Aucun prêt approuvé'];
        }
        
        $balance = floatval($user['balance']);
        if ($balance <= 0) {
            return ['can_request' => false, 'reason' => 'Solde insuffisant'];
        }
        
        $pendingWithdrawal = $this->db->fetchOne("
            SELECT id FROM withdrawals 
            WHERE user_id = ? AND status IN ('pending', 'approved') 
            LIMIT 1
        ", [$userId]);
        
        if ($pendingWithdrawal) {
            return ['can_request' => false, 'reason' => 'Demande de retrait en cours'];
        }
        
        $requiredDocs = $this->db->fetchAll("
            SELECT document_type FROM documents 
            WHERE user_id = ? AND is_verified = 1 AND document_type IN ('identity', 'bank_statement')
        ", [$userId]);
        
        if (count($requiredDocs) < 2) {
            return ['can_request' => false, 'reason' => 'Documents manquants ou non vérifiés'];
        }
        
        return [
            'can_request' => true, 
            'max_amount' => $this->getWithdrawableAmount($userId),
            'balance' => $balance
        ];
    }
    
    public function validateBankDetails($data) {
        $errors = [];
        
        if (empty($data['bank_name']) || strlen(trim($data['bank_name'])) < 2) {
            $errors[] = 'Nom de banque requis';
        }
        
        if (empty($data['account_number']) || strlen(trim($data['account_number'])) < 5) {
            $errors[] = 'Numéro de compte requis';
        }
        
        if (empty($data['account_holder_name']) || strlen(trim($data['account_holder_name'])) < 2) {
            $errors[] = 'Nom du titulaire requis';
        }
        
        if (!empty($data['iban']) && !$this->validateIBAN($data['iban'])) {
            $errors[] = 'Format IBAN invalide';
        }
        
        if (!empty($data['swift_code']) && !$this->validateSWIFT($data['swift_code'])) {
            $errors[] = 'Format SWIFT/BIC invalide';
        }
        
        return $errors;
    }
    
    private function validateIBAN($iban) {
        $iban = strtoupper(preg_replace('/[\s\-]/', '', $iban));
        
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }
        
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_digit($char)) {
                $numeric .= $char;
            } else {
                $numeric .= (ord($char) - ord('A') + 10);
            }
        }
        
        return bcmod($numeric, '97') == 1;
    }
    
    private function validateSWIFT($swift) {
        $swift = strtoupper(preg_replace('/[\s\-]/', '', $swift));
        return preg_match('/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $swift);
    }
    
    private function validateWithdrawalData($data) {
        $errors = [];
        
        if (empty($data['amount']) || !is_numeric($data['amount']) || floatval($data['amount']) <= 0) {
            $errors[] = 'Montant invalide';
        } elseif (floatval($data['amount']) < 10) {
            $errors[] = 'Montant minimum: 10€';
        }
        
        $bankErrors = $this->validateBankDetails($data);
        $errors = array_merge($errors, $bankErrors);
        
        return $errors;
    }
    
    private function createNotification($userId, $type, $title, $message, $relatedId = null) {
        return $this->db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId
        ]);
    }
    
    public function updateWithdrawalNotes($withdrawalId, $adminId, $notes) {
        try {
            $this->db->update('withdrawals', ['notes' => $notes], 'id = ?', [$withdrawalId]);
            $this->db->logActivity(null, $adminId, 'withdrawal_notes_updated', "Notes mises à jour pour le retrait #$withdrawalId");
            
            return ['success' => true, 'message' => 'Notes mises à jour'];
        } catch (Exception $e) {
            error_log("Erreur mise à jour notes retrait: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
        }
    }
    
    public function deleteWithdrawal($withdrawalId, $adminId) {
        try {
            $withdrawal = $this->getWithdrawalById($withdrawalId);
            if (!$withdrawal) {
                return ['success' => false, 'message' => 'Demande non trouvée'];
            }
            
            if ($withdrawal['status'] === 'processed') {
                return ['success' => false, 'message' => 'Impossible de supprimer un retrait traité'];
            }
            
            $this->db->delete('withdrawals', 'id = ?', [$withdrawalId]);
            $this->db->logActivity($withdrawal['user_id'], $adminId, 'withdrawal_deleted', "Demande de retrait #$withdrawalId supprimée");
            
            return ['success' => true, 'message' => 'Demande supprimée'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression retrait: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    public function generateWithdrawalReport($startDate = null, $endDate = null) {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-t');
        
        $data = $this->db->fetchAll("
            SELECT 
                DATE(w.created_at) as date,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN w.status = 'processed' THEN 1 END) as processed,
                SUM(CASE WHEN w.status = 'processed' THEN w.amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN w.status = 'processed' THEN TIMESTAMPDIFF(HOUR, w.created_at, w.processed_at) END) as avg_processing_hours
            FROM withdrawals w
            WHERE DATE(w.created_at) BETWEEN ? AND ?
            GROUP BY DATE(w.created_at)
            ORDER BY date DESC
        ", [$startDate, $endDate]);
        
        $summary = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'processed' THEN 1 END) as processed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                SUM(CASE WHEN status = 'processed' THEN amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN status = 'processed' THEN amount END) as avg_amount
            FROM withdrawals
            WHERE DATE(created_at) BETWEEN ? AND ?
        ", [$startDate, $endDate]);
        
        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => $summary,
            'daily_data' => $data
        ];
    }
}