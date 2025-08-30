<aside class="admin-sidebar" id="adminSidebar">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <ul class="nav-menu">
                        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/dashboard.php" class="nav-link">
                                <span class="nav-icon">📊</span>
                                <span class="nav-text">Tableau de bord</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Gestion</div>
                    <ul class="nav-menu">
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/users/list.php" class="nav-link">
                                <span class="nav-icon">👥</span>
                                <span class="nav-text">Utilisateurs</span>
                                <span class="nav-badge"><?php echo $admin->getDashboardStats()['users']['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/loans/') !== false ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/loans/list.php" class="nav-link">
                                <span class="nav-icon">💰</span>
                                <span class="nav-text">Demandes de prêt</span>
                                <span class="nav-badge priority"><?php echo $admin->getDashboardStats()['loans']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/withdrawals/') !== false ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/withdrawals/list.php" class="nav-link">
                                <span class="nav-icon">💸</span>
                                <span class="nav-text">Retraits</span>
                                <span class="nav-badge priority"><?php echo $admin->getDashboardStats()['withdrawals']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/documents/') !== false ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/documents/list.php" class="nav-link">
                                <span class="nav-icon">📄</span>
                                <span class="nav-text">Documents</span>
                                <span class="nav-badge"><?php echo $admin->getDashboardStats()['documents']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Contenu</div>
                    <ul class="nav-menu">
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/blog/') !== false ? 'active' : ''; ?>">
                            <a href="/deboutatoutprix/blog/list.php" class="nav-link">
                                <span class="nav-icon">📝</span>
                                <span class="nav-text">Blog</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($currentAdmin['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($currentAdmin['username']); ?></div>
                        <div class="user-role"><?php echo ucfirst($currentAdmin['role']); ?></div>
                    </div>
                </div>
                
                <div class="sidebar-version">
                    <small>PrestaCapi Admin v2.1</small>
                </div>
            </div>
        </aside>