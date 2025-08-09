<aside class="admin-sidebar" id="adminSidebar">
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <ul class="nav-menu">
                        <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <a href="dashboard.php" class="nav-link">
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
                            <a href="users/list.php" class="nav-link">
                                <span class="nav-icon">👥</span>
                                <span class="nav-text">Utilisateurs</span>
                                <span class="nav-badge"><?php echo $admin->getDashboardStats()['users']['total'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/loans/') !== false ? 'active' : ''; ?>">
                            <a href="loans/list.php" class="nav-link">
                                <span class="nav-icon">💰</span>
                                <span class="nav-text">Demandes de prêt</span>
                                <span class="nav-badge priority"><?php echo $admin->getDashboardStats()['loans']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/withdrawals/') !== false ? 'active' : ''; ?>">
                            <a href="withdrawals/list.php" class="nav-link">
                                <span class="nav-icon">💸</span>
                                <span class="nav-text">Retraits</span>
                                <span class="nav-badge priority"><?php echo $admin->getDashboardStats()['withdrawals']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/documents/') !== false ? 'active' : ''; ?>">
                            <a href="documents/list.php" class="nav-link">
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
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/partners/') !== false ? 'active' : ''; ?>">
                            <a href="partners/list.php" class="nav-link">
                                <span class="nav-icon">🏦</span>
                                <span class="nav-text">Partenaires</span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/blog/') !== false ? 'active' : ''; ?>">
                            <a href="blog/list.php" class="nav-link">
                                <span class="nav-icon">📝</span>
                                <span class="nav-text">Blog</span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/testimonials/') !== false ? 'active' : ''; ?>">
                            <a href="testimonials/list.php" class="nav-link">
                                <span class="nav-icon">⭐</span>
                                <span class="nav-text">Témoignages</span>
                                <span class="nav-badge"><?php echo $admin->getDashboardStats()['testimonials']['pending'] ?? 0; ?></span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <?php if (hasPermission('manage_admins') || hasPermission('view_reports')): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>
                    <ul class="nav-menu">
                        <?php if (hasPermission('view_reports')): ?>
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>">
                            <a href="reports/statistics.php" class="nav-link">
                                <span class="nav-icon">📈</span>
                                <span class="nav-text">Rapports</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('manage_admins')): ?>
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/admins/') !== false ? 'active' : ''; ?>">
                            <a href="admins/list.php" class="nav-link">
                                <span class="nav-icon">👨‍💼</span>
                                <span class="nav-text">Administrateurs</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>">
                            <a href="settings/general.php" class="nav-link">
                                <span class="nav-icon">⚙️</span>
                                <span class="nav-text">Paramètres</span>
                            </a>
                        </li>
                        
                        <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/logs/') !== false ? 'active' : ''; ?>">
                            <a href="logs/activity.php" class="nav-link">
                                <span class="nav-icon">📋</span>
                                <span class="nav-text">Journaux</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="nav-section">
                    <div class="nav-section-title">Support</div>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="../" target="_blank" class="nav-link">
                                <span class="nav-icon">🌐</span>
                                <span class="nav-text">Voir le site</span>
                                <span class="nav-external">↗</span>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a href="help.php" class="nav-link">
                                <span class="nav-icon">❓</span>
                                <span class="nav-text">Aide</span>
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