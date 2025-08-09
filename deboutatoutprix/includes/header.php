<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin PrestaCapi</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../images/favicon/favicon.ico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body class="admin-layout">
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="header-logo">
                    <img src="../images/logo-admin.png" alt="PrestaCapi Admin" onerror="this.style.display='none'">
                    <span class="logo-text">PrestaCapi <small>Admin</small></span>
                </div>
            </div>
            
            <div class="header-center">
                <div class="header-search">
                    <input type="text" placeholder="Rechercher..." id="globalSearch">
                    <button type="button" class="search-btn">🔍</button>
                </div>
            </div>
            
            <div class="header-right">
                <div class="header-notifications">
                    <button class="notification-btn" id="notificationBtn">
                        <span class="notification-icon">🔔</span>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <button class="mark-all-read">Tout marquer lu</button>
                        </div>
                        <div class="notification-list">
                            <div class="notification-item unread">
                                <div class="notification-content">
                                    <div class="notification-title">Nouvelle demande de prêt</div>
                                    <div class="notification-text">Marie Dubois - 15,000€</div>
                                    <div class="notification-time">Il y a 5 min</div>
                                </div>
                            </div>
                            <div class="notification-item unread">
                                <div class="notification-content">
                                    <div class="notification-title">Demande de retrait</div>
                                    <div class="notification-text">Pierre Martin - 8,500€</div>
                                    <div class="notification-time">Il y a 12 min</div>
                                </div>
                            </div>
                            <div class="notification-item">
                                <div class="notification-content">
                                    <div class="notification-title">Document vérifié</div>
                                    <div class="notification-text">Julie Leroy - CNI validée</div>
                                    <div class="notification-time">Il y a 1h</div>
                                </div>
                            </div>
                        </div>
                        <div class="notification-footer">
                            <a href="#" class="view-all-notifications">Voir toutes les notifications</a>
                        </div>
                    </div>
                </div>
                
                <div class="header-profile">
                    <button class="profile-btn" id="profileBtn">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($currentAdmin['full_name'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <div class="profile-name"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></div>
                            <div class="profile-role"><?php echo ucfirst($currentAdmin['role']); ?></div>
                        </div>
                        <span class="profile-arrow">▼</span>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-dropdown-header">
                            <div class="profile-dropdown-avatar">
                                <?php echo strtoupper(substr($currentAdmin['full_name'], 0, 1)); ?>
                            </div>
                            <div class="profile-dropdown-info">
                                <div class="profile-dropdown-name"><?php echo htmlspecialchars($currentAdmin['full_name']); ?></div>
                                <div class="profile-dropdown-email"><?php echo htmlspecialchars($currentAdmin['email']); ?></div>
                            </div>
                        </div>
                        <div class="profile-dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <span class="dropdown-icon">👤</span>
                                Mon profil
                            </a>
                            <a href="settings/general.php" class="dropdown-item">
                                <span class="dropdown-icon">⚙️</span>
                                Paramètres
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-error">
                                <span class="dropdown-icon">🚪</span>
                                Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>