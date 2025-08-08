<?php
$lang = Language::getInstance();
$user = new User();
$currentUser = $user->getCurrentUser();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['REQUEST_URI'];

function isActivePage($page, $currentPage, $currentPath) {
    if (strpos($currentPath, '/' . $page) !== false) {
        return 'active';
    }
    if ($currentPage === $page) {
        return 'active';
    }
    return '';
}

function getUserProgress() {
    global $currentUser;
    
    $progress = 0;
    $total_steps = 4;
    
    if (!empty($currentUser['first_name']) && !empty($currentUser['last_name'])) {
        $progress++;
    }
    
    if (!empty($currentUser['phone']) && !empty($currentUser['date_of_birth'])) {
        $progress++;
    }
    
    require_once __DIR__ . '/../classes/Document.php';
    $document = new Document();
    $docs = $document->getUserDocuments($currentUser['id'], true);
    
    $required_docs = ['identity', 'income_proof'];
    $verified_docs = 0;
    
    foreach ($required_docs as $doc_type) {
        if (isset($docs[$doc_type])) {
            foreach ($docs[$doc_type] as $doc) {
                if ($doc['is_verified']) {
                    $verified_docs++;
                    break;
                }
            }
        }
    }
    
    if ($verified_docs >= 1) $progress++;
    if ($verified_docs >= 2) $progress++;
    
    return ($progress / $total_steps) * 100;
}

$userProgress = 0;
if ($currentUser) {
    $userProgress = getUserProgress();
}
?>

