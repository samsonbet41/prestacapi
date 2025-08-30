<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/LoanRequest.php';
requirePermission('manage_loans');

$pageTitle = 'Demandes de prêt';

$loanRequest = new LoanRequest();
$db = Database::getInstance();

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$minAmount = $_GET['min_amount'] ?? '';
$maxAmount = $_GET['max_amount'] ?? '';
$sortBy = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';
$userId = $_GET['user_id'] ?? '';

$whereConditions = [];
$params = [];

if (!empty($status)) {
    $whereConditions[] = "lr.status = ?";
    $params[] = $status;
}

if (!empty($userId)) {
    $whereConditions[] = "lr.user_id = ?";
    $params[] = $userId;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR lr.id LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(lr.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(lr.created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($minAmount)) {
    $whereConditions[] = "lr.amount >= ?";
    $params[] = floatval($minAmount);
}

if (!empty($maxAmount)) {
    $whereConditions[] = "lr.amount <= ?";
    $params[] = floatval($maxAmount);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$allowedSorts = ['created_at', 'amount', 'duration', 'status', 'first_name', 'last_name'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'created_at';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

$sortColumn = $sortBy === 'first_name' || $sortBy === 'last_name' ? "u.$sortBy" : "lr.$sortBy";

$loans = $db->fetchAll("
    SELECT lr.*, u.first_name, u.last_name, u.email, u.phone,
           CASE WHEN lr.status = 'pending' THEN 1
                WHEN lr.status = 'under_review' THEN 2
                WHEN lr.status = 'approved' THEN 3
                WHEN lr.status = 'rejected' THEN 4
                ELSE 5 END as status_priority
    FROM loan_requests lr 
    JOIN users u ON lr.user_id = u.id 
    $whereClause 
    ORDER BY $sortColumn $sortOrder 
    LIMIT ? OFFSET ?
", array_merge($params, [$limit, $offset]));

$totalLoans = $db->count("
    SELECT COUNT(*) 
    FROM loan_requests lr 
    JOIN users u ON lr.user_id = u.id 
    $whereClause
", $params);

$totalPages = ceil($totalLoans / $limit);

$loanStats = $loanRequest->getLoanRequestStats();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Demandes de prêt</h1>
        <p class="page-subtitle">Gestion et traitement des demandes de financement</p>
        <div class="page-actions">
            <div class="export-dropdown">
                <button class="export-btn">
                    📊 Exporter
                    <span>▼</span>
                </button>
                <div class="export-dropdown-content">
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="loans" data-format="csv">CSV</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="loans" data-format="excel">Excel</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="loans" data-format="pdf">PDF</a>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Actualiser
            </button>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(6, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total</div>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-value"><?php echo number_format($loanStats['total']); ?></div>
            <div class="stat-change positive">Demandes</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">En attente</div>
                <div class="stat-icon" style="background: var(--warning-color);">⏳</div>
            </div>
            <div class="stat-value"><?php echo number_format($loanStats['pending']); ?></div>
            <div class="stat-change <?php echo $loanStats['pending'] > 0 ? 'priority' : 'positive'; ?>">À traiter</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">En cours</div>
                <div class="stat-icon" style="background: var(--info-color);">🔄</div>
            </div>
            <div class="stat-value"><?php echo number_format($loanStats['under_review']); ?></div>
            <div class="stat-change positive">Examens</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Approuvées</div>
                <div class="stat-icon" style="background: var(--success-color);">✅</div>
            </div>
            <div class="stat-value"><?php echo number_format($loanStats['approved']); ?></div>
            <div class="stat-change positive"><?php echo round(($loanStats['approved'] / max($loanStats['total'], 1)) * 100, 1); ?>%</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Rejetées</div>
                <div class="stat-icon" style="background: var(--error-color);">❌</div>
            </div>
            <div class="stat-value"><?php echo number_format($loanStats['rejected']); ?></div>
            <div class="stat-change negative"><?php echo round(($loanStats['rejected'] / max($loanStats['total'], 1)) * 100, 1); ?>%</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Montant total</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">💎</div>
            </div>
            <div class="stat-value"><?php echo formatCurrency($loanStats['total_approved_amount']); ?></div>
            <div class="stat-change positive">Approuvé</div>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <select name="status" class="filter-select">
                    <option value="">Tous</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="under_review" <?php echo $status === 'under_review' ? 'selected' : ''; ?>>En cours</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                    <option value="disbursed" <?php echo $status === 'disbursed' ? 'selected' : ''; ?>>Déboursé</option>
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

            <div class="filter-group">
                <label class="filter-label">Montant min:</label>
                <input type="number" name="min_amount" value="<?php echo htmlspecialchars($minAmount); ?>" class="filter-input" placeholder="€">
            </div>

            <div class="filter-group">
                <label class="filter-label">Montant max:</label>
                <input type="number" name="max_amount" value="<?php echo htmlspecialchars($maxAmount); ?>" class="filter-input" placeholder="€">
            </div>

            <div class="filter-group" style="flex: 1;">
                <input type="text" name="search" placeholder="Rechercher par nom, email, ID..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="filter-search">
            </div>

            <button type="submit" class="btn btn-primary">Filtrer</button>
            <?php if (!empty($status) || !empty($search) || !empty($dateFrom) || !empty($dateTo) || !empty($minAmount) || !empty($maxAmount)): ?>
                <a href="list.php" class="btn btn-secondary">Réinitialiser</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="bulk-actions" id="bulkActions">
        <span class="bulk-actions-text">
            <span class="selected-count">0</span> demande(s) sélectionnée(s)
        </span>
        <div class="bulk-actions-buttons">
            <button class="btn btn-sm btn-info" data-action="bulk-action" data-bulk-action="review">
                Mettre en cours
            </button>
            <button class="btn btn-sm btn-success" data-action="bulk-action" data-bulk-action="approve">
                Approuver
            </button>
            <button class="btn btn-sm btn-error" data-action="bulk-action" data-bulk-action="reject" 
                    data-confirm="Êtes-vous sûr de vouloir rejeter les demandes sélectionnées ?">
                Rejeter
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Liste des demandes 
                <span class="badge badge-info"><?php echo number_format($totalLoans); ?></span>
            </h3>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 0.875rem; color: #6B7280;">
                    Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                </span>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.875rem;">Trier par:</label>
                    <select onchange="changeSorting(this.value)" style="padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid #E5E7EB;">
                        <option value="created_at-DESC" <?php echo ($sortBy === 'created_at' && $sortOrder === 'DESC') ? 'selected' : ''; ?>>Plus récentes</option>
                        <option value="created_at-ASC" <?php echo ($sortBy === 'created_at' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Plus anciennes</option>
                        <option value="amount-DESC" <?php echo ($sortBy === 'amount' && $sortOrder === 'DESC') ? 'selected' : ''; ?>>Montant élevé</option>
                        <option value="amount-ASC" <?php echo ($sortBy === 'amount' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Montant faible</option>
                        <option value="status-ASC" <?php echo ($sortBy === 'status' && $sortOrder === 'ASC') ? 'selected' : ''; ?>>Par statut</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body" style="padding: 0;">
            <?php if (empty($loans)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💰</div>
                    <h3 class="empty-state-title">Aucune demande trouvée</h3>
                    <p class="empty-state-description">
                        <?php if (!empty($search) || !empty($status)): ?>
                            Aucune demande ne correspond aux critères de recherche.
                        <?php else: ?>
                            Il n'y a encore aucune demande de prêt.
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
                            <th>Demandeur</th>
                            <th>Montant</th>
                            <th>Durée</th>
                            <th>Objectif</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr class="<?php echo getPriorityClass($loan['status']); ?>">
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $loan['id']; ?>">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, var(--primary-color), var(--accent-1)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                                            <?php echo strtoupper(substr($loan['first_name'], 0, 1) . substr($loan['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: var(--accent-2);">
                                                <?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6B7280;">
                                                ID: <?php echo $loan['id']; ?> • <?php echo htmlspecialchars($loan['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo formatCurrency($loan['amount']); ?>
                                    </div>
                                    <?php if ($loan['approved_amount'] && $loan['status'] === 'approved'): ?>
                                        <div style="font-size: 0.75rem; color: var(--success-color);">
                                            Approuvé: <?php echo formatCurrency($loan['approved_amount']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 500;"><?php echo $loan['duration']; ?> mois</span>
                                </td>
                                <td>
                                    <div style="max-width: 200px;">
                                        <?php echo htmlspecialchars(truncateText($loan['purpose'], 80)); ?>
                                    </div>
                                </td>
                                <td data-status="<?php echo $loan['status']; ?>">
                                    <?php echo getStatusBadge($loan['status']); ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php echo formatDateTime($loan['created_at']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="view.php?id=<?php echo $loan['id']; ?>" class="action-btn view" title="Voir détails">
                                            👁️
                                        </a>
                                        
                                        <?php if ($loan['status'] === 'pending'): ?>
                                            <button class="action-btn edit" 
                                                    data-action="update-loan-status" 
                                                    data-id="<?php echo $loan['id']; ?>" 
                                                    data-status="under_review"
                                                    title="Mettre en cours">🔄</button>

                                            <button class="action-btn view" 
                                                data-action="approve-loan" 
                                                data-id="<?php echo $loan['id']; ?>" 
                                                data-modal-id="quickApproveModal" 
                                                data-target-input="approveLoanId" title="Approuver">✅
                                            </button>

                                            <button class="action-btn delete" 
                                                data-action="reject-loan" 
                                                data-id="<?php echo $loan['id']; ?>" 
                                                data-modal-id="quickRejectModal" 
                                                data-target-input="rejectLoanId" title="Rejeter">❌
                                            </button>
                                        <?php elseif ($loan['status'] === 'under_review'): ?>
                                            <button class="action-btn view" 
                                                data-action="approve-loan" 
                                                data-id="<?php echo $loan['id']; ?>" 
                                                data-modal-id="quickApproveModal" 
                                                data-target-input="approveLoanId" title="Approuver">✅
                                            </button>
                                            <button class="action-btn delete" 
                                                data-action="reject-loan" 
                                                data-id="<?php echo $loan['id']; ?>" 
                                                data-modal-id="quickRejectModal" 
                                                data-target-input="rejectLoanId" title="Rejeter">❌
                                            </button>
                                        <?php elseif ($loan['status'] === 'approved'): ?>
                                            <button class="action-btn edit" 
                                                    data-action="update-loan-status" 
                                                    data-id="<?php echo $loan['id']; ?>" 
                                                    data-status="disbursed"
                                                    title="Marquer comme déboursé">💸</button>
                                        <?php endif; ?>
                                        
                                        <a href="../users/view.php?id=<?php echo $loan['user_id']; ?>" class="action-btn edit" title="Voir utilisateur">
                                            👤
                                        </a>
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
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.875rem; color: #6B7280;">
                        Affichage de <?php echo number_format($offset + 1); ?> à <?php echo number_format(min($offset + $limit, $totalLoans)); ?> 
                        sur <?php echo number_format($totalLoans); ?> demandes
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
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

<div id="quickApproveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Approuver la demande de prêt</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quickApproveForm" method="POST" action="/deboutatoutprix/ajax/loan-actions.php" data-ajax="true">
                <input type="hidden" id="approveLoanId" name="loan_id">
                
                <div class="form-group">
                    <label for="approvedAmount" class="form-label">Montant approuvé (€) *</label>
                    <input type="number" id="approvedAmount" name="approved_amount" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="partnerBank" class="form-label">Banque partenaire</label>
                    <select id="partnerBank" name="partner_bank" class="form-control">
                        <option value="PrestaCapi">PrestaCapi</option>
                        <option value="Crédit Agricole">Crédit Agricole</option>
                        <option value="BNP Paribas">BNP Paribas</option>
                        <option value="Société Générale">Société Générale</option>
                        <option value="CIC">CIC</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="approvalNotes" class="form-label">Notes (optionnel)</label>
                    <textarea id="approvalNotes" name="notes" class="form-control" rows="3" placeholder="Commentaires sur l'approbation..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="quickApproveForm" class="btn btn-success">✅ Approuver</button>
        </div>
    </div>
</div>

<div id="quickRejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rejeter la demande de prêt</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quickRejectForm" method="POST" action="/deboutatoutprix/ajax/loan-actions.php" data-ajax="true">
                <input type="hidden" id="rejectLoanId" name="loan_id">
                
                <div class="form-group">
                    <label for="rejectionReason" class="form-label">Motif du refus *</label>
                    <select id="rejectionReason" name="rejection_reason" class="form-control" required>
                        <option value="">Sélectionner un motif</option>
                        <option value="Revenus insuffisants">Revenus insuffisants</option>
                        <option value="Trop d'endettement">Trop d'endettement</option>
                        <option value="Documents manquants">Documents manquants</option>
                        <option value="Historique de crédit">Historique de crédit</option>
                        <option value="Critères non respectés">Critères non respectés</option>
                        <option value="Autre">Autre motif</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="rejectionNotes" class="form-label">Explication détaillée *</label>
                    <textarea id="rejectionNotes" name="notes" class="form-control" rows="4" required placeholder="Expliquez les raisons du refus..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="quickRejectForm" class="btn btn-error">❌ Rejeter</button>
        </div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>