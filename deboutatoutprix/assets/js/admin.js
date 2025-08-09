class AdminApp {
    constructor() {
        this.initializeApp();
        this.bindEvents();
        this.setupAjax();
    }

    initializeApp() {
        this.checkAuthentication();
        this.initializeComponents();
        this.loadNotifications();
        this.startAutoRefresh();
    }

    checkAuthentication() {
        fetch('ajax/check-auth.php')
            .then(response => response.json())
            .then(data => {
                if (!data.authenticated) {
                    window.location.href = 'login.php';
                }
            })
            .catch(error => {
                console.error('Erreur vérification auth:', error);
            });
    }

    initializeComponents() {
        this.initializeDataTables();
        this.initializeTooltips();
        this.initializeModals();
        this.initializeDatePickers();
        this.initializeFileUploads();
    }

    bindEvents() {
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        document.addEventListener('change', this.handleInputChange.bind(this));
        
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
        window.addEventListener('resize', this.handleResize.bind(this));
    }

    setupAjax() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            window.csrfToken = token;
        }

        fetch.defaults = {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.csrfToken || ''
            }
        };
    }

    handleGlobalClick(event) {
        const target = event.target.closest('[data-action]');
        if (!target) return;

        event.preventDefault();
        const action = target.dataset.action;
        const params = { ...target.dataset };
        delete params.action;

        this.executeAction(action, params, target);
    }

    executeAction(action, params, element) {
        switch (action) {
            case 'approve-loan':
                this.approveLoan(params.id, element);
                break;
            case 'reject-loan':
                this.rejectLoan(params.id, element);
                break;
            case 'approve-withdrawal':
                this.approveWithdrawal(params.id, element);
                break;
            case 'reject-withdrawal':
                this.rejectWithdrawal(params.id, element);
                break;
            case 'verify-document':
                this.verifyDocument(params.id, element);
                break;
            case 'toggle-user-status':
                this.toggleUserStatus(params.id, element);
                break;
            case 'delete-item':
                this.deleteItem(params.type, params.id, element);
                break;
            case 'bulk-action':
                this.executeBulkAction(params.action, element);
                break;
            case 'export-data':
                this.exportData(params.type, params.format);
                break;
            case 'refresh-stats':
                this.refreshStats();
                break;
            default:
                console.warn('Action non reconnue:', action);
        }
    }

    async approveLoan(loanId, element) {
        const approved_amount = prompt('Montant approuvé (€):');
        if (!approved_amount || isNaN(approved_amount)) return;

        const partner_bank = prompt('Banque partenaire:') || 'PrestaCapi';
        const notes = prompt('Notes (optionnel):') || '';

        try {
            this.showLoading();
            const response = await fetch('ajax/loan-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'approve',
                    loan_id: loanId,
                    approved_amount: parseFloat(approved_amount),
                    partner_bank: partner_bank,
                    notes: notes
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Prêt approuvé avec succès', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de l\'approbation', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async rejectLoan(loanId, element) {
        const reason = prompt('Motif du refus:');
        if (!reason || reason.trim() === '') return;

        try {
            this.showLoading();
            const response = await fetch('ajax/loan-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject',
                    loan_id: loanId,
                    reason: reason
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Prêt rejeté', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors du rejet', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async approveWithdrawal(withdrawalId, element) {
        const reference = prompt('Référence de transaction (optionnel):') || '';
        const notes = prompt('Notes (optionnel):') || 'Retrait approuvé et traité';

        try {
            this.showLoading();
            const response = await fetch('ajax/withdrawal-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'approve',
                    withdrawal_id: withdrawalId,
                    transaction_reference: reference,
                    notes: notes
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Retrait approuvé avec succès', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de l\'approbation', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async rejectWithdrawal(withdrawalId, element) {
        const reason = prompt('Motif du refus:');
        if (!reason || reason.trim() === '') return;

        try {
            this.showLoading();
            const response = await fetch('ajax/withdrawal-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'reject',
                    withdrawal_id: withdrawalId,
                    reason: reason
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Retrait rejeté', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors du rejet', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async verifyDocument(documentId, element) {
        const verified = confirm('Vérifier ce document ?');
        if (!verified) return;

        const notes = prompt('Notes (optionnel):') || '';

        try {
            this.showLoading();
            const response = await fetch('ajax/document-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'verify',
                    document_id: documentId,
                    notes: notes
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Document vérifié avec succès', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de la vérification', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async toggleUserStatus(userId, element) {
        const currentStatus = element.dataset.currentStatus;
        const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
        const action = newStatus === 'active' ? 'activer' : 'suspendre';

        if (!confirm(`Voulez-vous ${action} cet utilisateur ?`)) return;

        try {
            this.showLoading();
            const response = await fetch('ajax/user-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_status',
                    user_id: userId,
                    status: newStatus
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Utilisateur ${action === 'activer' ? 'activé' : 'suspendu'}`, 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de la modification', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    async deleteItem(type, id, element) {
        const confirmMessage = element.dataset.confirm || 'Êtes-vous sûr de vouloir supprimer cet élément ?';
        if (!confirm(confirmMessage)) return;

        try {
            this.showLoading();
            const response = await fetch('ajax/delete-item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    id: id
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast('Élément supprimé avec succès', 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    executeBulkAction(action, element) {
        const selectedItems = document.querySelectorAll('input[type="checkbox"][name="selected[]"]:checked');
        
        if (selectedItems.length === 0) {
            this.showToast('Aucun élément sélectionné', 'warning');
            return;
        }

        const ids = Array.from(selectedItems).map(item => item.value);
        const confirmMessage = `Appliquer l'action "${action}" à ${ids.length} élément(s) ?`;
        
        if (!confirm(confirmMessage)) return;

        this.performBulkAction(action, ids);
    }

    async performBulkAction(action, ids) {
        try {
            this.showLoading();
            const response = await fetch('ajax/bulk-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    ids: ids
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(`Action "${action}" appliquée à ${ids.length} élément(s)`, 'success');
                this.refreshCurrentPage();
            } else {
                this.showToast(data.message || 'Erreur lors de l\'action groupée', 'error');
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            this.hideLoading();
        }
    }

    exportData(type, format) {
        const params = new URLSearchParams({
            type: type,
            format: format,
            timestamp: Date.now()
        });

        const url = `ajax/export-data.php?${params}`;
        
        const link = document.createElement('a');
        link.href = url;
        link.download = `prestacapi_${type}_${new Date().toISOString().split('T')[0]}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.showToast(`Export ${format.toUpperCase()} en cours...`, 'info');
    }

    handleFormSubmit(event) {
        const form = event.target;
        if (!form.dataset.ajax) return;

        event.preventDefault();
        this.submitFormAjax(form);
    }

    async submitFormAjax(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Envoi...';
        }

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.showToast(data.message || 'Opération réussie', 'success');
                
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    form.reset();
                }
            } else {
                this.showToast(data.message || 'Erreur lors de l\'opération', 'error');
                
                if (data.errors) {
                    this.displayFormErrors(form, data.errors);
                }
            }
        } catch (error) {
            this.showToast('Erreur de connexion', 'error');
            console.error('Erreur:', error);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.originalText || 'Envoyer';
            }
        }
    }

    displayFormErrors(form, errors) {
        form.querySelectorAll('.form-error').forEach(error => error.remove());
        
        Object.keys(errors).forEach(field => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.textContent = errors[field];
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    handleInputChange(event) {
        const input = event.target;
        
        if (input.type === 'checkbox' && input.name === 'select-all') {
            this.toggleAllCheckboxes(input.checked);
        } else if (input.type === 'checkbox' && input.name === 'selected[]') {
            this.updateBulkActions();
        }
    }

    toggleAllCheckboxes(checked) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="selected[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateBulkActions();
    }

    updateBulkActions() {
        const selectedItems = document.querySelectorAll('input[type="checkbox"][name="selected[]"]:checked');
        const bulkActions = document.querySelector('.bulk-actions');
        
        if (bulkActions) {
            if (selectedItems.length > 0) {
                bulkActions.classList.add('show');
                const countElement = bulkActions.querySelector('.selected-count');
                if (countElement) {
                    countElement.textContent = selectedItems.length;
                }
            } else {
                bulkActions.classList.remove('show');
            }
        }
    }

    initializeDataTables() {
        const tables = document.querySelectorAll('.data-table[data-sortable="true"]');
        tables.forEach(table => {
            this.makeSortable(table);
        });
    }

    makeSortable(table) {
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(table, header.dataset.sort);
            });
        });
    }

    sortTable(table, column) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAscending = table.dataset.sortDirection !== 'asc';
        
        rows.sort((a, b) => {
            const aValue = a.querySelector(`[data-${column}]`)?.dataset[column] || '';
            const bValue = b.querySelector(`[data-${column}]`)?.dataset[column] || '';
            
            if (isAscending) {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });
        
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
        
        table.dataset.sortDirection = isAscending ? 'asc' : 'desc';
        
        table.querySelectorAll('th[data-sort]').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        const sortedHeader = table.querySelector(`th[data-sort="${column}"]`);
        if (sortedHeader) {
            sortedHeader.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        }
    }

    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            this.createTooltip(element);
        });
    }

    createTooltip(element) {
        let tooltip = null;

        element.addEventListener('mouseenter', () => {
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = element.dataset.tooltip;
            document.body.appendChild(tooltip);

            const rect = element.getBoundingClientRect();
            tooltip.style.left = `${rect.left + rect.width / 2}px`;
            tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
            tooltip.style.transform = 'translateX(-50%)';
        });

        element.addEventListener('mouseleave', () => {
            if (tooltip) {
                tooltip.remove();
                tooltip = null;
            }
        });
    }

    initializeModals() {
        document.addEventListener('click', (event) => {
            if (event.target.dataset.modal) {
                event.preventDefault();
                this.openModal(event.target.dataset.modal);
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            const closeButtons = modal.querySelectorAll('[data-close]');
            closeButtons.forEach(button => {
                button.addEventListener('click', () => this.closeModal(modalId));
            });

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    this.closeModal(modalId);
                }
            });
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    initializeDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"], input[data-datepicker]');
        dateInputs.forEach(input => {
            if (!input.value && input.dataset.default) {
                input.value = input.dataset.default;
            }
        });
    }

    initializeFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (event) => {
                this.handleFilePreview(event.target);
            });
        });
    }

    handleFilePreview(input) {
        const previewContainer = document.getElementById(input.dataset.preview);
        if (!previewContainer) return;

        previewContainer.innerHTML = '';

        Array.from(input.files).forEach(file => {
            const preview = document.createElement('div');
            preview.className = 'file-preview';

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                preview.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'file-icon';
                icon.textContent = '📄';
                preview.appendChild(icon);
            }

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            preview.appendChild(fileName);

            previewContainer.appendChild(preview);
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('ajax/get-notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateNotifications(data.notifications, data.count);
            }
        } catch (error) {
            console.error('Erreur chargement notifications:', error);
        }
    }

    updateNotifications(notifications, count) {
        const badge = document.querySelector('.notification-badge');
        const list = document.querySelector('.notification-list');
        
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
        
        if (list) {
            list.innerHTML = notifications.map(notif => `
                <div class="notification-item ${notif.is_read ? '' : 'unread'}">
                    <div class="notification-content">
                        <div class="notification-title">${notif.title}</div>
                        <div class="notification-text">${notif.message}</div>
                        <div class="notification-time">${notif.time_ago}</div>
                    </div>
                </div>
            `).join('');
        }
    }

    startAutoRefresh() {
        setInterval(() => {
            this.loadNotifications();
            this.refreshStats();
        }, 30000);
    }

    async refreshStats() {
        const statsElements = document.querySelectorAll('[data-stat-url]');
        
        statsElements.forEach(async (element) => {
            try {
                const response = await fetch(element.dataset.statUrl);
                const data = await response.json();
                
                if (data.success) {
                    element.textContent = data.value;
                    
                    if (data.change) {
                        const changeElement = element.nextElementSibling;
                        if (changeElement && changeElement.classList.contains('stat-change')) {
                            changeElement.textContent = data.change;
                            changeElement.className = `stat-change ${data.change.startsWith('+') ? 'positive' : 'negative'}`;
                        }
                    }
                }
            } catch (error) {
                console.error('Erreur refresh stat:', error);
            }
        });
    }

    refreshCurrentPage() {
        const url = new URL(window.location);
        url.searchParams.set('_refresh', Date.now());
        window.location.href = url.toString();
    }

    handleBeforeUnload(event) {
        const forms = document.querySelectorAll('form[data-warn-unsaved]');
        for (const form of forms) {
            if (this.hasUnsavedChanges(form)) {
                event.preventDefault();
                event.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
                return event.returnValue;
            }
        }
    }

    hasUnsavedChanges(form) {
        const formData = new FormData(form);
        const originalData = form.dataset.originalData;
        
        if (!originalData) return false;
        
        return JSON.stringify(Object.fromEntries(formData)) !== originalData;
    }

    handleResize() {
        this.adjustLayout();
    }

    adjustLayout() {
        const sidebar = document.getElementById('adminSidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (window.innerWidth <= 768) {
            if (sidebar && !sidebar.classList.contains('mobile-hidden')) {
                sidebar.classList.add('mobile-hidden');
            }
        } else {
            if (sidebar && sidebar.classList.contains('mobile-hidden')) {
                sidebar.classList.remove('mobile-hidden');
            }
        }
    }

    showToast(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${this.getToastIcon(type)}</span>
                <span class="toast-message">${message}</span>
            </div>
            <button class="toast-close">×</button>
        `;
        
        const container = document.getElementById('toastContainer') || document.body;
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
        
        if (duration > 0) {
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }, duration);
        }
    }

    getToastIcon(type) {
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || icons.info;
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('show');
        }
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.adminApp = new AdminApp();
});

window.showToast = function(message, type = 'info') {
    if (window.adminApp) {
        window.adminApp.showToast(message, type);
    }
};

window.showLoading = function() {
    if (window.adminApp) {
        window.adminApp.showLoading();
    }
};

window.hideLoading = function() {
    if (window.adminApp) {
        window.adminApp.hideLoading();
    }
};

window.refreshStats = function() {
    if (window.adminApp) {
        window.adminApp.refreshStats();
    }
};