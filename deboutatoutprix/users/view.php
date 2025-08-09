<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_users');

$pageTitle = 'Détail utilisateur';

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: list.php');
    exit;
}

$db = Database::getInstance();

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
if (!$user) {
    header('Location: list.php');
    exit;
}

$user_obj = new User();
$loanRequest_obj = new LoanRequest();
$withdrawal_obj = new Withdrawal();
$document_obj = new Document();

$userLoanRequests = $loanRequest_obj->getUserLoanRequests($userId);
$userWithdrawals = $withdrawal_obj->getUserWithdrawals($userId);
$userDocuments = $document_obj->getUserDocuments($userId, false);
$userNotifications = $db->getUserNotifications($userId, 10);

$dashboardStats = $user_obj->getDashboardStats($userId);

$totalApproved = array_sum(array_column(array_filter($userLoanRequests, function($loan) {
    return $loan['status'] === 'approved';
}), 'approved_amount'));

$totalWithdrawn = array_sum(array_column(array_filter($userWithdrawals, function($w) {
    return $w['status'] === 'processed';
}), 'amount'));

$userStats = [
    'total_loans' => count($userLoanRequests),
    'approved_loans' => count(array_filter($userLoanRequests, function($loan) {
        return $loan['status'] === 'approved';
    })),
    'total_approved_amount' => $totalApproved,
    'total_withdrawals' => count($userWithdrawals),
    'total_withdrawn' => $totalWithdrawn,
    'current_balance' => $user['balance'],
    'documents_count' => count($userDocuments),
    'verified_documents' => count(array_filter($userDocuments, function($doc) {
        return $doc['is_verified'] == 1;
    }))
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="list.php" class="btn btn-secondary">← Retour</a>
            <div>
                <h1 class="page-title">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                </h1>
                <p class="page-subtitle">
                    Utilisateur ID: <?php echo $user['id']; ?> • 
                    Inscrit le <?php echo formatDateTime($user['created_at']); ?>
                </p>
            </div>
        </div>
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="window.print()">
                🖨️ Imprimer
            </button>
            <button class="btn btn-warning" 
                    data-action="toggle-user-status" 
                    data-id="<?php echo $user['id']; ?>" 
                    data-current-status="<?php echo $user['status']; ?>">
                <?php echo $user['status'] === 'active' ? '🚫 Suspendre' : '✅ Activer'; ?>
            </button>
            <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                ✏️ Modifier
            </a>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Solde actuel</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">💰</div>
            </div>
            <div class="stat-value"><?php echo formatCurrency($userStats['current_balance']); ?></div>
            <div class="stat-change positive">Disponible</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Prêts approuvés</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #42A5F5);">📊</div>
            </div>
            <div class="stat-value"><?php echo $userStats['approved_loans']; ?></div>
            <div class="stat-change positive">sur <?php echo $userStats['total_loans']; ?> demandes</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Montant total</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #FFB74D);">💎</div>
            </div>
            <div class="stat-value"><?php echo formatCurrency($userStats['total_approved_amount']); ?></div>
            <div class="stat-change positive">Approuvé</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Documents</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #9C27B0, #BA68C8);">📄</div>
            </div>
            <div class="stat-value"><?php echo $userStats['verified_documents']; ?></div>
            <div class="stat-change positive">sur <?php echo $userStats['documents_count']; ?> uploadés</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informations personnelles</h3>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <?php echo getStatusBadge($user['status']); ?>
                    <?php if ($user['email_verified']): ?>
                        <span class="badge badge-success">✅ Email vérifié</span>
                    <?php else: ?>
                        <span class="badge badge-warning">⚠️ Email non vérifié</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 4rem; height: 4rem; background: linear-gradient(135deg, var(--primary-color), var(--accent-1)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem;">
                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h4 style="margin: 0; font-size: 1.25rem; color: var(--primary-color);">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h4>
                            <p style="margin: 0; color: #6B7280; font-size: 0.875rem;">
                                Membre depuis le <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                        <div>
                            <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Email</label>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Téléphone</label>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user['phone'] ?: 'Non renseigné'); ?></div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">WhatsApp</label>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($user['whatsapp'] ?: 'Non renseigné'); ?></div>
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Date de naissance</label>
                            <div style="font-weight: 500;">
                                <?php 
                                if ($user['date_of_birth']) {
                                    $birthDate = new DateTime($user['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $today->diff($birthDate)->y;
                                    echo date('d/m/Y', strtotime($user['date_of_birth'])) . " ($age ans)";
                                } else {
                                    echo 'Non renseignée';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['address']): ?>
                    <div style="padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                        <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Adresse</label>
                        <div style="font-weight: 500; line-height: 1.5;">
                            <?php echo htmlspecialchars($user['address']); ?><br>
                            <?php if ($user['postal_code'] || $user['city']): ?>
                                <?php echo htmlspecialchars($user['postal_code'] . ' ' . $user['city']); ?><br>
                            <?php endif; ?>
                            <?php if ($user['country']): ?>
                                <?php echo htmlspecialchars($user['country']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Dernière connexion</label>
                                <div style="font-weight: 500;">
                                    <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Jamais'; ?>
                                </div>
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Mis à jour</label>
                                <div style="font-weight: 500;"><?php echo formatDateTime($user['updated_at']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Activité récente</h3>
                <a href="../logs/activity.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Voir tout</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $userActivity = $db->fetchAll("
                        SELECT * FROM activity_logs 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 8
                    ", [$userId]);

                    if (empty($userActivity)):
                    ?>
                        <div style="padding: 2rem; text-align: center; color: #6B7280;">
                            Aucune activité récente
                        </div>
                    <?php else: ?>
                        <?php foreach ($userActivity as $activity): ?>
                            <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; align-items: flex-start; gap: 0.75rem;">
                                <div style="width: 2rem; height: 2rem; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0; margin-top: 0.125rem;">
                                    <?php
                                    $icon = '📋';
                                    if (strpos($activity['action'], 'login') !== false) $icon = '🔑';
                                    elseif (strpos($activity['action'], 'loan') !== false) $icon = '💰';
                                    elseif (strpos($activity['action'], 'withdrawal') !== false) $icon = '💸';
                                    elseif (strpos($activity['action'], 'document') !== false) $icon = '📄';
                                    echo $icon;
                                    ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 500; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($activity['description'] ?: $activity['action']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        <?php echo formatDateTime($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Demandes de prêt 
                    <span class="badge badge-info"><?php echo count($userLoanRequests); ?></span>
                </h3>
                <a href="../loans/list.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Voir tout</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($userLoanRequests)): ?>
                    <div style="padding: 2rem; text-align: center; color: #6B7280;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">💰</div>
                        <h4>Aucune demande de prêt</h4>
                        <p>Cet utilisateur n'a pas encore fait de demande de prêt.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Montant</th>
                                <th>Durée</th>
                                <th>Statut</th>
                                <th>Créée le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($userLoanRequests, 0, 5) as $loan): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight: 600; color: var(--primary-color);">#<?php echo $loan['id']; ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;">
                                            <?php echo formatCurrency($loan['amount']); ?>
                                        </div>
                                        <?php if ($loan['approved_amount'] && $loan['status'] === 'approved'): ?>
                                            <div style="font-size: 0.75rem; color: var(--success-color);">
                                                Approuvé: <?php echo formatCurrency($loan['approved_amount']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $loan['duration']; ?> mois</td>
                                    <td><?php echo getStatusBadge($loan['status']); ?></td>
                                    <td>
                                        <div><?php echo date('d/m/Y', strtotime($loan['created_at'])); ?></div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo $lang->getTimeAgo($loan['created_at']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="../loans/view.php?id=<?php echo $loan['id']; ?>" class="action-btn view">👁️</a>
                                        <?php if ($loan['status'] === 'pending'): ?>
                                            <button class="action-btn edit" 
                                                    data-action="approve-loan" 
                                                    data-id="<?php echo $loan['id']; ?>"
                                                    title="Approuver">✅</button>
                                            <button class="action-btn delete" 
                                                    data-action="reject-loan" 
                                                    data-id="<?php echo $loan['id']; ?>"
                                                    title="Rejeter">❌</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($userLoanRequests) > 5): ?>
                        <div style="padding: 1rem; text-align: center; border-top: 1px solid #E5E7EB;">
                            <a href="../loans/list.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">
                                Voir les <?php echo count($userLoanRequests) - 5; ?> autres demandes
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Retraits 
                        <span class="badge badge-info"><?php echo count($userWithdrawals); ?></span>
                    </h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($userWithdrawals)): ?>
                        <div style="padding: 2rem; text-align: center; color: #6B7280;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">💸</div>
                            <p>Aucun retrait</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach (array_slice($userWithdrawals, 0, 5) as $withdrawal): ?>
                                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600;"><?php echo formatCurrency($withdrawal['amount']); ?></div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo htmlspecialchars($withdrawal['bank_name']); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo date('d/m/Y', strtotime($withdrawal['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php echo getStatusBadge($withdrawal['status']); ?>
                                        <div style="margin-top: 0.5rem;">
                                            <a href="../withdrawals/view.php?id=<?php echo $withdrawal['id']; ?>" class="action-btn view">👁️</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Documents 
                        <span class="badge badge-info"><?php echo count($userDocuments); ?></span>
                    </h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($userDocuments)): ?>
                        <div style="padding: 2rem; text-align: center; color: #6B7280;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">📄</div>
                            <p>Aucun document</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach (array_slice($userDocuments, 0, 5) as $doc): ?>
                                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500;">
                                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo date('d/m/Y', strtotime($doc['uploaded_at'])); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php if ($doc['is_verified']): ?>
                                            <span class="badge badge-success">✅ Vérifié</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⏳ En attente</span>
                                        <?php endif; ?>
                                        <div style="margin-top: 0.5rem;">
                                            <a href="../documents/view.php?id=<?php echo $doc['id']; ?>" class="action-btn view">👁️</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('[data-action]');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const id = this.dataset.id;
            
            if (action === 'toggle-user-status') {
                const currentStatus = this.dataset.currentStatus;
                const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
                const actionText = newStatus === 'active' ? 'activer' : 'suspendre';
                
                if (confirm(`Voulez-vous ${actionText} cet utilisateur ?`)) {
                    toggleUserStatus(id, newStatus);
                }
            }
        });
    });
});

async function toggleUserStatus(userId, newStatus) {
    try {
        showLoading();
        const response = await fetch('../ajax/user-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_status',
                user_id: userId,
                status: newStatus
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast(`Utilisateur ${newStatus === 'active' ? 'activé' : 'suspendu'}`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message || 'Erreur lors de la modification', 'error');
        }
    } catch (error) {
        showToast('Erreur de connexion', 'error');
        console.error('Erreur:', error);
    } finally {
        hideLoading();
    }
}
</script>

<?php include '../includes/footer.php'; ?>