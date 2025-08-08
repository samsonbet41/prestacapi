<?php
header('Content-Type: application/json');
session_start();

require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Language.php';

$lang = Language::getInstance();
$user = new User();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise']);
    exit;
}

try {
    $currentUser = $user->getCurrentUser();
    $userId = $currentUser['id'];
    
    $limit = intval($_GET['limit'] ?? 10);
    $limit = min(max($limit, 1), 50);
    
    $notifications = $user->getUserNotifications($userId, $limit);
    $unreadCount = $user->getUnreadNotificationsCount($userId);
    
    $html = '';
    
    if (empty($notifications)) {
        $html = '
        <div class="empty-notifications">
            <div class="empty-icon">🔔</div>
            <p>Aucune notification</p>
        </div>';
    } else {
        foreach ($notifications as $notification) {
            $icons = [
                'loan_approved' => '✅',
                'loan_rejected' => '❌',
                'withdrawal_approved' => '💰',
                'withdrawal_rejected' => '⚠️',
                'document_verified' => '📄',
                'general' => '🔔'
            ];
            
            $icon = $icons[$notification['type']] ?? '🔔';
            $unreadClass = !$notification['is_read'] ? 'unread' : '';
            $timeAgo = $lang->getTimeAgo($notification['created_at']);
            
            $html .= '
            <div class="notification-item ' . $unreadClass . '" data-id="' . $notification['id'] . '">
                <div class="notification-icon">' . $icon . '</div>
                <div class="notification-content">
                    <h4 class="notification-title">' . htmlspecialchars($notification['title']) . '</h4>
                    <p class="notification-message">' . htmlspecialchars($notification['message']) . '</p>
                    <span class="notification-time">' . $timeAgo . '</span>
                </div>';
                
            if (!$notification['is_read']) {
                $html .= '
                <button class="notification-mark-read" onclick="markNotificationRead(' . $notification['id'] . ')" title="Marquer comme lu">
                    ✓
                </button>';
            }
            
            $html .= '</div>';
        }
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'unread_count' => $unreadCount,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    error_log("Erreur notifications AJAX: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors du chargement des notifications'
    ]);
}