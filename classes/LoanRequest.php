<?php
require_once 'Database.php';
require_once 'Mailer.php';

class LoanRequest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createLoanRequest($userId, $data, $languageCode) {
        try {
            $errors = $this->validateLoanRequestData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => implode(', ', $errors)];
            }
            
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return ['success' => false, 'message' => 'Utilisateur non trouvé'];
            }
            
            $activeLoanRequest = $this->db->fetchOne("
                SELECT id FROM loan_requests 
                WHERE user_id = ? AND status IN ('pending', 'under_review') 
                LIMIT 1
            ", [$userId]);
            
            if ($activeLoanRequest) {
                return ['success' => false, 'message' => 'Vous avez déjà une demande de prêt en cours'];
            }
            
            $loanData = [
                'user_id' => $userId,
                'amount' => floatval($data['amount']),
                'duration' => intval($data['duration']),
                'purpose' => trim($data['purpose']),
                'monthly_income' => floatval($data['monthly_income']),
                'monthly_expenses' => floatval($data['monthly_expenses']),
                'employment_status' => trim($data['employment_status']),
                'employment_duration' => intval($data['employment_duration']),
                'employer_name' => trim($data['employer_name'] ?? ''),
                'employer_phone' => trim($data['employer_phone'] ?? ''),
                'other_loans' => floatval($data['other_loans'] ?? 0),
                'collateral' => trim($data['collateral'] ?? ''),
                'co_signer_name' => trim($data['co_signer_name'] ?? ''),
                'co_signer_phone' => trim($data['co_signer_phone'] ?? ''),
                'notes' => trim($data['notes'] ?? ''),
                'status' => 'pending'
            ];
            
            $loanId = $this->db->insert('loan_requests', $loanData);
            
            if ($loanId) {
                $this->db->logActivity($userId, null, 'loan_request_created', 'Nouvelle demande de prêt créée', null, null);
                
                $this->createNotification($userId, 'general', 'Demande de prêt envoyée', 'Votre demande de prêt a été envoyée avec succès. Vous recevrez une réponse sous 48-72h.', $loanId);
                
                $mailer = new Mailer();
                $loanData['id'] = $loanId;
                $mailer->sendLoanRequestConfirmation($user, $loanData, $languageCode);
                $mailer->sendAdminNotification('new_loan_request', array_merge($loanData, [
                    'user_name' => $user['first_name'] . ' ' . $user['last_name']
                ]));
                
                return [
                    'success' => true, 
                    'message' => 'Demande de prêt envoyée avec succès',
                    'loan_id' => $loanId
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création de la demande'];
            
        } catch (Exception $e) {
            error_log("Erreur création demande prêt: " . $e->getMessage());
            return ['success' => false, 'message' => 'Une erreur est survenue lors de la création de la demande'];
        }
    }
    
    public function getLoanRequestById($id, $userId = null) {
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.email, u.phone 
                FROM loan_requests lr 
                JOIN users u ON lr.user_id = u.id 
                WHERE lr.id = ?";
        $params = [$id];
        
        if ($userId) {
            $sql .= " AND lr.user_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    public function getUserLoanRequests($userId, $limit = null) {
        $sql = "SELECT * FROM loan_requests WHERE user_id = ? ORDER BY created_at DESC";
        $params = [$userId];
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getAllLoanRequests($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.email, u.phone,
                       CASE WHEN lr.status = 'pending' THEN 1
                            WHEN lr.status = 'under_review' THEN 2
                            WHEN lr.status = 'approved' THEN 3
                            WHEN lr.status = 'rejected' THEN 4
                            ELSE 5 END as status_priority
                FROM loan_requests lr 
                JOIN users u ON lr.user_id = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE lr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY status_priority, lr.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function updateLoanRequestStatus($loanId, $status, $adminId, $notes = null, $approvedAmount = null, $partnerBank = null) {
        try {
            $this->db->beginTransaction();
            
            $loanRequest = $this->getLoanRequestById($loanId);
            if (!$loanRequest) {
                throw new Exception('Demande de prêt non trouvée');
            }
            
            $updateData = [
                'status' => $status,
                'notes' => $notes
            ];
            
            if ($status === 'approved') {
                if (!$approvedAmount || $approvedAmount <= 0) {
                    throw new Exception('Montant approuvé requis');
                }
                
                $updateData['approved_amount'] = floatval($approvedAmount);
                $updateData['approved_by'] = $adminId;
                $updateData['approved_at'] = date('Y-m-d H:i:s');
                $updateData['partner_bank'] = $partnerBank ?: 'PrestaCapi';
                
                $this->db->update('users', ['balance' => $approvedAmount], 'id = ?', [$loanRequest['user_id']]);
                
                $this->createNotification(
                    $loanRequest['user_id'], 
                    'loan_approved', 
                    'Prêt approuvé !', 
                    "Félicitations ! Votre demande de prêt de " . number_format($approvedAmount, 0, ',', ' ') . " € a été approuvée.",
                    $loanId
                );
                
            } elseif ($status === 'rejected') {
                if (!$notes) {
                    throw new Exception('Motif de refus requis');
                }
                
                $updateData['rejection_reason'] = $notes;
                
                $this->createNotification(
                    $loanRequest['user_id'], 
                    'loan_rejected', 
                    'Demande de prêt non approuvée', 
                    "Votre demande de prêt n'a pas pu être approuvée. Motif: " . $notes,
                    $loanId
                );
            }
            
            $this->db->update('loan_requests', $updateData, 'id = ?', [$loanId]);
            
            $this->db->logActivity($loanRequest['user_id'], $adminId, 'loan_status_updated', "Statut de la demande #$loanId modifié vers: $status");
            
            $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$loanRequest['user_id']]);
            $loanData = array_merge($loanRequest, $updateData);
            
            $mailer = new Mailer();
            if ($status === 'approved') {
                $mailer->sendLoanApprovalEmail($user, $loanData);
            } elseif ($status === 'rejected') {
                $mailer->sendLoanRejectionEmail($user, $loanData);
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Statut mis à jour avec succès'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Erreur mise à jour statut prêt: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getLoanRequestStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count("SELECT COUNT(*) FROM loan_requests");
        $stats['pending'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'pending'");
        $stats['under_review'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'under_review'");
        $stats['approved'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'approved'");
        $stats['rejected'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'rejected'");
        $stats['disbursed'] = $this->db->count("SELECT COUNT(*) FROM loan_requests WHERE status = 'disbursed'");
        
        $totalApproved = $this->db->fetchOne("SELECT SUM(approved_amount) as total FROM loan_requests WHERE status = 'approved'");
        $stats['total_approved_amount'] = $totalApproved['total'] ?? 0;
        
        $avgApproved = $this->db->fetchOne("SELECT AVG(approved_amount) as avg FROM loan_requests WHERE status = 'approved'");
        $stats['avg_approved_amount'] = $avgApproved['avg'] ?? 0;
        
        $approvalRate = $this->db->fetchOne("
            SELECT 
                (COUNT(CASE WHEN status = 'approved' THEN 1 END) * 100.0 / 
                 COUNT(CASE WHEN status IN ('approved', 'rejected') THEN 1 END)) as rate
            FROM loan_requests
        ");
        $stats['approval_rate'] = round($approvalRate['rate'] ?? 0, 1);
        
        return $stats;
    }
    
    public function getMonthlyStats($year = null) {
        $year = $year ?: date('Y');
        
        return $this->db->fetchAll("
            SELECT 
                MONTH(created_at) as month,
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
                SUM(CASE WHEN status = 'approved' THEN approved_amount ELSE 0 END) as total_amount
            FROM loan_requests 
            WHERE YEAR(created_at) = ?
            GROUP BY MONTH(created_at)
            ORDER BY month
        ", [$year]);
    }
    
    public function searchLoanRequests($query, $status = null, $limit = 20) {
        $sql = "SELECT lr.*, u.first_name, u.last_name, u.email, u.phone 
                FROM loan_requests lr 
                JOIN users u ON lr.user_id = u.id 
                WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR lr.id LIKE ?)";
        
        $searchTerm = "%$query%";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        
        if ($status) {
            $sql .= " AND lr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY lr.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    public function calculateLoanEligibility($userData, $loanData) {
        $score = 0;
        $factors = [];
        
        $monthlyIncome = floatval($loanData['monthly_income']);
        $monthlyExpenses = floatval($loanData['monthly_expenses']);
        $requestedAmount = floatval($loanData['amount']);
        $employmentDuration = intval($loanData['employment_duration']);
        
        $netIncome = $monthlyIncome - $monthlyExpenses;
        $debtToIncomeRatio = $monthlyExpenses / $monthlyIncome * 100;
        
        if ($netIncome > 0) {
            $score += 25;
            $factors[] = "Revenu net positif: +" . number_format($netIncome, 0) . "€";
        } else {
            $factors[] = "Revenu net négatif: " . number_format($netIncome, 0) . "€";
        }
        
        if ($debtToIncomeRatio < 30) {
            $score += 25;
            $factors[] = "Ratio d'endettement faible: " . round($debtToIncomeRatio, 1) . "%";
        } elseif ($debtToIncomeRatio < 50) {
            $score += 15;
            $factors[] = "Ratio d'endettement modéré: " . round($debtToIncomeRatio, 1) . "%";
        } else {
            $factors[] = "Ratio d'endettement élevé: " . round($debtToIncomeRatio, 1) . "%";
        }
        
        if ($employmentDuration >= 24) {
            $score += 20;
            $factors[] = "Emploi stable (+" . $employmentDuration . " mois)";
        } elseif ($employmentDuration >= 12) {
            $score += 10;
            $factors[] = "Emploi récent (" . $employmentDuration . " mois)";
        } else {
            $factors[] = "Emploi très récent (" . $employmentDuration . " mois)";
        }
        
        $loanToIncomeRatio = $requestedAmount / ($monthlyIncome * 12) * 100;
        if ($loanToIncomeRatio < 100) {
            $score += 15;
            $factors[] = "Montant raisonnable par rapport aux revenus";
        } elseif ($loanToIncomeRatio < 200) {
            $score += 5;
            $factors[] = "Montant modéré par rapport aux revenus";
        } else {
            $factors[] = "Montant élevé par rapport aux revenus";
        }
        
        if (!empty($loanData['collateral'])) {
            $score += 10;
            $factors[] = "Garantie fournie";
        }
        
        if (!empty($loanData['co_signer_name'])) {
            $score += 5;
            $factors[] = "Co-signataire présent";
        }
        
        $recommendation = 'rejected';
        if ($score >= 70) {
            $recommendation = 'approved';
        } elseif ($score >= 50) {
            $recommendation = 'under_review';
        }
        
        return [
            'score' => $score,
            'recommendation' => $recommendation,
            'factors' => $factors,
            'debt_to_income_ratio' => $debtToIncomeRatio,
            'net_income' => $netIncome,
            'loan_to_income_ratio' => $loanToIncomeRatio
        ];
    }
    
    public function getRequiredDocuments($loanAmount) {
        $documents = [
            'identity' => [
                'name' => 'Pièce d\'identité',
                'required' => true,
                'description' => 'Carte d\'identité, passeport ou permis de conduire en cours de validité'
            ],
            'income_proof' => [
                'name' => 'Justificatifs de revenus',
                'required' => true,
                'description' => '3 derniers bulletins de paie ou attestation de revenus'
            ],
            'bank_statement' => [
                'name' => 'Relevés bancaires',
                'required' => true,
                'description' => '3 derniers relevés de compte bancaire'
            ]
        ];
        
        if ($loanAmount >= 10000) {
            $documents['employment_certificate'] = [
                'name' => 'Attestation d\'emploi',
                'required' => true,
                'description' => 'Certificat de travail ou contrat de travail'
            ];
        }
        
        if ($loanAmount >= 20000) {
            $documents['birth_certificate'] = [
                'name' => 'Acte de naissance',
                'required' => true,
                'description' => 'Acte de naissance de moins de 3 mois'
            ];
        }
        
        return $documents;
    }
    
    private function validateLoanRequestData($data) {
        $errors = [];
        
        if (empty($data['amount']) || !is_numeric($data['amount']) || floatval($data['amount']) <= 0) {
            $errors[] = 'Montant invalide';
        } elseif (floatval($data['amount']) < 500) {
            $errors[] = 'Montant minimum: 500€';
        } elseif (floatval($data['amount']) > 50000) {
            $errors[] = 'Montant maximum: 50 000€';
        }
        
        if (empty($data['duration']) || !is_numeric($data['duration']) || intval($data['duration']) <= 0) {
            $errors[] = 'Durée invalide';
        } elseif (intval($data['duration']) < 6) {
            $errors[] = 'Durée minimum: 6 mois';
        } elseif (intval($data['duration']) > 60) {
            $errors[] = 'Durée maximum: 60 mois';
        }
        
        if (empty($data['purpose']) || strlen(trim($data['purpose'])) < 3) {
            $errors[] = 'Objectif du prêt requis (minimum 3 caractères)';
        }
        
        if (empty($data['monthly_income']) || !is_numeric($data['monthly_income']) || floatval($data['monthly_income']) <= 0) {
            $errors[] = 'Revenus mensuels requis';
        }
        
        if (empty($data['monthly_expenses']) || !is_numeric($data['monthly_expenses']) || floatval($data['monthly_expenses']) < 0) {
            $errors[] = 'Charges mensuelles requises';
        }
        
        if (floatval($data['monthly_income']) <= floatval($data['monthly_expenses'])) {
            $errors[] = 'Les revenus doivent être supérieurs aux charges';
        }
        
        if (empty($data['employment_status'])) {
            $errors[] = 'Statut d\'emploi requis';
        }
        
        if (empty($data['employment_duration']) || !is_numeric($data['employment_duration']) || intval($data['employment_duration']) < 0) {
            $errors[] = 'Durée d\'emploi requise';
        }
        
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
    
    public function getPartnerBanks() {
        return $this->db->fetchAll("
            SELECT name, type, logo FROM partners 
            WHERE is_active = 1 AND type IN ('bank', 'microfinance', 'lender')
            ORDER BY display_order, name
        ");
    }
    
    public function updateLoanRequestNotes($loanId, $adminId, $notes) {
        try {
            $this->db->update('loan_requests', ['notes' => $notes], 'id = ?', [$loanId]);
            $this->db->logActivity(null, $adminId, 'loan_notes_updated', "Notes mises à jour pour la demande #$loanId");
            
            return ['success' => true, 'message' => 'Notes mises à jour'];
        } catch (Exception $e) {
            error_log("Erreur mise à jour notes: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
        }
    }
    
    public function deleteLoanRequest($loanId, $adminId) {
        try {
            $loanRequest = $this->getLoanRequestById($loanId);
            if (!$loanRequest) {
                return ['success' => false, 'message' => 'Demande non trouvée'];
            }
            
            if ($loanRequest['status'] === 'approved') {
                return ['success' => false, 'message' => 'Impossible de supprimer une demande approuvée'];
            }
            
            $this->db->delete('loan_requests', 'id = ?', [$loanId]);
            $this->db->logActivity($loanRequest['user_id'], $adminId, 'loan_request_deleted', "Demande de prêt #$loanId supprimée");
            
            return ['success' => true, 'message' => 'Demande supprimée'];
            
        } catch (Exception $e) {
            error_log("Erreur suppression demande: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
}