<nav class="user-nav">
    <div class="container">
        <div class="user-nav-content">
            <div class="user-nav-info">
                <?php if ($currentUser): ?>
                    <div class="user-welcome">
                        <h2 class="welcome-text">
                            <?php echo $lang->get('dashboard_welcome', ['name' => htmlspecialchars($currentUser['first_name'])]); ?>
                        </h2>
                        <div class="user-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $userProgress; ?>%"></div>
                            </div>
                            <span class="progress-text">
                                Profil complété à <?php echo round($userProgress); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div class="user-balance">
                        <span class="balance-label"><?php echo $lang->get('dashboard_balance'); ?></span>
                        <span class="balance-amount">
                            <?php echo $lang->formatCurrency($currentUser['balance'] ?? 0); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <ul class="user-nav-links">
                <li class="nav-item <?php echo isActivePage('dashboard', $current_page, $current_path); ?>">
                    <a href="<?php echo $lang->pageUrl('dashboard'); ?>" class="nav-link">
                        <i class="icon-dashboard"></i>
                        <span><?php echo $lang->get('dashboard'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item <?php echo isActivePage('profile', $current_page, $current_path); ?>">
                    <a href="<?php echo $lang->pageUrl('profile'); ?>" class="nav-link">
                        <i class="icon-user"></i>
                        <span><?php echo $lang->get('profile'); ?></span>
                        <?php if ($userProgress < 50): ?>
                            <span class="nav-badge incomplete">!</span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item <?php echo isActivePage('documents', $current_page, $current_path); ?>">
                    <a href="<?php echo $lang->pageUrl('documents'); ?>" class="nav-link">
                        <i class="icon-document"></i>
                        <span><?php echo $lang->get('documents_title'); ?></span>
                        <?php
                        if ($currentUser) {
                            require_once __DIR__ . '/../classes/Document.php';
                            $document = new Document();
                            $pendingDocs = count($document->getAllDocuments('pending'));
                            if ($pendingDocs > 0):
                        ?>
                            <span class="nav-badge pending"><?php echo $pendingDocs; ?></span>
                        <?php endif; } ?>
                    </a>
                </li>
                
                <li class="nav-item <?php echo isActivePage('loan-request', $current_page, $current_path); ?>">
                    <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="nav-link">
                        <i class="icon-money"></i>
                        <span><?php echo $lang->get('loan_request_title'); ?></span>
                    </a>
                </li>
                
                <li class="nav-item <?php echo isActivePage('withdrawal', $current_page, $current_path); ?>">
                    <a href="<?php echo $lang->pageUrl('withdrawal'); ?>" class="nav-link">
                        <i class="icon-withdraw"></i>
                        <span><?php echo $lang->get('withdrawal_title'); ?></span>
                        <?php if ($currentUser && floatval($currentUser['balance']) > 0): ?>
                            <span class="nav-badge available">€</span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <div class="user-nav-actions">
                <div class="nav-notifications">
                    <button class="notifications-toggle" onclick="toggleNotifications()">
                        <i class="icon-bell"></i>
                        <?php
                        if ($currentUser) {
                            $unreadCount = $user->getUnreadNotificationsCount($currentUser['id']);
                            if ($unreadCount > 0):
                        ?>
                            <span class="notification-count"><?php echo $unreadCount; ?></span>
                        <?php endif; } ?>
                    </button>
                    
                    <div class="notifications-dropdown" id="notificationsDropdown">
                        <div class="notifications-header">
                            <h3><?php echo $lang->get('notifications_title'); ?></h3>
                            <button class="mark-all-read" onclick="markAllNotificationsRead()">
                                <?php echo $lang->get('notifications_mark_all_read'); ?>
                            </button>
                        </div>
                        
                        <div class="notifications-list" id="notificationsList">
                            <div class="loading-notifications">
                                <div class="spinner"></div>
                                <span><?php echo $lang->get('loading'); ?></span>
                            </div>
                        </div>
                        
                        <div class="notifications-footer">
                            <a href="/notifications" class="view-all-notifications">
                                Voir toutes les notifications
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="nav-help">
                    <button class="help-toggle" onclick="toggleHelp()">
                        <i class="icon-help"></i>
                        <span><?php echo $lang->get('help'); ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="user-nav-mobile">
    <button class="mobile-nav-toggle" onclick="toggleMobileUserNav()">
        <i class="icon-menu"></i>
        <span>Menu</span>
    </button>
    
    <div class="mobile-nav-overlay" id="mobileNavOverlay" onclick="closeMobileUserNav()"></div>
    
    <div class="mobile-nav-menu" id="mobileNavMenu">
        <div class="mobile-nav-header">
            <div class="user-info">
                <div class="user-avatar-mobile">
                    <?php echo $currentUser ? strtoupper(substr($currentUser['first_name'], 0, 1)) : 'U'; ?>
                </div>
                <div class="user-details">
                    <span class="user-name">
                        <?php echo $currentUser ? htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) : 'Utilisateur'; ?>
                    </span>
                    <span class="user-balance-mobile">
                        <?php echo $lang->formatCurrency($currentUser['balance'] ?? 0); ?>
                    </span>
                </div>
            </div>
            <button class="mobile-nav-close" onclick="closeMobileUserNav()">
                <i class="icon-close"></i>
            </button>
        </div>
        
        <div class="mobile-nav-links">
            <a href="<?php echo $lang->pageUrl('dashboard'); ?>" class="mobile-nav-link">
                <i class="icon-dashboard"></i>
                <span><?php echo $lang->get('dashboard'); ?></span>
            </a>
            <a href="<?php echo $lang->pageUrl('profile'); ?>" class="mobile-nav-link">
                <i class="icon-user"></i>
                <span><?php echo $lang->get('profile'); ?></span>
            </a>
            <a href="<?php echo $lang->pageUrl('documents'); ?>" class="mobile-nav-link">
                <i class="icon-document"></i>
                <span><?php echo $lang->get('documents_title'); ?></span>
            </a>
            <a href="<?php echo $lang->pageUrl('loan_request'); ?>" class="mobile-nav-link">
                <i class="icon-money"></i>
                <span><?php echo $lang->get('loan_request_title'); ?></span>
            </a>
            <a href="<?php echo $lang->pageUrl('withdrawal'); ?>" class="mobile-nav-link">
                <i class="icon-withdraw"></i>
                <span><?php echo $lang->get('withdrawal_title'); ?></span>
            </a>
        </div>
        
        <div class="mobile-nav-footer">
            <a href="/logout" class="mobile-logout-link">
                <i class="icon-logout"></i>
                <span><?php echo $lang->get('header_logout'); ?></span>
            </a>
        </div>
    </div>
</div>