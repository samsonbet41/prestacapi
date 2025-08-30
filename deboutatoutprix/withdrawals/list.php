<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/Withdrawal.php';
requirePermission('manage_withdrawals');

$pageTitle = 'Demandes de retrait';

$withdrawal = new Withdrawal();
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
    $whereConditions[] = "w.status = ?";
    $params[] = $status;
}

if (!empty($userId)) {
    $whereConditions[] = "w.user_id = ?";
    $params[] = $userId;
}

if (!empty($search)) {
    $whereConditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR w.id LIKE ? OR w.bank_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(w.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(w.created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($minAmount)) {
    $whereConditions[] = "w.amount >= ?";
    $params[] = floatval($minAmount);
}

if (!empty($maxAmount)) {
    $whereConditions[] = "w.amount <= ?";
    $params[] = floatval($maxAmount);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$allowedSorts = ['created_at', 'amount', 'status', 'first_name', 'last_name', 'processed_at'];
if (!in_array($sortBy, $allowedSorts)) {
    $sortBy = 'created_at';
}

$allowedOrders = ['ASC', 'DESC'];
if (!in_array($sortOrder, $allowedOrders)) {
    $sortOrder = 'DESC';
}

$sortColumn = $sortBy === 'first_name' || $sortBy === 'last_name' ? "u.$sortBy" : "w.$sortBy";

$withdrawals = $db->fetchAll("
    SELECT w.*, u.first_name, u.last_name, u.email, u.phone, u.balance,
           lr.approved_amount, lr.partner_bank,
           CASE WHEN w.status = 'pending' THEN 1
                WHEN w.status = 'approved' THEN 2
                WHEN w.status = 'processed' THEN 3
                WHEN w.status = 'rejected' THEN 4
                ELSE 5 END as status_priority
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    JOIN loan_requests lr ON w.loan_request_id = lr.id
    $whereClause 
    ORDER BY $sortColumn $sortOrder 
    LIMIT ? OFFSET ?
", array_merge($params, [$limit, $offset]));

$totalWithdrawals = $db->count("
    SELECT COUNT(*) 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    JOIN loan_requests lr ON w.loan_request_id = lr.id
    $whereClause
", $params);

$totalPages = ceil($totalWithdrawals / $limit);

$withdrawalStats = $withdrawal->getWithdrawalStats();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Demandes de retrait</h1>
        <p class="page-subtitle">Gestion et traitement des virements</p>
        <div class="page-actions">
            <div class="export-dropdown">
                <button class="export-btn">
                    📊 Exporter
                    <span>▼</span>
                </button>
                <div class="export-dropdown-content">
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="withdrawals" data-format="csv">CSV</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="withdrawals" data-format="excel">Excel</a>
                    <a href="#" class="export-dropdown-item" data-action="export-data" data-type="withdrawals" data-format="pdf">PDF</a>
                </div>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Actualiser
            </button>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Total</div>
                <div class="stat-icon">💸</div>
            </div>
            <div class="stat-value"><?php echo number_format($withdrawalStats['total']); ?></div>
            <div class="stat-change positive">Demandes</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">En attente</div>
                <div class="stat-icon" style="background: var(--warning-color);">⏳</div>
            </div>
            <div class="stat-value"><?php echo number_format($withdrawalStats['pending']); ?></div>
            <div class="stat-change <?php echo $withdrawalStats['pending'] > 0 ? 'priority' : 'positive'; ?>">À traiter</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Approuvées</div>
                <div class="stat-icon" style="background: var(--info-color);">✅</div>
            </div>
            <div class="stat-value"><?php echo number_format($withdrawalStats['approved']); ?></div>
            <div class="stat-change positive">En cours</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Traitées</div>
                <div class="stat-icon" style="background: var(--success-color);">💰</div>
            </div>
            <div class="stat-value"><?php echo number_format($withdrawalStats['processed']); ?></div>
            <div class="stat-change positive">Finalisées</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Montant versé</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">💎</div>
            </div>
            <div class="stat-value"><?php echo formatCurrency($withdrawalStats['total_processed_amount']); ?></div>
            <div class="stat-change positive">Total</div>
        </div>
    </div>

    <div class="filters-bar">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <select name="status" class="filter-select">
                    <option value="">Tous</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                    <option value="processed" <?php echo $status === 'processed' ? 'selected' : ''; ?>>Traité</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
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
                <input type="text" name="search" placeholder="Rechercher par nom, email, banque..." 
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
            <button class="btn btn-sm btn-success" data-action="bulk-action" data-bulk-action="approve">
                Approuver
            </button>
            <button class="btn btn-sm btn-info" data-action="bulk-action" data-bulk-action="process">
                Traiter
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
                Liste des retraits 
                <span class="badge badge-info"><?php echo number_format($totalWithdrawals); ?></span>
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
            <?php if (empty($withdrawals)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">💸</div>
                    <h3 class="empty-state-title">Aucune demande trouvée</h3>
                    <p class="empty-state-description">
                        <?php if (!empty($search) || !empty($status)): ?>
                            Aucune demande ne correspond aux critères de recherche.
                        <?php else: ?>
                            Il n'y a encore aucune demande de retrait.
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
                            <th>Banque</th>
                            <th>Coordonnées bancaires</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr class="<?php echo getPriorityClass($w['status']); ?>">
                                <td>
                                    <input type="checkbox" name="selected[]" value="<?php echo $w['id']; ?>">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="width: 2.5rem; height: 2.5rem; background: linear-gradient(135deg, var(--primary-color), var(--accent-1)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                                            <?php echo strtoupper(substr($w['first_name'], 0, 1) . substr($w['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 500; color: var(--accent-2);">
                                                <?php echo htmlspecialchars($w['first_name'] . ' ' . $w['last_name']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6B7280;">
                                                ID: <?php echo $w['id']; ?> • <?php echo htmlspecialchars($w['email']); ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #6B7280;">
                                                Solde: <?php echo formatCurrency($w['balance']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary-color);">
                                        <?php echo formatCurrency($w['amount']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        Prêt: <?php echo formatCurrency($w['approved_amount']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($w['bank_name']); ?>
                                    </div>
                                    <?php if ($w['partner_bank']): ?>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            via <?php echo htmlspecialchars($w['partner_bank']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($w['account_holder_name']); ?></div>
                                        <div style="font-family: monospace; font-size: 0.8rem; color: #6B7280;">
                                            <?php echo htmlspecialchars($w['account_number']); ?>
                                        </div>
                                        <?php if ($w['iban']): ?>
                                            <div style="font-family: monospace; font-size: 0.75rem; color: #6B7280;">
                                                IBAN: <?php echo htmlspecialchars(substr($w['iban'], 0, 10) . '...'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-status="<?php echo $w['status']; ?>">
                                    <?php echo getStatusBadge($w['status']); ?>
                                    <?php if ($w['transaction_reference']): ?>
                                        <div style="font-size: 0.75rem; color: #6B7280; margin-top: 0.25rem;">
                                            Réf: <?php echo htmlspecialchars($w['transaction_reference']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size: 0.875rem;">
                                        <?php echo formatDateTime($w['created_at']); ?>
                                    </div>
                                    <?php if ($w['processed_at']): ?>
                                        <div style="font-size: 0.75rem; color: var(--success-color);">
                                            Traité: <?php echo formatDateTime($w['processed_at']); ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo $lang->getTimeAgo($w['created_at']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="view.php?id=<?php echo $w['id']; ?>" class="action-btn view" title="Voir détails">
                                            👁️
                                        </a>
                                        
                                        <?php if ($w['status'] === 'pending'): ?>
                                            <button class="action-btn view" 
                                                    data-action="quick-approve-withdrawal" 
                                                    data-id="<?php echo $w['id']; ?>"
                                                    title="Approuver">✅</button>
                                            <button class="action-btn delete" 
                                                    data-action="quick-reject-withdrawal" 
                                                    data-id="<?php echo $w['id']; ?>"
                                                    title="Rejeter">❌</button>
                                        <?php elseif ($w['status'] === 'approved'): ?>
                                            <button class="action-btn edit" 
                                                    data-action="quick-process-withdrawal" 
                                                    data-id="<?php echo $w['id']; ?>"
                                                    title="Traiter le virement">💰</button>
                                        <?php endif; ?>
                                        
                                        <a href="../users/view.php?id=<?php echo $w['user_id']; ?>" class="action-btn edit" title="Voir utilisateur">
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
                        Affichage de <?php echo number_format($offset + 1); ?> à <?php echo number_format(min($offset + $limit, $totalWithdrawals)); ?> 
                        sur <?php echo number_format($totalWithdrawals); ?> demandes
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

<div id="approveWithdrawalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Approuver la demande de retrait</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="approveWithdrawalForm">
                <input type="hidden" id="approveWithdrawalId" name="withdrawal_id">
                
                <div class="form-group">
                    <label for="approvalNotes" class="form-label">Notes d'approbation</label>
                    <textarea id="approvalNotes" name="notes" class="form-control" rows="3" 
                              placeholder="Commentaires sur l'approbation du virement..."></textarea>
                </div>
                
                <div style="background: #E8F4FD; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">⚠️ Vérifications effectuées :</div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="checkbox" required>
                        <span>Coordonnées bancaires vérifiées</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="checkbox" required>
                        <span>Identité du bénéficiaire confirmée</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" required>
                        <span>Solde suffisant vérifié</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="approveWithdrawalForm" class="btn btn-success">✅ Approuver</button>
        </div>
    </div>
</div>

<div id="processWithdrawalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Traiter le virement</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="processWithdrawalForm">
                <input type="hidden" id="processWithdrawalId" name="withdrawal_id">
                
                <div class="form-group">
                    <label for="transactionRef" class="form-label">Référence de transaction</label>
                    <input type="text" id="transactionRef" name="transaction_reference" class="form-control" 
                           placeholder="Numéro de référence du virement bancaire">
                </div>
                
                <div class="form-group">
                    <label for="processNotes" class="form-label">Notes de traitement</label>
                    <textarea id="processNotes" name="notes" class="form-control" rows="3" 
                              placeholder="Détails sur le traitement du virement..."></textarea>
                </div>
                
                <div style="background: #D4F4DD; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">✅ Virement effectué :</div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="checkbox" required>
                        <span>Le virement a été effectué avec succès</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" required>
                        <span>Le solde utilisateur a été débité</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="processWithdrawalForm" class="btn btn-success">💰 Marquer comme traité</button>
        </div>
    </div>
</div>

<div id="rejectWithdrawalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rejeter la demande de retrait</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="rejectWithdrawalForm">
                <input type="hidden" id="rejectWithdrawalId" name="withdrawal_id">
                
                <div class="form-group">
                    <label for="rejectionReason" class="form-label">Motif du refus *</label>
                    <select id="rejectionReason" name="rejection_reason" class="form-control" required>
                        <option value="">Sélectionner un motif</option>
                        <option value="Coordonnées bancaires incorrectes">Coordonnées bancaires incorrectes</option>
                        <option value="Identité non vérifiée">Identité non vérifiée</option>
                        <option value="Solde insuffisant">Solde insuffisant</option>
                        <option value="Documents manquants">Documents manquants</option>
                        <option value="Compte bancaire invalide">Compte bancaire invalide</option>
                        <option value="Fraude suspectée">Fraude suspectée</option>
                        <option value="Autre motif">Autre motif</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="rejectionNotes" class="form-label">Explication détaillée *</label>
                    <textarea id="rejectionNotes" name="notes" class="form-control" rows="4" required 
                              placeholder="Expliquez les raisons du refus. Cette information sera communiquée au demandeur."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="rejectWithdrawalForm" class="btn btn-error">❌ Rejeter</button>
        </div>
    </div>
</div>

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

    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const id = this.dataset.id;
            
            if (action === 'quick-approve-withdrawal') {
                openApproveWithdrawalModal(id);
            } else if (action === 'quick-process-withdrawal') {
                openProcessWithdrawalModal(id);
            } else if (action === 'quick-reject-withdrawal') {
                openRejectWithdrawalModal(id);
            }
        });
    });

    document.getElementById('approveWithdrawalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('approve');
    });

    document.getElementById('processWithdrawalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('process');
    });

    document.getElementById('rejectWithdrawalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('reject');
    });
});

function openApproveWithdrawalModal(withdrawalId) {
    document.getElementById('approveWithdrawalId').value = withdrawalId;
    document.getElementById('approveWithdrawalModal').style.display = 'block';
}

function openProcessWithdrawalModal(withdrawalId) {
    document.getElementById('processWithdrawalId').value = withdrawalId;
    document.getElementById('processWithdrawalModal').style.display = 'block';
}

function openRejectWithdrawalModal(withdrawalId) {
    document.getElementById('rejectWithdrawalId').value = withdrawalId;
    document.getElementById('rejectWithdrawalModal').style.display = 'block';
}

async function submitWithdrawalAction(action) {
    let formId, modalId;
    
    switch (action) {
        case 'approve':
            formId = 'approveWithdrawalForm';
            modalId = 'approveWithdrawalModal';
            break;
        case 'process':
            formId = 'processWithdrawalForm';
            modalId = 'processWithdrawalModal';
            break;
        case 'reject':
            formId = 'rejectWithdrawalForm';
            modalId = 'rejectWithdrawalModal';
            break;
    }
    
    const formData = new FormData(document.getElementById(formId));
    formData.append('action', action);
    
    try {
        showLoading();
        const response = await fetch('../ajax/withdrawal-actions.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            const messages = {
                approve: 'Demande approuvée avec succès',
                process: 'Virement traité avec succès',
                reject: 'Demande rejetée'
            };
            
            showToast(messages[action], 'success');
            document.getElementById(modalId).style.display = 'none';
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(data.message || 'Erreur lors de l\'action', 'error');
        }
    } catch (error) {
        showToast('Erreur de connexion', 'error');
    } finally {
        hideLoading();
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal') || e.target.classList.contains('modal-close') || e.target.dataset.dismiss === 'modal') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>