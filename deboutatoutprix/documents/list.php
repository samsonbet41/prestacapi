<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/Document.php';
require_once '../../classes/Language.php';
requirePermission('manage_documents');

$pageTitle = 'Gestion des documents';

$doc_obj = new Document();
$db = Database::getInstance();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtres et recherche
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$userId = $_GET['user_id'] ?? '';

$whereConditions = [];
$params = [];

if ($status === 'pending') {
    $whereConditions[] = "d.is_verified = 0";
} elseif ($status === 'verified') {
    $whereConditions[] = "d.is_verified = 1";
}

if (!empty($userId)) {
    $whereConditions[] = "d.user_id = ?";
    $params[] = $userId;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR d.document_type LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$documents = $db->fetchAll("
    SELECT d.*, u.first_name, u.last_name, u.email
    FROM documents d
    JOIN users u ON d.user_id = u.id
    $whereClause
    ORDER BY d.uploaded_at DESC
    LIMIT ? OFFSET ?
", array_merge($params, [$limit, $offset]));

$totalDocuments = $db->count("
    SELECT COUNT(*) FROM documents d JOIN users u ON d.user_id = u.id $whereClause
", $params);

$totalPages = ceil($totalDocuments / $limit);
$docStats = $doc_obj->getDocumentStats();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Gestion des documents</h1>
        <p class="page-subtitle">Examinez et validez les documents soumis par les utilisateurs.</p>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total Documents</div>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-value"><?php echo number_format($docStats['total']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">En attente</div>
                <div class="stat-icon" style="background: var(--warning-color);">⏳</div>
            </div>
            <div class="stat-value"><?php echo number_format($docStats['pending']); ?></div>
             <div class="stat-change <?php echo $docStats['pending'] > 0 ? 'priority' : 'positive'; ?>">À vérifier</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Vérifiés</div>
                <div class="stat-icon" style="background: var(--success-color);">✅</div>
            </div>
            <div class="stat-value"><?php echo number_format($docStats['verified']); ?></div>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <select name="status" class="filter-select">
                    <option value="">Tous</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Vérifié</option>
                </select>
            </div>
             <div class="filter-group" style="flex: 1;">
                <input type="text" name="search" placeholder="Rechercher par utilisateur, email, type..."
                       value="<?php echo htmlspecialchars($search); ?>" class="filter-search">
            </div>
            <button type="submit" class="btn btn-primary">Filtrer</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Liste des documents <span class="badge badge-info"><?php echo number_format($totalDocuments); ?></span></h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📄</div>
                    <h3 class="empty-state-title">Aucun document trouvé</h3>
                    <p>Aucun document ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Type de document</th>
                            <th>Date d'upload</th>
                            <th>Statut</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div>
                                        <a href="../users/view.php?id=<?php echo $doc['user_id']; ?>" style="font-weight: 500;"><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></a>
                                        <div style="font-size: 0.8rem; color: #6c757d;"><?php echo htmlspecialchars($doc['email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($doc_obj->getDocumentTypeName($doc['document_type'])); ?></strong>
                                </td>
                                <td><?php echo formatDateTime($doc['uploaded_at']); ?></td>
                                <td>
                                    <?php if ($doc['is_verified']): ?>
                                        <span class="badge badge-success">Vérifié</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="view.php?id=<?php echo $doc['id']; ?>" class="action-btn view" title="Voir le document">👁️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
               </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>