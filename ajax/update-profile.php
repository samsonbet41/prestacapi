<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Language.php';

session_start();

try {
    $user = new User();
    $lang = Language::getInstance();
    
    if (!$user->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
        exit;
    }
    
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    $updateType = $_POST['update_type'] ?? 'profile';
    
    switch ($updateType) {
        case 'profile':
            $result = $user->updateProfile($userId, $_POST);
            break;
            
        case 'password':
            if (empty($_POST['current_password']) || empty($_POST['new_password'])) {
                echo json_encode(['success' => false, 'message' => 'Mot de passe actuel et nouveau mot de passe requis']);
                exit;
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                echo json_encode(['success' => false, 'message' => 'Les mots de passe ne correspondent pas']);
                exit;
            }
            
            $result = $user->changePassword($userId, $_POST['current_password'], $_POST['new_password']);
            break;
            
        case 'personal_info':
            $allowedFields = ['first_name', 'last_name', 'date_of_birth', 'phone', 'whatsapp'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = $_POST[$field];
                }
            }
            
            if (empty($updateData)) {
                echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour']);
                exit;
            }
            
            if (!empty($updateData['first_name']) && strlen(trim($updateData['first_name'])) < 2) {
                echo json_encode(['success' => false, 'message' => 'Le prénom doit contenir au moins 2 caractères']);
                exit;
            }
            
            if (!empty($updateData['last_name']) && strlen(trim($updateData['last_name'])) < 2) {
                echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 2 caractères']);
                exit;
            }
            
            if (!empty($updateData['phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $updateData['phone'])) {
                echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
                exit;
            }
            
            if (!empty($updateData['whatsapp']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $updateData['whatsapp'])) {
                echo json_encode(['success' => false, 'message' => 'Numéro WhatsApp invalide']);
                exit;
            }
            
            if (!empty($updateData['date_of_birth'])) {
                $birthDate = new DateTime($updateData['date_of_birth']);
                $today = new DateTime();
                $age = $today->diff($birthDate)->y;
                
                if ($age < 18) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être majeur(e)']);
                    exit;
                }
                
                if ($age > 100) {
                    echo json_encode(['success' => false, 'message' => 'Date de naissance invalide']);
                    exit;
                }
            }
            
            $result = $user->updateProfile($userId, $updateData);
            break;
            
        case 'address':
            $allowedFields = ['address', 'city', 'postal_code', 'country'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($_POST[$field])) {
                    $updateData[$field] = $_POST[$field];
                }
            }
            
            if (empty($updateData)) {
                echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour']);
                exit;
            }
            
            if (!empty($updateData['postal_code']) && !preg_match('/^[\d\-\s]{3,10}$/', $updateData['postal_code'])) {
                echo json_encode(['success' => false, 'message' => 'Code postal invalide']);
                exit;
            }
            
            $result = $user->updateProfile($userId, $updateData);
            break;
            
        case 'preferences':
            $db = Database::getInstance();
            $preferences = [];
            
            $booleanPrefs = ['email_notifications', 'sms_notifications', 'marketing_emails'];
            foreach ($booleanPrefs as $pref) {
                if (isset($_POST[$pref])) {
                    $preferences[$pref] = (bool)$_POST[$pref];
                }
            }
            
            if (isset($_POST['preferred_language']) && $lang->isLanguageSupported($_POST['preferred_language'])) {
                $preferences['preferred_language'] = $_POST['preferred_language'];
            }
            
            if (isset($_POST['preferred_contact_method'])) {
                $validMethods = ['email', 'phone', 'whatsapp'];
                if (in_array($_POST['preferred_contact_method'], $validMethods)) {
                    $preferences['preferred_contact_method'] = $_POST['preferred_contact_method'];
                }
            }
            
            if (!empty($preferences)) {
                $existingPrefs = $db->fetchOne("SELECT preferences FROM users WHERE id = ?", [$userId])['preferences'] ?? '{}';
                $currentPrefs = json_decode($existingPrefs, true) ?: [];
                $newPrefs = array_merge($currentPrefs, $preferences);
                
                $db->update('users', ['preferences' => json_encode($newPrefs)], 'id = ?', [$userId]);
                $db->logActivity($userId, null, 'preferences_updated', 'Préférences utilisateur mises à jour');
                
                $result = ['success' => true, 'message' => 'Préférences mises à jour avec succès'];
            } else {
                $result = ['success' => false, 'message' => 'Aucune préférence à mettre à jour'];
            }
            break;
            
        case 'delete_account':
            if (empty($_POST['password']) || empty($_POST['confirmation'])) {
                echo json_encode(['success' => false, 'message' => 'Mot de passe et confirmation requis']);
                exit;
            }
            
            if ($_POST['confirmation'] !== 'DELETE') {
                echo json_encode(['success' => false, 'message' => 'Tapez "DELETE" pour confirmer']);
                exit;
            }
            
            $userCheck = $user->getCurrentUser();
            if (!password_verify($_POST['password'], $userCheck['password'])) {
                echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect']);
                exit;
            }
            
            $db = Database::getInstance();
            $activeLoan = $db->fetchOne("SELECT id FROM loan_requests WHERE user_id = ? AND status IN ('pending', 'under_review', 'approved') LIMIT 1", [$userId]);
            
            if ($activeLoan) {
                echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le compte avec des prêts actifs']);
                exit;
            }
            
            $pendingWithdrawal = $db->fetchOne("SELECT id FROM withdrawals WHERE user_id = ? AND status IN ('pending', 'approved') LIMIT 1", [$userId]);
            
            if ($pendingWithdrawal) {
                echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le compte avec des retraits en cours']);
                exit;
            }
            
            $db->beginTransaction();
            try {
                $db->delete('notifications', 'user_id = ?', [$userId]);
                $db->delete('documents', 'user_id = ?', [$userId]);
                $db->delete('testimonials', 'user_id = ?', [$userId]);
                $db->update('loan_requests', ['user_id' => null], 'user_id = ?', [$userId]);
                $db->update('withdrawals', ['user_id' => null], 'user_id = ?', [$userId]);
                $db->delete('users', 'id = ?', [$userId]);
                
                $db->logActivity(null, null, 'account_deleted', "Compte utilisateur supprimé: {$userCheck['email']}");
                $db->commit();
                
                $user->logout();
                
                $result = ['success' => true, 'message' => 'Compte supprimé avec succès', 'redirect' => '/'];
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Type de mise à jour invalide']);
            exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur AJAX update-profile: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour']);
}