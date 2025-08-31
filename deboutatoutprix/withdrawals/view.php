<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/Withdrawal.php';
requirePermission('manage_withdrawals');

$pageTitle = 'Détail demande de retrait';

$withdrawalId = intval($_GET['id'] ?? 0);
if ($withdrawalId <= 0) {
    header('Location: list.php');
    exit;
}

$withdrawal = new Withdrawal();
$withdrawalData = $withdrawal->getWithdrawalById($withdrawalId);

if (!$withdrawalData) {
    header('Location: list.php');
    exit;
}

$db = Database::getInstance();

$canWithdraw = $withdrawal->canUserRequestWithdrawal($withdrawalData['user_id']);
$withdrawableAmount = $withdrawal->getWithdrawableAmount($withdrawalData['user_id']);

$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$withdrawalData['user_id']]);
$loanRequest = $db->fetchOne("SELECT * FROM loan_requests WHERE id = ?", [$withdrawalData['loan_request_id']]);

$userDocuments = $db->fetchAll("
    SELECT * FROM documents 
    WHERE user_id = ? AND document_type IN ('identity', 'bank_statement')
    ORDER BY document_type, uploaded_at DESC
", [$withdrawalData['user_id']]);

$withdrawalHistory = $db->fetchAll("
    SELECT w.*, lr.partner_bank
    FROM withdrawals w 
    JOIN loan_requests lr ON w.loan_request_id = lr.id
    WHERE w.user_id = ? 
    ORDER BY w.created_at DESC
", [$withdrawalData['user_id']]);

$userActivity = $db->fetchAll("
    SELECT * FROM activity_logs 
    WHERE user_id = ? AND action LIKE '%withdrawal%'
    ORDER BY created_at DESC 
    LIMIT 10
", [$withdrawalData['user_id']]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="list.php" class="btn btn-secondary">← Retour</a>
            <div>
                <h1 class="page-title">
                    Demande de retrait #<?php echo $withdrawalData['id']; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo htmlspecialchars($withdrawalData['first_name'] . ' ' . $withdrawalData['last_name']); ?> • 
                    <?php echo formatDateTime($withdrawalData['created_at']); ?>
                </p>
            </div>
        </div>
        <div class="page-actions">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                <?php echo getStatusBadge($withdrawalData['status']); ?>
            </div>
            
            <?php if ($withdrawalData['status'] === 'pending'): ?>
                <button class="btn btn-success" data-action="approve-withdrawal" data-id="<?php echo $withdrawalData['id']; ?>">
                    ✅ Approuver
                </button>
                <button class="btn btn-error" data-action="reject-withdrawal" data-id="<?php echo $withdrawalData['id']; ?>">
                    ❌ Rejeter
                </button>
            <?php elseif ($withdrawalData['status'] === 'approved'): ?>
                <button class="btn btn-info" data-action="process-withdrawal" data-id="<?php echo $withdrawalData['id']; ?>">
                    💰 Traiter le virement
                </button>
            <?php endif; ?>
            
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle">⚙️ Plus d'actions</button>
                <div class="dropdown-menu">
                    <a href="../users/view.php?id=<?php echo $withdrawalData['user_id']; ?>" class="dropdown-item">👤 Voir l'utilisateur</a>
                    <a href="../loans/view.php?id=<?php echo $withdrawalData['loan_request_id']; ?>" class="dropdown-item">💰 Voir le prêt</a>
                    <a href="#" class="dropdown-item" data-action="export-withdrawal" data-id="<?php echo $withdrawalData['id']; ?>">📄 Exporter PDF</a>
                    <a href="#" class="dropdown-item" data-action="send-email" data-id="<?php echo $withdrawalData['id']; ?>">📧 Envoyer email</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-error" data-action="delete-withdrawal" data-id="<?php echo $withdrawalData['id']; ?>" data-confirm="Supprimer cette demande ?">🗑️ Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Informations du retrait</h3>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if ($withdrawalData['amount'] > $user['balance']): ?>
                            <span class="badge badge-error">⚠️ Solde insuffisant</span>
                        <?php else: ?>
                            <span class="badge badge-success">✅ Solde suffisant</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Détails du retrait</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Montant demandé:</span>
                                    <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatCurrency($withdrawalData['amount']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Solde utilisateur:</span>
                                    <span style="font-weight: 600; color: <?php echo $user['balance'] >= $withdrawalData['amount'] ? 'var(--success-color)' : 'var(--error-color)'; ?>;">
                                        <?php echo formatCurrency($user['balance']); ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Montant disponible:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($withdrawableAmount); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Date de demande:</span>
                                    <span style="font-weight: 600;"><?php echo formatDateTime($withdrawalData['created_at']); ?></span>
                                </div>
                                <?php if ($withdrawalData['processed_at']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Date de traitement:</span>
                                    <span style="font-weight: 600;"><?php echo formatDateTime($withdrawalData['processed_at']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($withdrawalData['transaction_reference']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Référence:</span>
                                    <span style="font-weight: 600; font-family: monospace;"><?php echo htmlspecialchars($withdrawalData['transaction_reference']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Prêt associé</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">ID du prêt:</span>
                                    <span style="font-weight: 600;">
                                        <a href="../loans/view.php?id=<?php echo $loanRequest['id']; ?>" style="color: var(--primary-color);">
                                            #<?php echo $loanRequest['id']; ?>
                                        </a>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Montant approuvé:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($loanRequest['approved_amount']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Banque partenaire:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($loanRequest['partner_bank']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Date d'approbation:</span>
                                    <span style="font-weight: 600;"><?php echo formatDateTime($loanRequest['approved_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($withdrawalData['notes']): ?>
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #E5E7EB;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Notes</h4>
                        <p style="background: #F5F7FA; padding: 1rem; border-radius: 0.5rem; margin: 0;">
                            <?php echo htmlspecialchars($withdrawalData['notes']); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($withdrawalData['rejection_reason']): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB;">
                        <h4 style="margin-bottom: 1rem; color: var(--error-color);">Motif de refus</h4>
                        <p style="background: #FED7D7; padding: 1rem; border-radius: 0.5rem; margin: 0; color: var(--error-color);">
                            <?php echo htmlspecialchars($withdrawalData['rejection_reason']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Coordonnées bancaires</h3>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php
                        $isValidIBAN = !empty($withdrawalData['iban']) && strlen($withdrawalData['iban']) >= 15;
                        $isValidSWIFT = !empty($withdrawalData['swift_code']) && strlen($withdrawalData['swift_code']) >= 8;
                        ?>
                        <?php if ($isValidIBAN): ?>
                            <span class="badge badge-success">✅ IBAN</span>
                        <?php endif; ?>
                        <?php if ($isValidSWIFT): ?>
                            <span class="badge badge-success">✅ SWIFT</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <div style="display: grid; gap: 1rem;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Titulaire du compte</label>
                                    <div style="font-weight: 600; font-size: 1.1rem; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($withdrawalData['account_holder_name']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Banque</label>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($withdrawalData['bank_name']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Numéro de compte</label>
                                    <div style="font-family: monospace; font-weight: 600; background: #F5F7FA; padding: 0.5rem; border-radius: 0.375rem;">
                                        <?php echo htmlspecialchars($withdrawalData['account_number']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div style="display: grid; gap: 1rem;">
                                <?php if ($withdrawalData['iban']): ?>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">IBAN</label>
                                    <div style="font-family: monospace; font-weight: 600; background: #F5F7FA; padding: 0.5rem; border-radius: 0.375rem; word-break: break-all;">
                                        <?php echo htmlspecialchars($withdrawalData['iban']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($withdrawalData['swift_code']): ?>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Code SWIFT/BIC</label>
                                    <div style="font-family: monospace; font-weight: 600; background: #F5F7FA; padding: 0.5rem; border-radius: 0.375rem;">
                                        <?php echo htmlspecialchars($withdrawalData['swift_code']); ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div>
                                    <label style="font-size: 0.75rem; color: #6B7280; text-transform: uppercase; font-weight: 600;">Vérification</label>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="color: <?php echo $withdrawalData['account_holder_name'] === ($user['first_name'] . ' ' . $user['last_name']) ? 'var(--success-color)' : 'var(--warning-color)'; ?>;">
                                                <?php echo $withdrawalData['account_holder_name'] === ($user['first_name'] . ' ' . $user['last_name']) ? '✅' : '⚠️'; ?>
                                            </span>
                                            <span style="font-size: 0.875rem;">Nom correspondant</span>
                                        </div>
                                        <?php if ($isValidIBAN): ?>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <span style="color: var(--success-color);">✅</span>
                                            <span style="font-size: 0.875rem;">Format IBAN valide</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">État de la validation</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Solde suffisant:</span>
                            <span style="color: <?php echo $user['balance'] >= $withdrawalData['amount'] ? 'var(--success-color)' : 'var(--error-color)'; ?>;">
                                <?php echo $user['balance'] >= $withdrawalData['amount'] ? '✅' : '❌'; ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Documents vérifiés:</span>
                            <span style="color: <?php echo count(array_filter($userDocuments, fn($doc) => $doc['is_verified'])) >= 2 ? 'var(--success-color)' : 'var(--warning-color)'; ?>;">
                                <?php echo count(array_filter($userDocuments, fn($doc) => $doc['is_verified'])) >= 2 ? '✅' : '⚠️'; ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Nom correspondant:</span>
                            <span style="color: <?php echo $withdrawalData['account_holder_name'] === ($user['first_name'] . ' ' . $user['last_name']) ? 'var(--success-color)' : 'var(--warning-color)'; ?>;">
                                <?php echo $withdrawalData['account_holder_name'] === ($user['first_name'] . ' ' . $user['last_name']) ? '✅' : '⚠️'; ?>
                            </span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Format IBAN:</span>
                            <span style="color: <?php echo $isValidIBAN ? 'var(--success-color)' : 'var(--warning-color)'; ?>;">
                                <?php echo $isValidIBAN ? '✅' : '⚠️'; ?>
                            </span>
                        </div>
                    </div>

                    <?php
                    $validationScore = 0;
                    if ($user['balance'] >= $withdrawalData['amount']) $validationScore += 25;
                    if (count(array_filter($userDocuments, fn($doc) => $doc['is_verified'])) >= 2) $validationScore += 25;
                    if ($withdrawalData['account_holder_name'] === ($user['first_name'] . ' ' . $user['last_name'])) $validationScore += 25;
                    if ($isValidIBAN) $validationScore += 25;
                    ?>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB;">
                        <div style="text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: conic-gradient(var(--success-color) <?php echo $validationScore * 3.6; ?>deg, #E5E7EB 0deg); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                                <div style="width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo $validationScore; ?>%
                                </div>
                            </div>
                            <div style="font-weight: 600;">
                                Score de validation
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">
                        Documents 
                        <span class="badge badge-info"><?php echo count($userDocuments); ?></span>
                    </h3>
                    <a href="../documents/list.php?user_id=<?php echo $withdrawalData['user_id']; ?>" class="btn btn-sm btn-secondary">Voir tout</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($userDocuments)): ?>
                        <div style="padding: 2rem; text-align: center; color: #6B7280;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">📄</div>
                            <p>Aucun document</p>
                        </div>
                    <?php else: ?>
                        <div>
                            <?php foreach ($userDocuments as $doc): ?>
                                <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 500; font-size: 0.875rem;">
                                            <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6B7280;">
                                            <?php echo date('d/m/Y', strtotime($doc['uploaded_at'])); ?>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <?php if ($doc['is_verified']): ?>
                                            <span class="badge badge-success">✅</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">⏳</span>
                                        <?php endif; ?>
                                        <div style="margin-top: 0.25rem;">
                                            <a href="../documents/view.php?id=<?php echo $doc['id']; ?>" class="action-btn view" style="font-size: 0.75rem;">👁️</a>
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
                    <h3 class="card-title">Informations utilisateur</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Email:</span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Téléphone:</span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($user['phone'] ?: 'Non renseigné'); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Statut:</span>
                            <span><?php echo getStatusBadge($user['status']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Membre depuis:</span>
                            <span style="font-weight: 600;"><?php echo date('m/Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Total retraits:</span>
                            <span style="font-weight: 600;"><?php echo count($withdrawalHistory); ?></span>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB;">
                        <a href="../users/view.php?id=<?php echo $withdrawalData['user_id']; ?>" class="btn btn-primary btn-block">
                            👤 Voir profil complet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($withdrawalHistory) && count($withdrawalHistory) > 1): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historique des retraits</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Montant</th>
                        <th>Banque</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($withdrawalHistory, 0, 5) as $w): ?>
                        <tr class="<?php echo $w['id'] == $withdrawalData['id'] ? 'current-row' : ''; ?>">
                            <td>
                                <span style="font-weight: 600; color: var(--primary-color);">#<?php echo $w['id']; ?></span>
                                <?php if ($w['id'] == $withdrawalData['id']): ?>
                                    <span class="badge badge-info" style="margin-left: 0.5rem;">Actuel</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?php echo formatCurrency($w['amount']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($w['bank_name']); ?></div>
                                <?php if ($w['partner_bank']): ?>
                                    <div style="font-size: 0.75rem; color: #6B7280;">via <?php echo htmlspecialchars($w['partner_bank']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getStatusBadge($w['status']); ?></td>
                            <td>
                                <div><?php echo date('d/m/Y', strtotime($w['created_at'])); ?></div>
                                <?php if ($w['processed_at']): ?>
                                    <div style="font-size: 0.75rem; color: var(--success-color);">
                                        Traité: <?php echo date('d/m/Y', strtotime($w['processed_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($w['id'] != $withdrawalData['id']): ?>
                                    <a href="view.php?id=<?php echo $w['id']; ?>" class="action-btn view">👁️</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

<div id="approveModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Approuver la demande de retrait</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="approveForm">
                <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawalData['id']; ?>">
                
                <div class="form-group">
                    <label for="approval_notes" class="form-label">Notes d'approbation</label>
                    <textarea id="approval_notes" 
                              name="notes" 
                              class="form-control" 
                              rows="3" 
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
            <button type="submit" form="approveForm" class="btn btn-success">✅ Approuver</button>
        </div>
    </div>
</div>

<div id="processModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Traiter le virement</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="processForm">
                <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawalData['id']; ?>">
                
                <div class="form-group">
                    <label for="transaction_reference" class="form-label">Référence de transaction</label>
                    <input type="text" 
                           id="transaction_reference" 
                           name="transaction_reference" 
                           class="form-control" 
                           placeholder="Numéro de référence du virement bancaire">
                </div>
                
                <div class="form-group">
                    <label for="process_notes" class="form-label">Notes de traitement</label>
                    <textarea id="process_notes" 
                              name="notes" 
                              class="form-control" 
                              rows="3" 
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
                        <span>Le solde utilisateur sera débité automatiquement</span>
                    </label>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="processForm" class="btn btn-success">💰 Marquer comme traité</button>
        </div>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Rejeter la demande de retrait</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="rejectForm">
                <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawalData['id']; ?>">
                
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Motif du refus *</label>
                    <select id="rejection_reason" name="rejection_reason" class="form-control" required>
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
                    <label for="rejection_notes" class="form-label">Explication détaillée *</label>
                    <textarea id="rejection_notes" 
                              name="notes" 
                              class="form-control" 
                              rows="4" 
                              required 
                              placeholder="Expliquez les raisons du refus. Cette information sera communiquée au demandeur."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="rejectForm" class="btn btn-error">❌ Rejeter</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-action]').forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const id = this.dataset.id;
            
            switch (action) {
                case 'approve-withdrawal':
                    document.getElementById('approveModal').style.display = 'block';
                    break;
                case 'process-withdrawal':
                    document.getElementById('processModal').style.display = 'block';
                    break;
                case 'reject-withdrawal':
                    document.getElementById('rejectModal').style.display = 'block';
                    break;
            }
        });
    });

    document.getElementById('approveForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('approve');
    });

    document.getElementById('processForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('process');
    });

    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitWithdrawalAction('reject');
    });
});

async function submitWithdrawalAction(action) {
    let formId, modalId;
    
    switch (action) {
        case 'approve':
            formId = 'approveForm';
            modalId = 'approveModal';
            break;
        case 'process':
            formId = 'processForm';
            modalId = 'processModal';
            break;
        case 'reject':
            formId = 'rejectForm';
            modalId = 'rejectModal';
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

<style>
.btn-block {
    width: 100%;
    text-align: center;
}

.current-row {
    background: rgba(0, 184, 217, 0.05);
    border-left: 3px solid var(--accent-1);
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #E5E7EB;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    min-width: 200px;
}

.dropdown-toggle:hover + .dropdown-menu,
.dropdown-menu:hover {
    display: block;
}

.dropdown-item {
    display: block;
    padding: 0.75rem 1rem;
    color: #374151;
    text-decoration: none;
    border-bottom: 1px solid #F3F4F6;
}

.dropdown-item:hover {
    background: #F9FAFB;
}

.dropdown-item.text-error {
    color: var(--error-color);
}

.dropdown-divider {
    height: 1px;
    background: #E5E7EB;
    margin: 0.5rem 0;
}
</style>

<?php include '../includes/footer.php'; ?>