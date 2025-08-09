<?php
require_once 'includes/auth-admin.php';

$pageTitle = 'Tableau de bord';

$stats = $admin->getDashboardStats();
$monthlyStats = $admin->getMonthlyStats();
$systemInfo = $admin->getSystemInfo();

$db = Database::getInstance();

$recentLoans = $db->fetchAll("
    SELECT lr.*, u.first_name, u.last_name, u.email 
    FROM loan_requests lr 
    JOIN users u ON lr.user_id = u.id 
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at DESC 
    LIMIT 5
");

$recentWithdrawals = $db->fetchAll("
    SELECT w.*, u.first_name, u.last_name 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'pending'
    ORDER BY w.created_at DESC 
    LIMIT 5
");

$recentUsers = $db->fetchAll("
    SELECT id, first_name, last_name, email, created_at, status 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

$pendingDocuments = $db->fetchAll("
    SELECT d.*, u.first_name, u.last_name 
    FROM documents d 
    JOIN users u ON d.user_id = u.id 
    WHERE d.is_verified = 0 
    ORDER BY d.uploaded_at DESC 
    LIMIT 5
");

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-subtitle">Vue d'ensemble de votre plateforme PrestaCapi</p>
        <div class="page-actions">
            <button class="btn btn-secondary" onclick="refreshStats()">
                🔄 Actualiser
            </button>
            <a href="reports/statistics.php" class="btn btn-primary">
                📊 Rapports détaillés
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Utilisateurs</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">👥</div>
            </div>
            <div class="stat-value" data-stat-url="ajax/quick-stat.php?type=users"><?php echo number_format($stats['users']['total']); ?></div>
            <div class="stat-change positive">
                +<?php echo $stats['users']['new_today']; ?> aujourd'hui
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Prêts en attente</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #FF9800, #FFB74D);">⏳</div>
            </div>
            <div class="stat-value" data-stat-url="ajax/quick-stat.php?type=pending_loans"><?php echo number_format($stats['loans']['pending']); ?></div>
            <div class="stat-change <?php echo $stats['loans']['pending'] > 0 ? 'negative' : 'positive'; ?>">
                <?php echo $stats['loans']['pending']; ?> à traiter
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Montant accordé</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #42A5F5);">💰</div>
            </div>
            <div class="stat-value" data-stat-url="ajax/quick-stat.php?type=total_amount"><?php echo formatCurrency($stats['loans']['total_amount']); ?></div>
            <div class="stat-change positive">
                +12.5% ce mois
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-title">Retraits en attente</div>
                <div class="stat-icon" style="background: linear-gradient(135deg, #E53935, #EF5350);">💸</div>
            </div>
            <div class="stat-value" data-stat-url="ajax/quick-stat.php?type=pending_withdrawals"><?php echo number_format($stats['withdrawals']['pending']); ?></div>
            <div class="stat-change <?php echo $stats['withdrawals']['pending'] > 0 ? 'negative' : 'positive'; ?>">
                <?php echo formatCurrency($stats['withdrawals']['total_amount']); ?> total
            </div>
        </div>
    </div>

    <div class="dashboard-overview">
        <div class="dashboard-chart">
            <div class="chart-header">
                <h3 class="chart-title">Évolution mensuelle</h3>
                <div class="chart-period">
                    <button class="period-btn active" data-period="6m" data-chart="monthly">6 mois</button>
                    <button class="period-btn" data-period="1y" data-chart="monthly">1 an</button>
                    <button class="period-btn" data-period="2y" data-chart="monthly">2 ans</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="dashboard-activity">
            <div class="activity-header">
                <h3 class="activity-title">Activité récente</h3>
                <a href="logs/activity.php" class="activity-link">Voir tout</a>
            </div>
            <div class="activity-list" data-update-url="ajax/recent-activity.php" data-update-interval="30000">
                <?php
                $recentActivity = $db->fetchAll("
                    SELECT al.*, u.first_name, u.last_name, au.username as admin_username 
                    FROM activity_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    LEFT JOIN admin_users au ON al.admin_id = au.id 
                    ORDER BY al.created_at DESC 
                    LIMIT 8
                ");

                foreach ($recentActivity as $activity):
                    $iconClass = 'info';
                    $icon = 'ℹ️';
                    
                    if (strpos($activity['action'], 'approved') !== false) {
                        $iconClass = 'success';
                        $icon = '✅';
                    } elseif (strpos($activity['action'], 'rejected') !== false) {
                        $iconClass = 'error';
                        $icon = '❌';
                    } elseif (strpos($activity['action'], 'created') !== false) {
                        $iconClass = 'info';
                        $icon = '➕';
                    }
                ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $iconClass; ?>">
                            <?php echo $icon; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title-text"><?php echo htmlspecialchars($activity['description'] ?: $activity['action']); ?></div>
                            <div class="activity-description">
                                <?php if ($activity['user_id']): ?>
                                    Par <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                <?php elseif ($activity['admin_id']): ?>
                                    Par admin <?php echo htmlspecialchars($activity['admin_username']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time"><?php echo $lang->getTimeAgo($activity['created_at']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-quick-actions">
        <a href="loans/list.php?status=pending" class="quick-action-card">
            <div class="quick-action-icon">⏳</div>
            <div class="quick-action-title">Prêts en attente</div>
            <div class="quick-action-description"><?php echo $stats['loans']['pending']; ?> demande(s) à examiner</div>
        </a>

        <a href="withdrawals/list.php?status=pending" class="quick-action-card">
            <div class="quick-action-icon">💸</div>
            <div class="quick-action-title">Retraits à traiter</div>
            <div class="quick-action-description"><?php echo $stats['withdrawals']['pending']; ?> retrait(s) en attente</div>
        </a>

        <a href="documents/list.php?status=pending" class="quick-action-card">
            <div class="quick-action-icon">📄</div>
            <div class="quick-action-title">Documents à vérifier</div>
            <div class="quick-action-description"><?php echo $stats['documents']['pending']; ?> document(s) en attente</div>
        </a>

        <a href="users/list.php" class="quick-action-card">
            <div class="quick-action-icon">👥</div>
            <div class="quick-action-title">Gestion utilisateurs</div>
            <div class="quick-action-description"><?php echo $stats['users']['total']; ?> utilisateur(s) inscrits</div>
        </a>

        <a href="testimonials/list.php?status=pending" class="quick-action-card">
            <div class="quick-action-icon">⭐</div>
            <div class="quick-action-title">Témoignages</div>
            <div class="quick-action-description"><?php echo $stats['testimonials']['pending']; ?> témoignage(s) à modérer</div>
        </a>

        <a href="settings/general.php" class="quick-action-card">
            <div class="quick-action-icon">⚙️</div>
            <div class="quick-action-title">Paramètres</div>
            <div class="quick-action-description">Configuration de la plateforme</div>
        </a>
    </div>

    <div class="dashboard-pending">
        <?php if (!empty($recentLoans)): ?>
        <div class="pending-card">
            <div class="pending-header">
                <h3 class="pending-title">
                    💰 Prêts en attente
                    <span class="pending-count"><?php echo count($recentLoans); ?></span>
                </h3>
                <a href="loans/list.php?status=pending" class="pending-view-all">Voir tout</a>
            </div>
            <div class="pending-list">
                <?php foreach ($recentLoans as $loan): ?>
                    <div class="pending-item" onclick="window.location.href='loans/view.php?id=<?php echo $loan['id']; ?>'">
                        <div class="pending-info">
                            <div class="pending-user"><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?></div>
                            <div class="pending-details"><?php echo htmlspecialchars($loan['purpose']); ?></div>
                        </div>
                        <div class="pending-amount"><?php echo formatCurrency($loan['amount']); ?></div>
                        <div class="pending-time"><?php echo formatDateTime($loan['created_at']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($recentWithdrawals)): ?>
        <div class="pending-card">
            <div class="pending-header">
                <h3 class="pending-title">
                    💸 Retraits en attente
                    <span class="pending-count"><?php echo count($recentWithdrawals); ?></span>
                </h3>
                <a href="withdrawals/list.php?status=pending" class="pending-view-all">Voir tout</a>
            </div>
            <div class="pending-list">
                <?php foreach ($recentWithdrawals as $withdrawal): ?>
                    <div class="pending-item" onclick="window.location.href='withdrawals/view.php?id=<?php echo $withdrawal['id']; ?>'">
                        <div class="pending-info">
                            <div class="pending-user"><?php echo htmlspecialchars($withdrawal['first_name'] . ' ' . $withdrawal['last_name']); ?></div>
                            <div class="pending-details"><?php echo htmlspecialchars($withdrawal['bank_name']); ?></div>
                        </div>
                        <div class="pending-amount"><?php echo formatCurrency($withdrawal['amount']); ?></div>
                        <div class="pending-time"><?php echo formatDateTime($withdrawal['created_at']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($pendingDocuments)): ?>
        <div class="pending-card">
            <div class="pending-header">
                <h3 class="pending-title">
                    📄 Documents à vérifier
                    <span class="pending-count"><?php echo count($pendingDocuments); ?></span>
                </h3>
                <a href="documents/list.php?status=pending" class="pending-view-all">Voir tout</a>
            </div>
            <div class="pending-list">
                <?php foreach ($pendingDocuments as $doc): ?>
                    <div class="pending-item" onclick="window.location.href='documents/view.php?id=<?php echo $doc['id']; ?>'">
                        <div class="pending-info">
                            <div class="pending-user"><?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></div>
                            <div class="pending-details"><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></div>
                        </div>
                        <div class="pending-time"><?php echo formatDateTime($doc['uploaded_at']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-system">
        <div class="system-header">
            <h3 class="system-title">État du système</h3>
            <p class="system-subtitle">Informations techniques et performances</p>
        </div>
        <div class="system-metrics">
            <div class="system-metric">
                <div class="metric-value">
                    <?php echo $systemInfo['php_version']; ?>
                    <span class="metric-status good"></span>
                </div>
                <div class="metric-label">Version PHP</div>
            </div>
            
            <div class="system-metric">
                <div class="metric-value">
                    <?php echo $systemInfo['disk_usage']['used_percent']; ?>%
                    <span class="metric-status <?php echo $systemInfo['disk_usage']['used_percent'] > 80 ? 'warning' : 'good'; ?>"></span>
                </div>
                <div class="metric-label">Utilisation disque</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $systemInfo['disk_usage']['used_percent']; ?>%"></div>
                </div>
            </div>
            
            <div class="system-metric">
                <div class="metric-value">
                    <?php echo $systemInfo['memory_usage']['current']; ?>
                    <span class="metric-status good"></span>
                </div>
                <div class="metric-label">Mémoire utilisée</div>
            </div>
            
            <div class="system-metric">
                <div class="metric-value">
                    <?php echo $systemInfo['database_size']; ?>
                    <span class="metric-status good"></span>
                </div>
                <div class="metric-label">Taille BDD</div>
            </div>
        </div>
    </div>
</main>

<?php
$additionalJS = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.chartManager) {
        window.chartManager.createMonthlyChart();
    }
    
    const pendingItems = document.querySelectorAll(".pending-item");
    pendingItems.forEach(item => {
        item.addEventListener("mouseenter", function() {
            this.style.transform = "translateX(4px)";
        });
        
        item.addEventListener("mouseleave", function() {
            this.style.transform = "translateX(0)";
        });
    });
    
    function updateRealTimeStats() {
        fetch("ajax/real-time-stats.php")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Object.keys(data.stats).forEach(key => {
                        const element = document.querySelector(`[data-stat-url*="${key}"]`);
                        if (element) {
                            element.textContent = data.stats[key];
                        }
                    });
                }
            })
            .catch(error => console.error("Erreur mise à jour stats:", error));
    }
    
    setInterval(updateRealTimeStats, 60000);
});
</script>
';

include 'includes/footer.php';
?>