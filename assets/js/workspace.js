/**
 * Personal Workspace JavaScript
 * Handles claiming, completion, and real-time updates
 */

class WorkspaceManager {
    constructor() {
        this.currentTab = 'available';
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadTabData('available');
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Tab switching
        document.querySelectorAll('#workspaceTabs button[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const tabId = e.target.getAttribute('data-bs-target').replace('#', '');
                this.currentTab = tabId;
                this.loadTabData(tabId);
            });
        });

        // Refresh button
        document.getElementById('refresh-tabs').addEventListener('click', () => {
            this.refreshCurrentTab();
        });
    }

    startAutoRefresh() {
        // Refresh current tab every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadTabData(this.currentTab, false); // Silent refresh
        }, 30000);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    refreshCurrentTab() {
        this.loadTabData(this.currentTab, true); // Show loading
    }

    async loadTabData(tab, showLoading = true) {
        if (showLoading) {
            this.showTabLoading(tab);
        }

        try {
            const response = await fetch(`${BASE_URL}/workspace/tab-data/${tab}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
            });

            const data = await response.json();

            if (data.success) {
                this.renderTabItems(tab, data.items);
                this.updateTabCount(tab, data.count);
            } else {
                this.showError(data.message || 'Failed to load data');
            }
        } catch (error) {
            console.error('Error loading tab data:', error);
            this.showError('Network error occurred');
        } finally {
            this.hideTabLoading(tab);
        }
    }

    showTabLoading(tab) {
        document.getElementById(`${tab}-loading`).classList.remove('d-none');
        document.getElementById(`${tab}-items`).classList.add('d-none');
        document.getElementById(`${tab}-empty`).classList.add('d-none');
    }

    hideTabLoading(tab) {
        document.getElementById(`${tab}-loading`).classList.add('d-none');
        document.getElementById(`${tab}-items`).classList.remove('d-none');
    }

    renderTabItems(tab, items) {
        const container = document.getElementById(`${tab}-items`);
        const emptyState = document.getElementById(`${tab}-empty`);

        if (items.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');
        container.innerHTML = items.map(item => this.createItemCard(item, tab)).join('');

        // Attach event listeners to buttons
        this.attachItemEventListeners();
    }

    createItemCard(item, tab) {
        const urgencyClass = this.getUrgencyClass(item);
        const urgencyBadge = this.getUrgencyBadge(item);
        const actionButton = this.getActionButton(item, tab);
        const completedDate = item.completed_at ? new Date(item.completed_at).toLocaleDateString() : '';

        return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card workspace-item-card ${urgencyClass}" data-item-id="${item.order_item_id}">
                    <div class="card-header d-flex justify-content-between align-items-center py-2">
                        <small class="text-muted">${item.order_number}</small>
                        ${urgencyBadge}
                    </div>
                    <div class="card-body">
                        <h6 class="card-title mb-2">${this.escapeHtml(item.customer_name)}</h6>
                        ${item.company_name ? `<div class="text-muted small mb-2">${this.escapeHtml(item.company_name)}</div>` : ''}
                        
                        <div class="mb-2">
                            <strong>${item.quantity}x ${item.product_type}</strong>
                            ${item.product_size !== 'N/A' ? ` (${item.product_size})` : ''}
                        </div>
                        
                        ${item.product_description ? `<div class="text-muted small mb-2">${this.escapeHtml(item.product_description)}</div>` : ''}
                        
                        <div class="row small text-muted">
                            <div class="col-6">
                                <strong>Method:</strong><br>
                                ${item.custom_method}
                            </div>
                            <div class="col-6">
                                <strong>Area:</strong><br>
                                ${item.custom_area || 'N/A'}
                            </div>
                        </div>
                        
                        ${item.due_date ? `
                            <div class="mt-2 small">
                                <i class="bi bi-calendar-event"></i> Due: ${new Date(item.due_date).toLocaleDateString()}
                            </div>
                        ` : ''}
                        
                        ${tab === 'completed' && completedDate ? `
                            <div class="mt-1 small text-success">
                                <i class="bi bi-check-circle"></i> Completed: ${completedDate}
                            </div>
                        ` : ''}
                        
                        ${item.note_item ? `
                            <div class="mt-2 small">
                                <i class="bi bi-sticky"></i> <em>${this.escapeHtml(item.note_item)}</em>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer">
                        ${actionButton}
                    </div>
                </div>
            </div>
        `;
    }

    getUrgencyClass(item) {
        if (item.is_overdue) return 'border-danger';
        if (item.is_rush) return 'border-warning';
        if (item.is_due_soon) return 'border-info';
        return '';
    }

    getUrgencyBadge(item) {
        if (item.is_overdue) return '<span class="badge bg-danger">OVERDUE</span>';
        if (item.is_rush) return '<span class="badge bg-warning text-dark">RUSH</span>';
        if (item.is_due_soon) return '<span class="badge bg-info">DUE SOON</span>';
        return '';
    }

    getActionButton(item, tab) {
        switch (tab) {
            case 'available':
                return `<button class="btn btn-primary btn-sm advance-btn" data-item-id="${item.order_item_id}">
                           <i class="bi bi-arrow-right"></i> Send Artwork
                        </button>`;
            case 'artwork-sent':
                return `<button class="btn btn-info btn-sm advance-btn" data-item-id="${item.order_item_id}">
                           <i class="bi bi-arrow-right"></i> Mark Approved
                        </button>`;
            case 'artwork-approved':
                return `<button class="btn btn-purple btn-sm advance-btn" data-item-id="${item.order_item_id}">
                           <i class="bi bi-arrow-right"></i> Start Nesting
                        </button>`;
            case 'nesting-done':
                return `<button class="btn btn-success btn-sm advance-btn" data-item-id="${item.order_item_id}">
                           <i class="bi bi-check-lg"></i> Complete
                        </button>`;
            case 'completed':
                return `<span class="text-success small">
                           <i class="bi bi-check-circle"></i> Completed
                        </span>`;
            default:
                return '';
        }
    }

    attachItemEventListeners() {
        // Advance buttons (move items to next stage)
        document.querySelectorAll('.advance-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = e.target.closest('button').getAttribute('data-item-id');
                this.advanceItem(itemId);
            });
        });
    }


    async advanceItem(itemId) {
        try {
            const response = await fetch(`${BASE_URL}/workspace/advance/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=${encodeURIComponent(CSRF_TOKEN)}`
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message || 'Item status updated successfully!');
                this.refreshAfterAction();
            } else {
                this.showError(data.message || 'Failed to update item status');
            }
        } catch (error) {
            console.error('Error advancing item:', error);
            this.showError('Network error occurred');
        }
    }


    refreshAfterAction() {
        // Only refresh current tab for better performance
        this.loadTabData(this.currentTab, false);
    }

    updateTabCount(tab, count) {
        const badge = document.getElementById(`${tab}-count`);
        if (badge) {
            badge.textContent = count;
            
            // Update badge color based on count
            badge.className = badge.className.replace(/bg-\w+/, '');
            if (count > 0) {
                switch (tab) {
                    case 'available':
                        badge.classList.add('bg-secondary');
                        break;
                    case 'artwork-sent':
                        badge.classList.add('bg-primary');
                        break;
                    case 'artwork-approved':
                        badge.classList.add('bg-info');
                        break;
                    case 'nesting-done':
                        badge.classList.add('bg-purple');
                        break;
                    case 'completed':
                        badge.classList.add('bg-success');
                        break;
                }
            } else {
                badge.classList.add('bg-light', 'text-dark');
            }
        }
    }

    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    showError(message) {
        this.showAlert(message, 'danger');
    }

    showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();
        
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${this.escapeHtml(message)}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        }, 5000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize workspace when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.workspaceManager = new WorkspaceManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.workspaceManager) {
        window.workspaceManager.stopAutoRefresh();
    }
});