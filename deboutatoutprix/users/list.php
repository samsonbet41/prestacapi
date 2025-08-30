<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_users');

require_once __DIR__ . '/../../classes/Language.php';
$lang = Language::getInstance();

$pageTitle = 'Gestion des utilisateurs';

$db = Database::getInstance();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

$whereConditions = [];
$params = [];

if (!empty($status)) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$allowedSorts = ['created_at', 'first_name', 'last_name', 'email', 'status', 'balance'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'created_at';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

$users = $db->fetchAll("
    SELECT *, 
           (SELECT COUNT(*) FROM loan_requests WHERE user_id = users.id) as loan_count,
           (SELECT COUNT(*) FROM loan_requests WHERE user_id = users.id AND status = 'approved') as approved_loans,
           (SELECT COUNT(*) FROM withdrawals WHERE user_id = users.id) as withdrawal_count
    FROM users 
    $whereClause 
    ORDER BY $sortBy $sortOrder 
    LIMIT ? OFFSET ?
", array_merge($params, [$limit, $offset]));

$totalUsers = $db->count("SELECT COUNT(*) FROM users $whereClause", $params);
$totalPages = ceil($totalUsers / $limit);

$userStats = [
    'total' => $db->count("SELECT COUNT(*) FROM users"),
    'active' => $db->count("SELECT COUNT(*) FROM users WHERE status = 'active'"),
    'suspended' => $db->count("SELECT COUNT(*) FROM users WHERE status = 'suspended'"),
    'today' => $db->count("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Gestion des utilisateurs</h1>
        <p class="page-subtitle">Liste et administration des comptes utilisateurs</p>
        <div class="page-actions">
            <div class="export-dropdown">
                <button class="export-btn">
                    📊 Exporter
                    <span>▼</span>
                </button>
                <div class="export-dropdown-content">
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="users" data-format="csv">CSV</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="users" data-format="excel">Excel</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="users" data-format="json">JSON</a>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Actualiser
            </button>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total utilisateurs</div>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-value"><?php echo number_format($userStats['total']); ?></div>
            <div class="stat-change positive">+<?php echo $userStats['today']; ?> aujourd'hui</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Actifs</div>
                <div class="stat-icon" style="background: var(--success-color);">✅</div>
            </div>
            <div class="stat-value"><?php echo number_format($userStats['active']); ?></div>
            <div class="stat-change positive"><?php echo round(($userStats['active'] / $userStats['total']) * 100, 1); ?>%</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Suspendus</div>
                <div class="stat-icon" style="background: var(--error-color);">🚫</div>
            </div>
            <div class="stat-value"><?php echo number_format($userStats['suspended']); ?></div>
            <div class="stat-change <?php echo $userStats['suspended'] > 0 ? 'negative' : 'positive'; ?>">
                <?php echo round(($userStats['suspended'] / $userStats['total']) * 100, 1); ?>%
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Nouveaux</div>
                <div class="stat-icon" style="background: var(--info-color);">📈</div>
            </div>
            <div class="stat-value"><?php echo number_format($userStats['today']); ?></div>
            <div class="stat-change positive">Aujourd'hui</div>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" class="filter-form" style="display: flex; align-items: center; gap: 1rem; width: 100%;">
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <select name="status" class="filter-select">
                    <option value="">Tous</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspendu</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Du:</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="filter-date">
            </div>

            <div class="filter-group">
                <label class="filter-label">Au:</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="filter-date">
            </div>

            <div class="filter-group" style="flex: 1;">
                <input type="text" name="search" placeholder="Rechercher par nom, email, téléphone..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="filter-search">
            </div>

            <button type="submit" class="btn btn-primary">Filtrer</button>
            <?php if (!empty($status) || !empty($search) || !empty($dateFrom) || !empty($dateTo)): ?>
                <a href="list.php" class="btn btn-secondary">Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bulk-actions" id="bulkActions">
        <span class="bulk-actions-text">
            <span class="selected-count">0</span> utilisateur(s) sélectionné(s)
        </span>
        <div class="bulk-actions-buttons">
            <button class="btn btn-sm btn-success" data-action="bulk-action" data-bulk-action="activate">
                Activer
            </button>
            <button class="btn btn-sm btn-warning" data-action="bulk-action" data-bulk-action="suspend">
                Suspendre
            </button>
            <button class="btn btn-sm btn-error" data-action="bulk-action" data-bulk-action="delete" 
                    data-confirm="Êtes-vous sûr de vouloir supprimer les utilisateurs sélectionnés ?">
                Supprimer
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des utilisateurs 
                <span class="badge badge-info"><?php echo number_format($totalUsers); ?></span>
            </h3>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.875rem; color: #6B7280;">
                    Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                </span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.875rem;">Trier par:</label>
                    <select onchange="changeSorting(this.value)" style="padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid #E5E7EB;">
                        <option value="created_at-DESC" <?php echo ($sortBy === 'created_at' && $sortOrder === 'DESC') ? 'selected' : ''; ?>>Plus récents</option>
                        <option value="created_at-ASC" <?php echo ($sortBy === 'created_at' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Plus anciens</option>
                        <option value="first_name-ASC" <?php echo ($sortBy === 'first_name' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Nom A-Z</option>
                        <option value="first_name-DESC" <?php echo ($sortBy === 'first_name' && $sortOrder === 'DESC') ? 'selected' : ''; ?>>Nom Z-A</option>
                        <option value="balance-DESC" <?php echo ($sortBy === 'balance' && $sortOrder === 'DESC') ? 'selected' : ''; ?>>Solde élevé</option>
                        <option value="balance-ASC" <?php echo ($sortBy === 'balance' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Solde faible</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <h3 class="empty-state-title">Aucun utilisateur trouvé</h3>
                    <p class="empty-state-description">
                        <?php if (!empty($search) || !empty($status)): ?>
                            Aucun utilisateur ne correspond aux critères de recherche.
                        <?php else: ?>
                            Il n'y a encore aucun utilisateur inscrit sur la plateforme.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" name="select-all" id="selectAll">
                            </th>
                            <th>Utilisateur</th>
                            <th>Contact</th>
                            <th>Statut</th>
                            <th>Solde</th>
                            <th>Prêts</th>
                            <th>Inscription</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo getPriorityClass($user['status']); ?>">
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $user['id']; ?>">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, var(--primary-color), var(--accent-1)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: var(--accent-2);">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6B7280;">
                                                ID: <?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="font-size: 0.875rem; margin-bottom: 0.25rem;">
                                            📧 <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                        <?php if ($user['phone']): ?>
                                            <div style="font-size: 0.75rem; color: #6B7280;">
                                                📱 <?php echo htmlspecialchars($user['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-status="<?php echo $user['status']; ?>">
                                    <?php echo getStatusBadge($user['status']); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo formatCurrency($user['balance']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <div>
                                            <span style="font-weight: 500;"><?php echo $user['loan_count']; ?></span> total
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <span style="color: var(--success-color);"><?php echo $user['approved_loans']; ?></span> approuvés
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php echo formatDateTime($user['created_at']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        <?php echo $lang->getTimeAgo($user['created_at']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="view.php?id=<?php echo $user['id']; ?>" class="action-btn view" title="Voir détails">
                                            👁️
                                        </a>
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="action-btn edit" title="Modifier">
                                            ✏️
                                        </a>
                                        <button class="action-btn <?php echo $user['status'] === 'active' ? 'delete' : 'view'; ?>" 
                                                data-action="toggle-user-status" 
                                                data-id="<?php echo $user['id']; ?>" 
                                                data-current-status="<?php echo $user['status']; ?>"
                                                title="<?php echo $user['status'] === 'active' ? 'Suspendre' : 'Activer'; ?>">
                                            <?php echo $user['status'] === 'active' ? '🚫' : '✅'; ?>
                                        </button>
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
                <div style="display: flex; justify-content: between; align-items: center;">
                    <div style="font-size: 0.875rem; color: #6B7280;">
                        Affichage de <?php echo number_format($offset + 1); ?> à <?php echo number_format(min($offset + $limit, $totalUsers)); ?> 
                        sur <?php echo number_format($totalUsers); ?> utilisateurs
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                               class="btn btn-sm btn-secondary">««</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="btn btn-sm btn-secondary">‹</a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="btn btn-sm btn-secondary">›</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" 
                               class="btn btn-sm btn-secondary">»»</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
function changeSorting(value) {
    const [sort, order] = value.split('-');
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.set('order', order);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const bulkActions = document.getElementById('bulkActions');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }
    
    function updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('input[name="selected[]"]:checked');
        const selectedCount = selectedCheckboxes.length;
        
        if (selectedCount > 0) {
            bulkActions.classList.add('show');
            const countElement = bulkActions.querySelector('.selected-count');
            if (countElement) {
                countElement.textContent = selectedCount;
            }
        } else {
            bulkActions.classList.remove('show');
        }
    }
    
    document.querySelectorAll('input[name="selected[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        const searchInput = filterForm.querySelector('input[name="search"]');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    filterForm.submit();
                }
            }, 500);
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>