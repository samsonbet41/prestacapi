class Dashboard {
    constructor(element, app) {
        this.element = element;
        this.app = app;
        this.refreshInterval = null;
        this.notifications = [];
        
        this.init();
    }
    
    init() {
        this.setupStatsCards();
        this.setupNotifications();
        this.setupQuickActions();
        this.setupCharts();
        this.setupProgressBars();
        this.setupRefreshTimer();
        this.loadDashboardData();
    }
    
    setupStatsCards() {
        this.animateStatCards();
        
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                const action = card.dataset.action;
                if (action) {
                    this.handleStatCardClick(action);
                }
            });
        });
    }
    
    animateStatCards() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateStatValue(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        });
        
        document.querySelectorAll('.stat-value').forEach(statValue => {
            observer.observe(statValue);
        });
    }
    
    animateStatValue(element) {
        const finalValue = parseFloat(element.textContent.replace(/[^\d.-]/g, ''));
        const duration = 2000;
        const startTime = performance.now();
        
        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);
            const currentValue = finalValue * easeOutCubic;
            
            if (element.textContent.includes('€')) {
                element.textContent = this.app.formatCurrency(currentValue);
            } else if (element.textContent.includes('%')) {
                element.textContent = Math.round(currentValue) + '%';
            } else {
                element.textContent = Math.round(currentValue).toLocaleString();
            }
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    setupNotifications() {
        this.loadNotifications();
        
        const markAllRead = document.querySelector('.mark-all-read');
        if (markAllRead) {
            markAllRead.addEventListener('click', () => {
                this.markAllNotificationsRead();
            });
        }
        
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                this.markNotificationRead(item.dataset.id);
            });
        });
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('/ajax/get-notifications.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.renderNotifications();
                this.updateNotificationBadge(data.unread_count);
            }
            
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    renderNotifications() {
        const container = document.querySelector('.notification-list');
        if (!container) return;
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <div class="empty-state-title">Aucune notification</div>
                    <div class="empty-state-description">Vous n'avez aucune notification pour le moment.</div>
                </div>
            `;
            return;
        }
        
        container.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-content">
                    <div class="notification-icon ${notification.type}">
                        ${this.getNotificationIcon(notification.type)}
                    </div>
                    <div class="notification-text">
                        <div class="notification-message">${notification.title}</div>
                        <div class="notification-description">${notification.message}</div>
                        <div class="notification-time">${this.formatTimeAgo(notification.created_at)}</div>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                this.markNotificationRead(item.dataset.id);
                
                const relatedId = item.dataset.related;
                if (relatedId) {
                    this.handleNotificationClick(item.dataset.type, relatedId);
                }
            });
        });
    }
    
    getNotificationIcon(type) {
        const icons = {
            'loan_approved': '<i class="fas fa-check-circle"></i>',
            'loan_rejected': '<i class="fas fa-times-circle"></i>',
            'withdrawal_approved': '<i class="fas fa-money-bill-wave"></i>',
            'withdrawal_rejected': '<i class="fas fa-ban"></i>',
            'document_verified': '<i class="fas fa-file-check"></i>',
            'general': '<i class="fas fa-info-circle"></i>'
        };
        
        return icons[type] || icons.general;
    }
    
    async markNotificationRead(notificationId) {
        try {
            const response = await fetch('/ajax/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const item = document.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                this.updateNotificationBadge(data.unread_count);
            }
            
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllNotificationsRead() {
        try {
            const response = await fetch('/ajax/mark-all-notifications-read.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                
                this.updateNotificationBadge(0);
            }
            
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    updateNotificationBadge(count) {
        const badges = document.querySelectorAll('.notification-badge, .nav-badge');
        
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        });
    }
    
    handleNotificationClick(type, relatedId) {
        const routes = {
            'loan_approved': () => window.location.href = '/dashboard#loans',
            'loan_rejected': () => window.location.href = '/dashboard#loans',
            'withdrawal_approved': () => window.location.href = '/dashboard#withdrawals',
            'withdrawal_rejected': () => window.location.href = '/dashboard#withdrawals',
            'document_verified': () => window.location.href = '/documents'
        };
        
        if (routes[type]) {
            routes[type]();
        }
    }
    
    setupQuickActions() {
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('a')) return;
                
                const action = card.dataset.action;
                this.handleQuickAction(action);
            });
        });
    }
    
    handleQuickAction(action) {
        const actions = {
            'new-loan': () => window.location.href = '/loan-request',
            'documents': () => window.location.href = '/documents',
            'withdrawal': () => window.location.href = '/withdrawal',
            'profile': () => window.location.href = '/profile',
            'support': () => window.location.href = '/contact'
        };
        
        if (actions[action]) {
            actions[action]();
        }
    }
    
    setupCharts() {
        this.initLoanChart();
        this.initWithdrawalChart();
    }
    
    initLoanChart() {
        const chartElement = document.getElementById('loanChart');
        if (!chartElement) return;
        
        const ctx = chartElement.getContext('2d');
        
        this.loanChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approuvés', 'En attente', 'Refusés'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: ['#4CAF50', '#FF9800', '#E53935'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    initWithdrawalChart() {
        const chartElement = document.getElementById('withdrawalChart');
        if (!chartElement) return;
        
        const ctx = chartElement.getContext('2d');
        
        this.withdrawalChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Retraits',
                    data: [],
                    borderColor: '#00B8D9',
                    backgroundColor: 'rgba(0, 184, 217, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '€';
                            }
                        }
                    }
                }
            }
        });
    }
    
    setupProgressBars() {
        document.querySelectorAll('.progress-bar').forEach(progressBar => {
            const fill = progressBar.querySelector('.progress-fill');
            const percentage = parseInt(fill.dataset.percentage || 0);
            
            setTimeout(() => {
                fill.style.width = percentage + '%';
            }, 500);
        });
    }
    
    setupRefreshTimer() {
        this.refreshInterval = setInterval(() => {
            this.refreshDashboardData();
        }, 5 * 60 * 1000);
        
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refreshDashboardData();
            }
        });
    }
    
    async loadDashboardData() {
        try {
            const response = await fetch('/ajax/dashboard-data.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardData(data.data);
            }
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }
    
    async refreshDashboardData() {
        try {
            const response = await fetch('/ajax/dashboard-data.php?refresh=1', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateDashboardData(data.data);
                this.loadNotifications();
            }
            
        } catch (error) {
            console.error('Error refreshing dashboard data:', error);
        }
    }
    
    updateDashboardData(data) {
        this.updateBalance(data.balance);
        this.updateStats(data.stats);
        this.updateProgress(data.progress);
        this.updateCharts(data.charts);
        this.updateRecentActivity(data.recent_activity);
    }
    
    updateBalance(balance) {
        const balanceElement = document.querySelector('.balance-amount');
        if (balanceElement) {
            const currentBalance = parseFloat(balanceElement.textContent.replace(/[^\d.-]/g, ''));
            
            if (currentBalance !== balance) {
                balanceElement.style.transform = 'scale(1.1)';
                balanceElement.style.color = '#4CAF50';
                
                setTimeout(() => {
                    balanceElement.textContent = this.app.formatCurrency(balance);
                    balanceElement.style.transform = 'scale(1)';
                    balanceElement.style.color = '';
                }, 300);
            }
        }
    }
    
    updateStats(stats) {
        Object.keys(stats).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    }
    
    updateProgress(progress) {
        const progressBar = document.querySelector('.progress-fill');
        const progressText = document.querySelector('.progress-percentage');
        
        if (progressBar && progress.percentage !== undefined) {
            progressBar.style.width = progress.percentage + '%';
        }
        
        if (progressText) {
            progressText.textContent = progress.percentage + '%';
        }
        
        const progressItems = document.querySelectorAll('.progress-item');
        progressItems.forEach((item, index) => {
            if (progress.items && progress.items[index]) {
                const icon = item.querySelector('.progress-item-icon');
                const text = item.querySelector('.progress-item-text');
                
                if (progress.items[index].completed) {
                    icon.classList.add('completed');
                    text.classList.add('completed');
                    icon.innerHTML = '<i class="fas fa-check"></i>';
                } else {
                    icon.classList.remove('completed');
                    text.classList.remove('completed');
                    icon.innerHTML = '<i class="fas fa-circle"></i>';
                }
            }
        });
    }
    
    updateCharts(chartData) {
        if (this.loanChart && chartData.loans) {
            this.loanChart.data.datasets[0].data = chartData.loans.data;
            this.loanChart.update();
        }
        
        if (this.withdrawalChart && chartData.withdrawals) {
            this.withdrawalChart.data.labels = chartData.withdrawals.labels;
            this.withdrawalChart.data.datasets[0].data = chartData.withdrawals.data;
            this.withdrawalChart.update();
        }
    }
    
    updateRecentActivity(activities) {
        const container = document.querySelector('.timeline');
        if (!container || !activities) return;
        
        container.innerHTML = activities.map(activity => `
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-title">${activity.title}</div>
                        <div class="timeline-date">${this.formatTimeAgo(activity.date)}</div>
                    </div>
                    <div class="timeline-description">${activity.description}</div>
                </div>
            </div>
        `).join('');
    }
    
    handleStatCardClick(action) {
        const actions = {
            'view-loans': () => this.showLoansModal(),
            'view-withdrawals': () => this.showWithdrawalsModal(),
            'view-documents': () => window.location.href = '/documents',
            'view-notifications': () => this.toggleNotificationPanel()
        };
        
        if (actions[action]) {
            actions[action]();
        }
    }
    
    showLoansModal() {
        this.app.openModal('loansModal');
    }
    
    showWithdrawalsModal() {
        this.app.openModal('withdrawalsModal');
    }
    
    toggleNotificationPanel() {
        const panel = document.querySelector('.notification-panel');
        if (panel) {
            panel.classList.toggle('open');
        }
    }
    
    formatTimeAgo(date) {
        const now = new Date();
        const past = new Date(date);
        const diffMs = now - past;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'À l\'instant';
        if (diffMins < 60) return `Il y a ${diffMins} min`;
        if (diffHours < 24) return `Il y a ${diffHours}h`;
        if (diffDays < 7) return `Il y a ${diffDays}j`;
        
        return this.app.formatDate(date);
    }
    
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        if (this.loanChart) {
            this.loanChart.destroy();
        }
        
        if (this.withdrawalChart) {
            this.withdrawalChart.destroy();
        }
    }
}

window.Dashboard = Dashboard;