<?php
require_once 'includes/header.php';
require_once 'includes/auth-check.php';
require_once 'classes/Withdrawal.php'; 

$seo->generateTitle($lang->get('withdrawal_page_title'));
$seo->generateDescription($lang->get('withdrawal_page_description'));

$withdrawalClass = new Withdrawal();
$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

$canRequest = $withdrawalClass->canUserRequestWithdrawal($userId);
$userBalance = $user->getUserBalance($userId);
$withdrawableAmount = $withdrawalClass->getWithdrawableAmount($userId);
$withdrawalHistory = $withdrawalClass->getUserWithdrawalHistory($userId);

$pendingWithdrawal = null;
foreach ($withdrawalHistory as $withdrawal) {
    if ($withdrawal['status'] === 'pending' || $withdrawal['status'] === 'approved') {
        $pendingWithdrawal = $withdrawal;
        break;
    }
}
?>

<main class="withdrawal-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $lang->get('withdrawal_title'); ?></h1>
            <p><?php echo $lang->get('withdrawal_subtitle'); ?></p>
        </div>

        <div class="withdrawal-content">
            <div class="withdrawal-main">
                <div class="balance-card">
                    <div class="balance-header">
                        <h2><?php echo $lang->get('current_balance'); ?></h2>
                        <div class="balance-amount">
                            <?php echo $lang->formatCurrency($userBalance); ?>
                        </div>
                    </div>
                    <div class="balance-details">
                        <div class="balance-item">
                            <span class="label"><?php echo $lang->get('available_for_withdrawal'); ?></span>
                            <span class="value"><?php echo $lang->formatCurrency($withdrawableAmount); ?></span>
                        </div>
                        <?php if ($userBalance > $withdrawableAmount): ?>
                        <div class="balance-item">
                            <span class="label"><?php echo $lang->get('pending_withdrawals'); ?></span>
                            <span class="value"><?php echo $lang->formatCurrency($userBalance - $withdrawableAmount); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!$canRequest['can_request']): ?>
                <div class="alert alert-warning">
                    <i class="icon-warning"></i>
                    <div>
                        <h3><?php echo $lang->get('withdrawal_not_available'); ?></h3>
                        <p>
                            <?php 
                            switch ($canRequest['reason']) {
                                case 'Aucun prêt approuvé':
                                    echo $lang->get('no_approved_loan');
                                    break;
                                case 'Solde insuffisant':
                                    echo $lang->get('insufficient_balance');
                                    break;
                                case 'Demande de retrait en cours':
                                    echo $lang->get('withdrawal_in_progress');
                                    break;
                                case 'Documents manquants ou non vérifiés':
                                    echo $lang->get('documents_missing');
                                    break;
                                default:
                                    echo $canRequest['reason'];
                            }
                            ?>
                        </p>
                        <?php if ($canRequest['reason'] === 'Documents manquants ou non vérifiés'): ?>
                        <a href="<?php echo generateLocalizedUrl('documents'); ?>" class="btn btn-primary">
                            <?php echo $lang->get('complete_documents'); ?>
                        </a>
                        <?php elseif ($canRequest['reason'] === 'Aucun prêt approuvé'): ?>
                        <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary">
                            <?php echo $lang->get('apply_for_loan'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($pendingWithdrawal): ?>
                <div class="alert alert-info">
                    <i class="icon-info"></i>
                    <div>
                        <h3><?php echo $lang->get('withdrawal_pending'); ?></h3>
                        <p><?php echo $lang->get('withdrawal_pending_desc'); ?></p>
                        <div class="pending-details">
                            <div class="detail-item">
                                <span class="label"><?php echo $lang->get('amount'); ?>:</span>
                                <span class="value"><?php echo $lang->formatCurrency($pendingWithdrawal['amount']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo $lang->get('bank'); ?>:</span>
                                <span class="value"><?php echo htmlspecialchars($pendingWithdrawal['bank_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo $lang->get('status'); ?>:</span>
                                <span class="value status-<?php echo $pendingWithdrawal['status']; ?>">
                                    <?php echo $lang->get('status_' . $pendingWithdrawal['status']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="label"><?php echo $lang->get('requested_on'); ?>:</span>
                                <span class="value"><?php echo $lang->formatDate($pendingWithdrawal['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="withdrawal-form-section">
                    <div class="form-header">
                        <h2><?php echo $lang->get('new_withdrawal_request'); ?></h2>
                        <p><?php echo $lang->get('withdrawal_form_desc'); ?></p>
                    </div>

                    <form id="withdrawalForm" class="withdrawal-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        
                        <div class="form-section">
                            <h3><?php echo $lang->get('withdrawal_amount'); ?></h3>
                            <div class="amount-section">
                                <div class="form-group">
                                    <label for="amount"><?php echo $lang->get('amount_to_withdraw'); ?> *</label>
                                    <div class="amount-input-group">
                                        <input type="number" id="amount" name="amount" 
                                               min="10" max="<?php echo $withdrawableAmount; ?>" 
                                               step="0.01" required>
                                        <span class="currency">€</span>
                                    </div>
                                    <div class="amount-info">
                                        <?php echo $lang->get('min_withdrawal'); ?>: 10€ | 
                                        <?php echo $lang->get('max_available'); ?>: <?php echo $lang->formatCurrency($withdrawableAmount); ?>
                                    </div>
                                    <div class="error-message"></div>
                                </div>
                                
                                <div class="quick-amounts">
                                    <span><?php echo $lang->get('quick_amounts'); ?>:</span>
                                    <?php
                                    $quickAmounts = [25, 50, 100, 500, 1000];
                                    foreach ($quickAmounts as $quickAmount) {
                                        if ($quickAmount <= $withdrawableAmount) {
                                            echo '<button type="button" class="quick-amount-btn" data-amount="' . $quickAmount . '">' . $quickAmount . '€</button>';
                                        }
                                    }
                                    if ($withdrawableAmount > 1000) {
                                        echo '<button type="button" class="quick-amount-btn" data-amount="' . $withdrawableAmount . '">' . $lang->get('all') . '</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3><?php echo $lang->get('bank_details'); ?></h3>
                            <div class="bank-details-section">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="bank_name"><?php echo $lang->get('bank_name'); ?> *</label>
                                        <input type="text" id="bank_name" name="bank_name" required 
                                               placeholder="<?php echo $lang->get('bank_name_placeholder'); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="account_holder_name"><?php echo $lang->get('account_holder_name'); ?> *</label>
                                        <input type="text" id="account_holder_name" name="account_holder_name" required
                                               value="<?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="account_number"><?php echo $lang->get('account_number'); ?> *</label>
                                    <input type="text" id="account_number" name="account_number" required
                                           placeholder="<?php echo $lang->get('account_number_placeholder'); ?>">
                                    <div class="error-message"></div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="iban"><?php echo $lang->get('iban'); ?></label>
                                        <input type="text" id="iban" name="iban" 
                                               placeholder="<?php echo $lang->get('iban_placeholder'); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="swift_code"><?php echo $lang->get('swift_bic'); ?></label>
                                        <input type="text" id="swift_code" name="swift_code"
                                               placeholder="<?php echo $lang->get('swift_placeholder'); ?>">
                                        <div class="error-message"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="notes"><?php echo $lang->get('additional_notes'); ?></label>
                                    <textarea id="notes" name="notes" rows="3" 
                                              placeholder="<?php echo $lang->get('notes_placeholder'); ?>"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="withdrawal-summary">
                                <h3><?php echo $lang->get('withdrawal_summary'); ?></h3>
                                <div class="summary-details">
                                    <div class="summary-item">
                                        <span class="label"><?php echo $lang->get('withdrawal_amount'); ?>:</span>
                                        <span class="value" id="summaryAmount">0,00 €</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="label"><?php echo $lang->get('processing_fees'); ?>:</span>
                                        <span class="value">0,00 €</span>
                                    </div>
                                    <div class="summary-item total">
                                        <span class="label"><?php echo $lang->get('net_amount'); ?>:</span>
                                        <span class="value" id="summaryTotal">0,00 €</span>
                                    </div>
                                </div>
                                <div class="processing-info">
                                    <i class="icon-info"></i>
                                    <span><?php echo $lang->get('processing_time_info'); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <span class="btn-text"><?php echo $lang->get('request_withdrawal'); ?></span>
                                <span class="btn-loader">
                                    <i class="icon-spinner"></i>
                                </span>
                            </button>
                        </div>

                        <div class="form-result" id="formResult"></div>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <div class="withdrawal-sidebar">
                <div class="info-card">
                    <h3><?php echo $lang->get('withdrawal_process'); ?></h3>
                    <div class="process-steps">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4><?php echo $lang->get('step_fill_form'); ?></h4>
                                <p><?php echo $lang->get('step_fill_form_desc'); ?></p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4><?php echo $lang->get('step_verification'); ?></h4>
                                <p><?php echo $lang->get('step_verification_desc'); ?></p>
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4><?php echo $lang->get('step_transfer'); ?></h4>
                                <p><?php echo $lang->get('step_transfer_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><?php echo $lang->get('important_info'); ?></h3>
                    <ul class="info-list">
                        <li><?php echo $lang->get('min_withdrawal_info'); ?></li>
                        <li><?php echo $lang->get('processing_time_detail'); ?></li>
                        <li><?php echo $lang->get('bank_verification_info'); ?></li>
                        <li><?php echo $lang->get('weekend_processing_info'); ?></li>
                    </ul>
                </div>

                <div class="info-card support-card">
                    <h3><?php echo $lang->get('need_help'); ?></h3>
                    <p><?php echo $lang->get('withdrawal_help_desc'); ?></p>
                    <div class="support-contacts">
                        <a href="tel:+33123456789" class="support-contact">
                            <i class="icon-phone"></i>
                            <span>+33 1 23 45 67 89</span>
                        </a>
                        <a href="mailto:support@prestacapi.com" class="support-contact">
                            <i class="icon-mail"></i>
                            <span>support@prestacapi.com</span>
                        </a>
                        <a href="<?php echo generateLocalizedUrl('contact'); ?>" class="btn btn-outline btn-sm">
                            <?php echo $lang->get('contact_us'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($withdrawalHistory)): ?>
        <div class="withdrawal-history">
            <h2><?php echo $lang->get('withdrawal_history'); ?></h2>
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th><?php echo $lang->get('date'); ?></th>
                            <th><?php echo $lang->get('amount'); ?></th>
                            <th><?php echo $lang->get('bank'); ?></th>
                            <th><?php echo $lang->get('status'); ?></th>
                            <th><?php echo $lang->get('reference'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($withdrawalHistory as $withdrawal): ?>
                        <tr>
                            <td><?php echo $lang->formatDate($withdrawal['created_at']); ?></td>
                            <td class="amount"><?php echo $lang->formatCurrency($withdrawal['amount']); ?></td>
                            <td><?php echo htmlspecialchars($withdrawal['bank_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $withdrawal['status']; ?>">
                                    <?php echo $lang->get('status_' . $withdrawal['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($withdrawal['transaction_reference'])): ?>
                                    <code><?php echo htmlspecialchars($withdrawal['transaction_reference']); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const withdrawalForm = document.getElementById('withdrawalForm');
    const amountInput = document.getElementById('amount');
    const summaryAmount = document.getElementById('summaryAmount');
    const summaryTotal = document.getElementById('summaryTotal');
    const quickAmountBtns = document.querySelectorAll('.quick-amount-btn');
    const submitBtn = document.getElementById('submitBtn');
    const formResult = document.getElementById('formResult');

    if (amountInput) {
        quickAmountBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const amount = this.getAttribute('data-amount');
                amountInput.value = amount;
                updateSummary();
            });
        });

        amountInput.addEventListener('input', updateSummary);

        function updateSummary() {
            const amount = parseFloat(amountInput.value) || 0;
            const fees = 0;
            const total = amount - fees;

            summaryAmount.textContent = amount.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';

            summaryTotal.textContent = total.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
        }

        withdrawalForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            clearErrors();
            setLoadingState(true);
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('ajax/withdrawal-request.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showResult(result.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (result.errors) {
                        showFieldErrors(result.errors);
                    }
                    showResult(result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showResult('<?php echo $lang->get("error_occurred"); ?>', 'error');
            } finally {
                setLoadingState(false);
            }
        });

        function setLoadingState(loading) {
            if (loading) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            } else {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        }

        function showResult(message, type) {
            formResult.textContent = message;
            formResult.className = `form-result ${type}`;
            formResult.style.display = 'block';
        }

        function clearErrors() {
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
                el.parentElement.classList.remove('error');
            });
        }

        function showFieldErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorElement = document.querySelector(`#${field} + .error-message, [name="${field}"] + .error-message`);
                if (errorElement) {
                    errorElement.textContent = errors[field];
                    errorElement.parentElement.classList.add('error');
                }
            });
        }
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.balance-card, .withdrawal-form-section, .info-card').forEach(el => {
        observer.observe(el);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>