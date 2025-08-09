</div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div class="loading-text">Chargement...</div>
        </div>
    </div>
    
    <div class="toast-container" id="toastContainer"></div>
    
    <script src="assets/js/admin.js"></script>
    <script src="assets/js/charts.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const notificationBtn = document.getElementById('notificationBtn');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const profileBtn = document.getElementById('profileBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    document.body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
                
                if (localStorage.getItem('sidebarCollapsed') === 'true') {
                    sidebar.classList.add('collapsed');
                    document.body.classList.add('sidebar-collapsed');
                }
            }
            
            if (notificationBtn && notificationDropdown) {
                notificationBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('show');
                    profileDropdown?.classList.remove('show');
                });
            }
            
            if (profileBtn && profileDropdown) {
                profileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                    notificationDropdown?.classList.remove('show');
                });
            }
            
            document.addEventListener('click', function() {
                notificationDropdown?.classList.remove('show');
                profileDropdown?.classList.remove('show');
            });
            
            const globalSearch = document.getElementById('globalSearch');
            if (globalSearch) {
                globalSearch.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const query = this.value.trim();
                        if (query) {
                            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                        }
                    }
                });
            }
            
            const markAllReadBtn = document.querySelector('.mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => item.classList.remove('unread'));
                    
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.textContent = '0';
                        badge.style.display = 'none';
                    }
                    
                    fetch('ajax/mark-notifications-read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                });
            }
            
            const tableFilters = document.querySelectorAll('.table-filter');
            tableFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    const table = document.querySelector('.data-table');
                    const filterValue = this.value.toLowerCase();
                    const filterType = this.dataset.filter;
                    
                    if (table) {
                        const rows = table.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            if (filterValue === '' || filterValue === 'all') {
                                row.style.display = '';
                            } else {
                                const cell = row.querySelector(`[data-${filterType}]`);
                                if (cell) {
                                    const cellValue = cell.dataset[filterType].toLowerCase();
                                    row.style.display = cellValue === filterValue ? '' : 'none';
                                }
                            }
                        });
                    }
                });
            });
            
            const confirmButtons = document.querySelectorAll('[data-confirm]');
            confirmButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const message = this.dataset.confirm;
                    if (!confirm(message)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
            
            const autoRefreshElements = document.querySelectorAll('[data-auto-refresh]');
            autoRefreshElements.forEach(element => {
                const interval = parseInt(element.dataset.autoRefresh) * 1000;
                if (interval > 0) {
                    setInterval(() => {
                        location.reload();
                    }, interval);
                }
            });
            
            window.showToast = function(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast toast-${type}`;
                toast.innerHTML = `
                    <div class="toast-content">
                        <span class="toast-icon">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
                        <span class="toast-message">${message}</span>
                    </div>
                    <button class="toast-close">×</button>
                `;
                
                const container = document.getElementById('toastContainer');
                if (container) {
                    container.appendChild(toast);
                    
                    setTimeout(() => {
                        toast.classList.add('show');
                    }, 100);
                    
                    const closeBtn = toast.querySelector('.toast-close');
                    closeBtn.addEventListener('click', () => {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 300);
                    });
                    
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.classList.remove('show');
                            setTimeout(() => toast.remove(), 300);
                        }
                    }, 5000);
                }
            };
            
            window.showLoading = function() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.classList.add('show');
                }
            };
            
            window.hideLoading = function() {
                const overlay = document.getElementById('loadingOverlay');
                if (overlay) {
                    overlay.classList.remove('show');
                }
            };
            
            const dataUpdates = document.querySelectorAll('[data-update-url]');
            dataUpdates.forEach(element => {
                const url = element.dataset.updateUrl;
                const interval = parseInt(element.dataset.updateInterval) || 30000;
                
                setInterval(async () => {
                    try {
                        const response = await fetch(url);
                        const data = await response.json();
                        
                        if (data.success && data.html) {
                            element.innerHTML = data.html;
                        }
                    } catch (error) {
                        console.error('Erreur mise à jour:', error);
                    }
                }, interval);
            });
        });
    </script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showToast('<?php echo addslashes($_SESSION['flash_message']['text']); ?>', '<?php echo $_SESSION['flash_message']['type']; ?>');
            });
        </script>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
</body>
</html>