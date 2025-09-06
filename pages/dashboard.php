<?php
$pageKey = 'dashboard';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Language.php';
require_once 'classes/LoanRequest.php';
require_once 'classes/Withdrawal.php';
require_once 'classes/Document.php';
require_once 'classes/SEO.php';

$lang = Language::getInstance();
$user = new User();
$loanRequest = new LoanRequest();
$withdrawal = new Withdrawal();
$document = new Document();
$seo = new SEO();

$user->requireAuth();

$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

$stats = $user->getDashboardStats($userId);
$recentLoans = $user->getUserLoanRequests($userId, 5);
$recentWithdrawals = $user->getUserWithdrawals($userId, 5);
$notifications = $user->getUserNotifications($userId, 10);
$documentStatus = $document->getUserDocumentStatus($userId);
$withdrawalCheck = $withdrawal->canUserRequestWithdrawal($userId);

$pageTitle = $lang->get('dashboard_title');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="canonical" href="<?php echo $seo->generateCanonicalUrl($lang->pageUrl($pageKey)); ?>">
    
    <?php echo $seo->generateAlternateLinks(); ?>
    
    <?php echo $seo->generateOpenGraphTags(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php echo $seo->generateTwitterCard(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    
    <?php echo $seo->generateMetaTags(); ?>

    <?php echo $seo->generateStructuredData('webpage', ['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <meta name="robots" content="noindex, nofollow">
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
    <style>
        .dashboard-alert {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background-color: #FFEBEE;
            color: #D32F2F;
            border: 1px solid #FFCDD2;
        }
        .dashboard-alert .alert-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .dashboard-alert .alert-content {
            flex-grow: 1;
        }
        .dashboard-alert .alert-content h4 {
            margin: 0 0 0.25rem 0;
            font-weight: 600;
            color: #C62828;
        }
        .dashboard-alert .alert-content p {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        .dashboard-alert .alert-action {
            margin-left: 1rem;
            flex-shrink: 0;
        }
        .dashboard-alert .alert-action .btn {
            background-color: #D32F2F;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        .dashboard-alert .alert-action .btn:hover {
            background-color: #C62828;
        }
    </style>
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <div class="user-welcome">
                    <h1 class="dashboard-title">
                        <?php echo $lang->get('dashboard_welcome', ['name' => htmlspecialchars($currentUser['first_name'])]); ?>
                    </h1>
                    <p class="dashboard-subtitle"><?php echo $lang->get('dashboard_subtitle'); ?></p>
                </div>
                
                <div class="user-profile-summary">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                    </div>
                    <div class="profile-balance">
                        <div class="balance-label"><?php echo $lang->get('dashboard_balance'); ?></div>
                        <div class="balance-amount"><?php echo $lang->formatCurrency($stats['balance']); ?></div>
                    </div>
                </div>
            </div>

            <?php if ($documentStatus['completion_percentage'] < 100): ?>
            <div class="dashboard-alert">
                <div class="alert-icon">
                    ⚠️
                </div>
                <div class="alert-content">
                    <h4><?php echo $lang->get('dashboard_documents_warning_title'); ?></h4>
                    <p><?php echo $lang->get('dashboard_documents_warning_desc'); ?></p>
                </div>
                <div class="alert-action">
                    <a href="<?php echo $lang->pageUrl('documents'); ?>" class="btn">
                        <?php echo $lang->get('dashboard_documents_warning_btn'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="dashboard-content">
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $lang->formatCurrency($stats['balance']); ?></div>
                            <div class="stat-label"><?php echo $lang->get('dashboard_balance'); ?></div>
                        </div>
                        <div class="stat-trend">
                            <?php if ($stats['total_approved_amount'] > 0): ?>
                                <span class="trend-up">+<?php echo $lang->formatCurrency($stats['total_approved_amount']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stats['total_loans']; ?></div>
                            <div class="stat-label"><?php echo $lang->get('dashboard_stats_loans'); ?></div>
                        </div>
                        <div class="stat-detail">
                            <?php echo $stats['approved_loans']; ?> <?php echo $lang->get('dashboard_stats_approved'); ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stats['pending_loans']; ?></div>
                            <div class="stat-label"><?php echo $lang->get('dashboard_stats_pending'); ?></div>
                        </div>
                        <div class="stat-detail">
                            <?php echo $lang->get('dashboard_pending_withdrawals_detail', ['count' => $stats['pending_withdrawals']]); ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔔</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $stats['unread_notifications']; ?></div>
                            <div class="stat-label"><?php echo $lang->get('notifications_title'); ?></div>
                        </div>
                        <div class="stat-detail">
                           <?php echo $lang->get('dashboard_unread_messages_detail'); ?>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $lang->get('dashboard_quick_actions'); ?></h2>
                            <p class="section-subtitle"><?php echo $lang->get('dashboard_quick_actions_subtitle'); ?></p>
                        </div>
                        
                        <div class="quick-actions">
                            <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="action-card primary">
                                <div class="action-icon">📝</div>
                                <div class="action-content">
                                    <h3 class="action-title"><?php echo $lang->get('dashboard_new_loan_request'); ?></h3>
                                    <p class="action-description"><?php echo $lang->get('dashboard_new_loan_request_desc'); ?></p>
                                </div>
                                <div class="action-arrow">→</div>
                            </a>
                            
                            <?php if ($withdrawalCheck['can_request']): ?>
                                <a href="<?php echo $lang->pageUrl('withdrawal'); ?>" class="action-card">
                                    <div class="action-icon">💸</div>
                                    <div class="action-content">
                                        <h3 class="action-title"><?php echo $lang->get('dashboard_request_withdrawal'); ?></h3>
                                        <p class="action-description"><?php echo $lang->get('dashboard_withdrawal_up_to', ['amount' => $lang->formatCurrency($withdrawalCheck['max_amount'])]); ?></p>
                                    </div>
                                    <div class="action-arrow">→</div>
                                </a>
                            <?php else: ?>
                                <div class="action-card disabled" title="<?php echo htmlspecialchars($withdrawalCheck['reason']); ?>">
                                    <div class="action-icon">💸</div>
                                    <div class="action-content">
                                        <h3 class="action-title"><?php echo $lang->get('dashboard_request_withdrawal'); ?></h3>
                                        <p class="action-description"><?php echo htmlspecialchars($withdrawalCheck['reason']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?php echo $lang->pageUrl('documents'); ?>" class="action-card">
                                <div class="action-icon">📄</div>
                                <div class="action-content">
                                    <h3 class="action-title"><?php echo $lang->get('dashboard_view_documents'); ?></h3>
                                    <p class="action-description"><?php echo $lang->get('dashboard_docs_verified_count', ['verified' => $documentStatus['verified'], 'total' => $documentStatus['total_required']]); ?></p>
                                </div>
                                <div class="action-arrow">→</div>
                            </a>
                            
                            <a href="<?php echo $lang->pageUrl('profile'); ?>" class="action-card">
                                <div class="action-icon">👤</div>
                                <div class="action-content">
                                    <h3 class="action-title"><?php echo $lang->get('dashboard_update_profile'); ?></h3>
                                    <p class="action-description"><?php echo $lang->get('dashboard_update_profile_desc'); ?></p>
                                </div>
                                <div class="action-arrow">→</div>
                            </a>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $lang->get('dashboard_loan_requests_title'); ?></h2>
                            <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="section-link"><?php echo $lang->get('dashboard_apply_link'); ?></a>
                        </div>
                        
                        <div class="data-table-container">
                            <?php if (!empty($recentLoans)): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo $lang->get('amount'); ?></th>
                                            <th><?php echo $lang->get('status'); ?></th>
                                            <th><?php echo $lang->get('date'); ?></th>
                                            <th><?php echo $lang->get('common_actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentLoans as $loan): ?>
                                            <tr>
                                                <td>
                                                    <div class="amount-cell">
                                                        <?php echo $lang->formatCurrency($loan['amount']); ?>
                                                        <?php if ($loan['approved_amount']): ?>
                                                            <small class="approved-amount"><?php echo $lang->get('dashboard_loan_approved_label'); ?> <?php echo $lang->formatCurrency($loan['approved_amount']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $loan['status']; ?>">
                                                        <?php echo $lang->get('loan_status_' . $loan['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="date-cell">
                                                        <?php echo $lang->formatDate($loan['created_at']); ?>
                                                        <small><?php echo $lang->getTimeAgo($loan['created_at']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="table-actions">
                                                        <button class="btn-icon" onclick="viewLoanDetails(<?php echo $loan['id']; ?>)" title="<?php echo $lang->get('dashboard_view_details_title'); ?>">
                                                            👁️
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📋</div>
                                    <h3><?php echo $lang->get('dashboard_no_loan_requests_title'); ?></h3>
                                    <p><?php echo $lang->get('dashboard_no_loan_requests_desc'); ?></p>
                                    <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="btn btn-primary">
                                        <?php echo $lang->get('dashboard_make_first_request_btn'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title"><?php echo $lang->get('dashboard_withdrawals_title'); ?></h2>
                            <?php if ($withdrawalCheck['can_request']): ?>
                                <a href="<?php echo $lang->pageUrl('withdrawal'); ?>" class="section-link"><?php echo $lang->get('dashboard_new_withdrawal_link'); ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="data-table-container">
                            <?php if (!empty($recentWithdrawals)): ?>
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo $lang->get('amount'); ?></th>
                                            <th><?php echo $lang->get('bank'); ?></th>
                                            <th><?php echo $lang->get('status'); ?></th>
                                            <th><?php echo $lang->get('date'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentWithdrawals as $withdrawalItem): ?>
                                            <tr>
                                                <td><?php echo $lang->formatCurrency($withdrawalItem['amount']); ?></td>
                                                <td>
                                                    <div class="bank-cell">
                                                        <?php echo htmlspecialchars($withdrawalItem['bank_name']); ?>
                                                        <small><?php echo htmlspecialchars(substr($withdrawalItem['account_number'], -4)); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $withdrawalItem['status']; ?>">
                                                        <?php echo $lang->get('withdrawal_status_' . $withdrawalItem['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="date-cell">
                                                        <?php echo $lang->formatDate($withdrawalItem['created_at']); ?>
                                                        <small><?php echo $lang->getTimeAgo($withdrawalItem['created_at']); ?></small>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon">💸</div>
                                    <h3><?php echo $lang->get('dashboard_no_withdrawals_title'); ?></h3>
                                    <p><?php echo $lang->get('dashboard_no_withdrawals_desc'); ?></p>
                                    <?php if ($withdrawalCheck['can_request']): ?>
                                        <a href="<?php echo $lang->pageUrl('withdrawal'); ?>" class="btn btn-outline">
                                            <?php echo $lang->get('dashboard_make_withdrawal_request_btn'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-sidebar">
                    <div class="sidebar-section">
                        <div class="section-header">
                            <h3 class="section-title"><?php echo $lang->get('notifications_title'); ?></h3>
                            <?php if ($stats['unread_notifications'] > 0): ?>
                                <button class="section-action" onclick="markAllNotificationsRead()">
                                    <?php echo $lang->get('notifications_mark_all_read'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notifications-list">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach (array_slice($notifications, 0, 5) as $notification): ?>
                                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                        <div class="notification-icon">
                                            <?php
                                            $icons = [
                                                'loan_approved' => '✅',
                                                'loan_rejected' => '❌',
                                                'withdrawal_approved' => '💰',
                                                'withdrawal_rejected' => '⚠️',
                                                'document_verified' => '📄',
                                                'general' => '🔔'
                                            ];
                                            echo $icons[$notification['type']] ?? '🔔';
                                            ?>
                                        </div>
                                        <div class="notification-content">
                                            <h4 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h4>
                                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <span class="notification-time"><?php echo $lang->getTimeAgo($notification['created_at']); ?></span>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="notification-mark-read" onclick="markNotificationRead(<?php echo $notification['id']; ?>)">
                                                ✓
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-notifications">
                                    <div class="empty-icon">🔔</div>
                                    <p><?php echo $lang->get('notifications_empty'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <div class="section-header">
                            <h3 class="section-title"><?php echo $lang->get('dashboard_doc_status_title'); ?></h3>
                            <a href="<?php echo $lang->pageUrl('documents'); ?>" class="section-action"><?php echo $lang->get('dashboard_manage_link'); ?></a>
                        </div>
                        
                        <div class="document-progress">
                            <div class="progress-circle">
                                <svg class="progress-ring" width="60" height="60">
                                    <circle class="progress-ring-background" cx="30" cy="30" r="25"></circle>
                                    <circle class="progress-ring-progress" cx="30" cy="30" r="25" 
                                            style="stroke-dashoffset: <?php echo 157 - (157 * $documentStatus['completion_percentage'] / 100); ?>"></circle>
                                </svg>
                                <div class="progress-text"><?php echo $documentStatus['completion_percentage']; ?>%</div>
                            </div>
                            <div class="progress-info">
                                <div class="progress-label"><?php echo $lang->get('dashboard_verified_documents_label'); ?></div>
                                <div class="progress-detail">
                                    <?php echo $lang->get('dashboard_doc_status_detail', ['verified' => $documentStatus['verified'], 'total' => $documentStatus['total_required']]); ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($documentStatus['missing'])): ?>
                            <div class="missing-documents">
                                <h4><?php echo $lang->get('dashboard_missing_documents_title'); ?></h4>
                                <ul>
                                    <?php foreach ($documentStatus['missing'] as $missingDoc): ?>
                                        <li><?php echo $document->getDocumentTypeName($missingDoc); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="sidebar-section">
                        <div class="section-header">
                            <h3 class="section-title"><?php echo $lang->get('dashboard_help_support_title'); ?></h3>
                        </div>
                        
                        <div class="support-links">
                            <a href="<?php echo $lang->pageUrl('contact'); ?>" class="support-link">
                                <div class="support-icon">💬</div>
                                <div class="support-content">
                                    <div class="support-title"><?php echo $lang->get('contact_us'); ?></div>
                                    <div class="support-description"><?php echo $lang->get('dashboard_support_7d'); ?></div>
                                </div>
                            </a>
                            
                            <a href="tel:+33745505207" class="support-link">
                                <div class="support-icon">📞</div>
                                <div class="support-content">
                                    <div class="support-title">+33 7 45 50 52 07</div>
                                    <div class="support-description"><?php echo $lang->get('dashboard_support_hours'); ?></div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div class="modal" id="loanDetailsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $lang->get('dashboard_modal_loan_details_title'); ?></h3>
                <button class="modal-close" onclick="closeLoanDetailsModal()">&times;</button>
            </div>
            <div class="modal-body" id="loanDetailsContent">
                <div class="loading"><?php echo $lang->get('loading'); ?></div>
            </div>
        </div>
    </div>
    
    <script src="/js/main.js"></script>
    <script src="/js/modules/dashboard.js"></script>
    <script>
        function viewLoanDetails(loanId) {
            document.getElementById('loanDetailsModal').classList.add('show');
            document.getElementById('loanDetailsContent').innerHTML = `<div class="loading"><?php echo $lang->get('loading'); ?></div>`;
            
            fetch(`/ajax/get-loan-details.php?id=${loanId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('loanDetailsContent').innerHTML = data.html;
                    } else {
                        document.getElementById('loanDetailsContent').innerHTML = `<div class="error"><?php echo $lang->get('dashboard_modal_load_error'); ?></div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('loanDetailsContent').innerHTML = `<div class="error"><?php echo $lang->get('dashboard_modal_load_error'); ?></div>`;
                });
        }
        
        function closeLoanDetailsModal() {
            document.getElementById('loanDetailsModal').classList.remove('show');
        }
        
        function markNotificationRead(notificationId) {
            fetch('/ajax/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function markAllNotificationsRead() {
            fetch('/ajax/mark-all-notifications-read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const statsCards = document.querySelectorAll('.stat-card');
            statsCards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('animate-in');
                }, index * 100);
            });
        });
    </script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>