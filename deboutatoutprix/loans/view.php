<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/LoanRequest.php'; 
require_once '../../classes/Document.php'; 
requirePermission('manage_loans');

$pageTitle = 'Détail demande de prêt';

$loanId = intval($_GET['id'] ?? 0);
if ($loanId <= 0) {
    header('Location: list.php');
    exit;
}

$loanRequest = new LoanRequest();
$loan = $loanRequest->getLoanRequestById($loanId);

if (!$loan) {
    header('Location: list.php');
    exit;
}

$document = new Document();
$userDocuments = $document->getUserDocuments($loan['user_id'], false);
$missingDocs = $document->getMissingDocuments($loan['user_id'], $loan['amount']);

$eligibility = $loanRequest->calculateLoanEligibility(['balance' => $loan['balance']], $loan);

$db = Database::getInstance();
$userActivity = $db->fetchAll("
    SELECT * FROM activity_logs 
    WHERE user_id = ? AND (action LIKE '%loan%' OR action LIKE '%document%')
    ORDER BY created_at DESC 
    LIMIT 10
", [$loan['user_id']]);

$userStats = [
    'total_loans' => $db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ?", [$loan['user_id']]),
    'approved_loans' => $db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ? AND status = 'approved'", [$loan['user_id']]),
    'total_approved_amount' => $db->fetchOne("SELECT SUM(approved_amount) as total FROM loan_requests WHERE user_id = ? AND status = 'approved'", [$loan['user_id']])['total'] ?? 0,
    'documents_verified' => count(array_filter($userDocuments, function($doc) { return $doc['is_verified']; }))
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
                    Demande de prêt #<?php echo $loan['id']; ?>
                </h1>
                <p class="page-subtitle">
                    <?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?> • 
                    <?php echo formatDateTime($loan['created_at']); ?>
                </p>
            </div>
        </div>
        <div class="page-actions">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-right: 1rem;">
                <?php echo getStatusBadge($loan['status']); ?>
            </div>
            
            <?php if ($loan['status'] === 'pending' || $loan['status'] === 'under_review'): ?>
                <button class="btn btn-info" data-action="update-status" data-id="<?php echo $loan['id']; ?>" data-status="under_review">
                    🔄 Mettre en cours
                </button>
                <button class="btn btn-success" data-action="approve-loan" data-id="<?php echo $loan['id']; ?>" data-modal-id="approveModal">
                    ✅ Approuver
                </button>
                <button class="btn btn-error" data-action="reject-loan" data-id="<?php echo $loan['id']; ?>" data-modal-id="rejectModal">
                    ❌ Rejeter
                </button>
            <?php elseif ($loan['status'] === 'approved'): ?>
                <button class="btn btn-warning" data-action="update-status" data-id="<?php echo $loan['id']; ?>" data-status="disbursed">
                    💸 Marquer déboursé
                </button>
            <?php endif; ?>
            
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle">⚙️ Plus d'actions</button>
                <div class="dropdown-menu">
                    <a href="../users/view.php?id=<?php echo $loan['user_id']; ?>" class="dropdown-item">👤 Voir l'utilisateur</a>
                    <a href="#" class="dropdown-item" data-action="export-loan" data-id="<?php echo $loan['id']; ?>">📄 Exporter PDF</a>
                    <a href="#" class="dropdown-item" data-action="send-email" data-id="<?php echo $loan['id']; ?>">📧 Envoyer email</a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item text-error" data-action="delete-loan" data-id="<?php echo $loan['id']; ?>" data-confirm="Supprimer cette demande ?">🗑️ Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Informations de la demande</h3>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="badge badge-info">Score: <?php echo $eligibility['score']; ?>/100</span>
                        <span class="badge <?php echo $eligibility['recommendation'] === 'approved' ? 'badge-success' : ($eligibility['recommendation'] === 'under_review' ? 'badge-warning' : 'badge-error'); ?>">
                            <?php echo ucfirst($eligibility['recommendation']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Détails du prêt</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Montant demandé:</span>
                                    <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatCurrency($loan['amount']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Durée:</span>
                                    <span style="font-weight: 600;"><?php echo $loan['duration']; ?> mois</span>
                                </div>
                                <?php if ($loan['approved_amount']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Montant approuvé:</span>
                                    <span style="font-weight: 600; color: var(--success-color);"><?php echo formatCurrency($loan['approved_amount']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($loan['partner_bank']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Banque partenaire:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($loan['partner_bank']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Date de demande:</span>
                                    <span style="font-weight: 600;"><?php echo formatDateTime($loan['created_at']); ?></span>
                                </div>
                                <?php if ($loan['approved_at']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Date d'approbation:</span>
                                    <span style="font-weight: 600;"><?php echo formatDateTime($loan['approved_at']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Situation financière</h4>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Revenus mensuels:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($loan['monthly_income']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Charges mensuelles:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($loan['monthly_expenses']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Revenu net:</span>
                                    <span style="font-weight: 600; color: <?php echo $eligibility['net_income'] > 0 ? 'var(--success-color)' : 'var(--error-color)'; ?>;">
                                        <?php echo formatCurrency($eligibility['net_income']); ?>
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Taux d'endettement:</span>
                                    <span style="font-weight: 600; color: <?php echo $eligibility['debt_to_income_ratio'] < 33 ? 'var(--success-color)' : 'var(--error-color)'; ?>;">
                                        <?php echo round($eligibility['debt_to_income_ratio'], 1); ?>%
                                    </span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Autres prêts:</span>
                                    <span style="font-weight: 600;"><?php echo formatCurrency($loan['other_loans']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #E5E7EB;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Objectif du prêt</h4>
                        <p style="background: #F5F7FA; padding: 1rem; border-radius: 0.5rem; margin: 0;">
                            <?php echo htmlspecialchars($loan['purpose']); ?>
                        </p>
                    </div>

                    <?php if ($loan['notes']): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB;">
                        <h4 style="margin-bottom: 1rem; color: var(--primary-color);">Notes administratives</h4>
                        <p style="background: #FFF3CD; padding: 1rem; border-radius: 0.5rem; margin: 0;">
                            <?php echo htmlspecialchars($loan['notes']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Informations professionnelles</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <div style="display: grid; gap: 1rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Statut d'emploi:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($loan['employment_status']); ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Durée d'emploi:</span>
                                    <span style="font-weight: 600;"><?php echo $loan['employment_duration']; ?> mois</span>
                                </div>
                                <?php if ($loan['employer_name']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Employeur:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($loan['employer_name']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($loan['employer_phone']): ?>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #6B7280;">Téléphone employeur:</span>
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($loan['employer_phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($loan['collateral']): ?>
                            <div style="margin-bottom: 1.5rem;">
                                <h5 style="margin-bottom: 0.5rem; color: var(--primary-color);">Garanties</h5>
                                <p style="background: #F5F7FA; padding: 1rem; border-radius: 0.5rem; margin: 0;">
                                    <?php echo htmlspecialchars($loan['collateral']); ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <?php if ($loan['co_signer_name']): ?>
                            <div>
                                <h5 style="margin-bottom: 0.5rem; color: var(--primary-color);">Co-signataire</h5>
                                <div style="background: #F5F7FA; padding: 1rem; border-radius: 0.5rem;">
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($loan['co_signer_name']); ?></div>
                                    <?php if ($loan['co_signer_phone']): ?>
                                        <div style="font-size: 0.875rem; color: #6B7280;"><?php echo htmlspecialchars($loan['co_signer_phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Score d'éligibilité</h3>
                </div>
                <div class="card-body">
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: conic-gradient(var(--success-color) <?php echo $eligibility['score'] * 3.6; ?>deg, #E5E7EB 0deg); display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                            <div style="width: 80px; height: 80px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                <?php echo $eligibility['score']; ?>
                            </div>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">
                            Recommandation: 
                            <span style="color: <?php echo $eligibility['recommendation'] === 'approved' ? 'var(--success-color)' : ($eligibility['recommendation'] === 'under_review' ? 'var(--warning-color)' : 'var(--error-color)'); ?>;">
                                <?php echo ucfirst($eligibility['recommendation']); ?>
                            </span>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <h5 style="margin-bottom: 1rem;">Facteurs d'évaluation:</h5>
                        <div style="display: grid; gap: 0.5rem;">
                            <?php foreach ($eligibility['factors'] as $factor): ?>
                                <div style="padding: 0.5rem; background: #F5F7FA; border-radius: 0.375rem; font-size: 0.875rem;">
                                    • <?php echo htmlspecialchars($factor); ?>
                                </div>
                            <?php endforeach; ?>
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
                    <a href="../documents/list.php?user_id=<?php echo $loan['user_id']; ?>" class="btn btn-sm btn-secondary">Voir tout</a>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($userDocuments)): ?>
                        <div style="padding: 2rem; text-align: center; color: #6B7280;">
                            <div style="font-size: 2rem; margin-bottom: 1rem;">📄</div>
                            <p>Aucun document uploadé</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($userDocuments as $doc): ?>
                                <div style="padding: 0.75rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center;">
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

                    <?php if (!empty($missingDocs)): ?>
                        <div style="padding: 1rem 1.5rem; background: #FFF3CD; border-top: 1px solid #FFE69C;">
                            <h5 style="margin-bottom: 0.5rem; color: #D97706;">Documents manquants:</h5>
                            <ul style="margin: 0; padding-left: 1rem; font-size: 0.875rem;">
                                <?php foreach ($missingDocs as $missing): ?>
                                    <li><?php echo htmlspecialchars($missing['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistiques utilisateur</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Total demandes:</span>
                            <span style="font-weight: 600;"><?php echo $userStats['total_loans']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Prêts approuvés:</span>
                            <span style="font-weight: 600; color: var(--success-color);"><?php echo $userStats['approved_loans']; ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Montant total:</span>
                            <span style="font-weight: 600;"><?php echo formatCurrency($userStats['total_approved_amount']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Documents vérifiés:</span>
                            <span style="font-weight: 600;"><?php echo $userStats['documents_verified']; ?>/<?php echo count($userDocuments); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6B7280;">Solde actuel:</span>
                            <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatCurrency($loan['balance']); ?></span>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #E5E7EB;">
                        <a href="../users/view.php?id=<?php echo $loan['user_id']; ?>" class="btn btn-primary btn-block">
                            👤 Voir profil complet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($userActivity)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Activité récente de l'utilisateur</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($userActivity as $activity): ?>
                    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 2rem; height: 2rem; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                            <?php
                            $icon = '📋';
                            if (strpos($activity['action'], 'loan') !== false) $icon = '💰';
                            elseif (strpos($activity['action'], 'document') !== false) $icon = '📄';
                            echo $icon;
                            ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 500; margin-bottom: 0.25rem;">
                                <?php echo htmlspecialchars($activity['description'] ?: $activity['action']); ?>
                            </div>
                            <div style="font-size: 0.75rem; color: #6B7280;">
                                <?php echo formatDateTime($activity['created_at']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<div id="approveModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Approuver la demande de prêt</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="approveForm" method="POST" action="/deboutatoutprix/ajax/loan-actions.php" data-ajax="true">
                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                
                <div class="form-group">
                    <label for="approved_amount" class="form-label">Montant approuvé (€) *</label>
                    <input type="number" 
                           id="approved_amount" 
                           name="approved_amount" 
                           class="form-control" 
                           value="<?php echo $loan['amount']; ?>" 
                           step="0.01" 
                           max="<?php echo $loan['amount']; ?>" 
                           required>
                    <small class="form-text">Maximum: <?php echo formatCurrency($loan['amount']); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="partner_bank" class="form-label">Banque partenaire</label>
                    <select id="partner_bank" name="partner_bank" class="form-control">
                        <option value="PrestaCapi">PrestaCapi</option>
                        <option value="Crédit Agricole">Crédit Agricole</option>
                        <option value="BNP Paribas">BNP Paribas</option>
                        <option value="Société Générale">Société Générale</option>
                        <option value="CIC">CIC</option>
                        <option value="La Banque Postale">La Banque Postale</option>
                        <option value="Crédit Mutuel">Crédit Mutuel</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="approval_notes" class="form-label">Notes d'approbation</label>
                    <textarea id="approval_notes" 
                              name="notes" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Commentaires sur l'approbation..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="approveForm" class="btn btn-success">✅ Approuver</button>
        </div>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Rejeter la demande de prêt</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="rejectForm" method="POST" action="/deboutatoutprix/ajax/loan-actions.php" data-ajax="true">
                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                
                <div class="form-group">
                    <label for="rejection_reason" class="form-label">Motif du refus *</label>
                    <select id="rejection_reason" name="rejection_reason" class="form-control" required>
                        <option value="">Sélectionner un motif</option>
                        <option value="Revenus insuffisants">Revenus insuffisants</option>
                        <option value="Taux d'endettement trop élevé">Taux d'endettement trop élevé</option>
                        <option value="Documents manquants ou non vérifiés">Documents manquants ou non vérifiés</option>
                        <option value="Historique de crédit défavorable">Historique de crédit défavorable</option>
                        <option value="Critères d'éligibilité non respectés">Critères d'éligibilité non respectés</option>
                        <option value="Durée d'emploi insuffisante">Durée d'emploi insuffisante</option>
                        <option value="Montant demandé trop élevé">Montant demandé trop élevé</option>
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
                              placeholder="Expliquez en détail les raisons du refus. Cette information sera communiquée au demandeur."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
            <button type="submit" form="rejectForm" class="btn btn-error">❌ Rejeter</button>
        </div>
    </div>
</div>


<style>
.btn-block {
    width: 100%;
    text-align: center;
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