/**
 * Dynamic UI Updates
 * Handles real-time UI updates without page reload
 */

class DynamicUpdates {
    constructor() {
        this.updateInterval = 10000; // 10 seconds
        this.timers = {};
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'success', duration = 3000) {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.dynamic-toast');
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = 'dynamic-toast';
        
        const colors = {
            success: { bg: '#10b981', icon: 'fa-check-circle' },
            error: { bg: '#ef4444', icon: 'fa-exclamation-circle' },
            warning: { bg: '#f59e0b', icon: 'fa-exclamation-triangle' },
            info: { bg: '#3b82f6', icon: 'fa-info-circle' }
        };

        const color = colors[type] || colors.info;

        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${color.bg};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95em;
            font-weight: 500;
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        `;

        toast.innerHTML = `
            <i class="fas ${color.icon}" style="font-size: 1.3em;"></i>
            <span>${message}</span>
        `;

        // Add animation CSS if not exists
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    /**
     * Update element content dynamically
     */
    updateElement(selector, content, fade = true) {
        const element = document.querySelector(selector);
        if (!element) return;

        if (fade) {
            element.style.transition = 'opacity 0.3s ease';
            element.style.opacity = '0';
            
            setTimeout(() => {
                element.innerHTML = content;
                element.style.opacity = '1';
            }, 300);
        } else {
            element.innerHTML = content;
        }
    }

    /**
     * Refresh data from API without reload
     */
    async refreshData(url, callback, showLoading = true) {
        let loadingEl;
        
        if (showLoading) {
            loadingEl = this.showLoadingSpinner();
        }

        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (callback) {
                callback(data);
            }
            
            return data;
        } catch (error) {
            console.error('Refresh error:', error);
            this.showToast('Failed to refresh data', 'error');
            throw error;
        } finally {
            if (loadingEl) {
                loadingEl.remove();
            }
        }
    }

    /**
     * Show loading spinner
     */
    showLoadingSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9998;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            text-align: center;
        `;

        spinner.innerHTML = `
            <div style="width: 50px; height: 50px; border: 4px solid #e2e8f0; border-top: 4px solid #5b7ac7; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
            <p style="color: #4a5568; font-weight: 500; margin: 0;">Loading...</p>
        `;

        // Add spin animation
        if (!document.getElementById('spinner-animation')) {
            const style = document.createElement('style');
            style.id = 'spinner-animation';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(spinner);
        return spinner;
    }

    /**
     * Start auto-refresh for specific content
     */
    startAutoRefresh(name, url, callback, interval = null) {
        const refreshInterval = interval || this.updateInterval;

        // Clear existing timer
        if (this.timers[name]) {
            clearInterval(this.timers[name]);
        }

        // Initial load
        this.refreshData(url, callback, false);

        // Set up interval
        this.timers[name] = setInterval(() => {
            this.refreshData(url, callback, false);
        }, refreshInterval);
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh(name) {
        if (this.timers[name]) {
            clearInterval(this.timers[name]);
            delete this.timers[name];
        }
    }

    /**
     * Stop all auto-refreshes
     */
    stopAllAutoRefresh() {
        Object.keys(this.timers).forEach(name => {
            this.stopAutoRefresh(name);
        });
    }

    /**
     * Animate element update
     */
    animateUpdate(element, type = 'highlight') {
        if (!element) return;

        switch(type) {
            case 'highlight':
                element.style.transition = 'background-color 0.5s ease';
                const originalBg = element.style.backgroundColor;
                element.style.backgroundColor = '#fef3c7';
                setTimeout(() => {
                    element.style.backgroundColor = originalBg;
                }, 500);
                break;
                
            case 'bounce':
                element.style.animation = 'bounce 0.5s ease';
                setTimeout(() => {
                    element.style.animation = '';
                }, 500);
                break;
                
            case 'fade':
                element.style.transition = 'opacity 0.3s ease';
                element.style.opacity = '0';
                setTimeout(() => {
                    element.style.opacity = '1';
                }, 300);
                break;
        }

        // Add bounce animation if not exists
        if (type === 'bounce' && !document.getElementById('bounce-animation')) {
            const style = document.createElement('style');
            style.id = 'bounce-animation';
            style.textContent = `
                @keyframes bounce {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-10px); }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Update table row dynamically
     */
    updateTableRow(tableId, rowId, newData) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const row = table.querySelector(`tr[data-id="${rowId}"]`);
        if (!row) return;

        // Highlight the row
        this.animateUpdate(row, 'highlight');

        // Update cells
        Object.keys(newData).forEach(key => {
            const cell = row.querySelector(`[data-field="${key}"]`);
            if (cell) {
                cell.textContent = newData[key];
            }
        });
    }

    /**
     * Add new table row dynamically
     */
    addTableRow(tableBodyId, rowData, position = 'top') {
        const tbody = document.getElementById(tableBodyId);
        if (!tbody) return;

        const row = document.createElement('tr');
        row.innerHTML = rowData;
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.5s ease';

        if (position === 'top') {
            tbody.insertBefore(row, tbody.firstChild);
        } else {
            tbody.appendChild(row);
        }

        // Fade in
        setTimeout(() => {
            row.style.opacity = '1';
        }, 10);

        this.animateUpdate(row, 'highlight');
    }

    /**
     * Remove table row dynamically
     */
    removeTableRow(rowElement) {
        if (!rowElement) return;

        rowElement.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        rowElement.style.opacity = '0';
        rowElement.style.transform = 'translateX(-20px)';

        setTimeout(() => {
            rowElement.remove();
        }, 300);
    }

    /**
     * Update counter/badge dynamically
     */
    updateCounter(selector, newValue, animate = true) {
        const element = document.querySelector(selector);
        if (!element) return;

        const oldValue = parseInt(element.textContent) || 0;

        if (animate && oldValue !== newValue) {
            this.animateCounterChange(element, oldValue, newValue);
        } else {
            element.textContent = newValue;
        }
    }

    /**
     * Animate counter change
     */
    animateCounterChange(element, oldValue, newValue) {
        const duration = 500;
        const steps = 20;
        const increment = (newValue - oldValue) / steps;
        let current = oldValue;
        let step = 0;

        const timer = setInterval(() => {
            step++;
            current += increment;

            if (step >= steps) {
                element.textContent = newValue;
                clearInterval(timer);
            } else {
                element.textContent = Math.round(current);
            }
        }, duration / steps);

        this.animateUpdate(element, 'bounce');
    }

    /**
     * Check for updates periodically
     */
    checkForUpdates(url, lastUpdateTime, callback) {
        fetch(url + '?since=' + lastUpdateTime)
            .then(response => response.json())
            .then(data => {
                if (data.hasUpdates) {
                    callback(data);
                    this.showToast('New updates available!', 'info', 2000);
                }
            })
            .catch(error => {
                console.error('Update check failed:', error);
            });
    }
}

// Initialize
const dynamicUpdates = new DynamicUpdates();
window.dynamicUpdates = dynamicUpdates;

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    dynamicUpdates.stopAllAutoRefresh();
});
