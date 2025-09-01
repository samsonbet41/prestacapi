<?php
header('Content-Type: application/json');
require_once '../includes/auth-admin.php';

// Vous pouvez affiner les permissions ici
if (!hasPermission('manage_blog')) {
    echo json_encode(['success' => false, 'message' => 'Permission non accordée']);
    exit;
}

require_once __DIR__ . '/../../classes/Blog.php';
require_once __DIR__ . '/../../classes/Language.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$postId = intval($input['post_id'] ?? 0);

if (!$action || $postId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$blog_obj = new Blog();
$response = ['success' => false, 'message' => 'Action inconnue'];

switch ($action) {
    case 'delete':
        $result = $blog_obj->deletePost($postId);
        if ($result['success']) {
            $response = ['success' => true, 'message' => 'Article supprimé avec succès'];
        } else {
            $response = ['success' => false, 'message' => $result['message']];
        }
        break;
    
    // Vous pourriez ajouter d'autres actions ici, comme 'toggle_publish'
}

echo json_encode($response);