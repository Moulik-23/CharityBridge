/**
 * Session State Manager
 * Ensures only one user can be logged in per browser
 * Handles dynamic session updates without page reload
 */

class SessionManager {
    constructor() {
        this.sessionKey = 'charitybridge_active_session';
        this.heartbeatInterval = 30000; // 30 seconds
        this.heartbeatTimer = null;
        this.init();
    }

    init() {
        // Check for existing session on page load
        this.checkExistingSession();
        
        // Start heartbeat to keep session alive
        this.startHeartbeat();
        
        // Listen for storage events (other tabs)
        window.addEventListener('storage', (e) => this.handleStorageChange(e));
        
        // Handle tab/window close
        window.addEventListener('beforeunload', () => this.handleBeforeUnload());
    }

    /**
     * Create a new session
     */
    createSession(userType, userId, userName) {
        const session = {
            userType: userType,
            userId: userId,
            userName: userName,
            timestamp: Date.now(),
            tabId: this.generateTabId()
        };

        // Store in localStorage
        localStorage.setItem(this.sessionKey, JSON.stringify(session));
        
        // Store in sessionStorage (tab-specific)
        sessionStorage.setItem('charitybridge_tab_id', session.tabId);
        
        return session;
    }

    /**
     * Check if another user is logged in
     */
    checkExistingSession() {
        const storedSession = localStorage.getItem(this.sessionKey);
        const currentTabId = sessionStorage.getItem('charitybridge_tab_id');

        if (storedSession) {
            const session = JSON.parse(storedSession);
            
            // If this tab doesn't match the active session, show warning
            if (currentTabId !== session.tabId) {
                this.handleSessionConflict(session);
            }
        }
    }

    /**
     * Handle when another user tries to log in
     */
    handleSessionConflict(existingSession) {
        // Show modal only (no browser confirm dialog)
        this.showSessionConflictModal(existingSession);
    }

    /**
     * Show session conflict modal
     */
    showSessionConflictModal(session) {
        // Create modal if doesn't exist
        let modal = document.getElementById('session-conflict-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'session-conflict-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="text-align: center;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3em; color: #f59e0b; margin-bottom: 20px;"></i>
                        <h2 style="color: #1a202c; margin-bottom: 15px;">Session Conflict Detected</h2>
                        <p style="color: #4a5568; margin-bottom: 10px;">
                            <strong>${session.userName}</strong> (${session.userType}) is already logged in.
                        </p>
                        <p style="color: #6b7280; font-size: 0.9em; margin-bottom: 25px;">
                            Only one user can be active at a time in this browser.
                        </p>
                        <div style="display: flex; gap: 12px; justify-content: center;">
                            <button id="force-logout-btn" style="padding: 12px 24px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                Logout Other User
                            </button>
                            <button id="cancel-session-btn" style="padding: 12px 24px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add event listeners
            document.getElementById('force-logout-btn').addEventListener('click', () => {
                this.forceLogout();
                window.location.reload();
            });
            
            document.getElementById('cancel-session-btn').addEventListener('click', () => {
                window.location.href = '/CharityBridge2/index.html';
            });
        } else {
            modal.style.display = 'flex';
        }
    }

    /**
     * Force logout of existing session
     */
    forceLogout() {
        localStorage.removeItem(this.sessionKey);
        sessionStorage.clear();
        
        // Broadcast to other tabs
        localStorage.setItem('charitybridge_force_logout', Date.now().toString());
    }

    /**
     * Start heartbeat to keep session alive
     */
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            this.updateHeartbeat();
        }, this.heartbeatInterval);
    }

    /**
     * Update session heartbeat
     */
    updateHeartbeat() {
        const storedSession = localStorage.getItem(this.sessionKey);
        const currentTabId = sessionStorage.getItem('charitybridge_tab_id');

        if (storedSession) {
            const session = JSON.parse(storedSession);
            
            // Only update if this is the active tab
            if (currentTabId === session.tabId) {
                session.timestamp = Date.now();
                localStorage.setItem(this.sessionKey, JSON.stringify(session));
            }
        }
    }

    /**
     * Handle storage changes from other tabs
     */
    handleStorageChange(event) {
        // Handle force logout
        if (event.key === 'charitybridge_force_logout') {
            alert('You have been logged out from another tab.');
            this.performLogout();
        }
        
        // Handle session changes
        if (event.key === this.sessionKey) {
            const currentTabId = sessionStorage.getItem('charitybridge_tab_id');
            
            if (event.newValue) {
                const newSession = JSON.parse(event.newValue);
                
                // If session changed and not this tab, handle conflict
                if (newSession.tabId !== currentTabId) {
                    alert('Another user has logged in. You will be logged out.');
                    this.performLogout();
                }
            } else {
                // Session was removed
                this.performLogout();
            }
        }
    }

    /**
     * Handle before unload (tab close)
     */
    handleBeforeUnload() {
        const storedSession = localStorage.getItem(this.sessionKey);
        const currentTabId = sessionStorage.getItem('charitybridge_tab_id');

        if (storedSession) {
            const session = JSON.parse(storedSession);
            
            // Only clear if this is the active tab
            if (currentTabId === session.tabId) {
                // Don't clear on navigation, only on actual close
                // This is handled by session timeout on server
            }
        }
    }

    /**
     * Perform logout
     */
    performLogout() {
        // Clear session storage
        sessionStorage.clear();
        
        // Redirect to appropriate logout based on URL
        const path = window.location.pathname;
        
        if (path.includes('/admin/')) {
            window.location.href = '/CharityBridge2/admin/backend/logout.php';
        } else if (path.includes('/donor/')) {
            window.location.href = '/CharityBridge2/donor/backend/donor_logout.php';
        } else if (path.includes('/ngo/')) {
            window.location.href = '/CharityBridge2/ngo/backend/logout.php';
        } else if (path.includes('/restaurant/')) {
            window.location.href = '/CharityBridge2/restaurant/backend/logout.php';
        } else if (path.includes('/volunteer/')) {
            window.location.href = '/CharityBridge2/volunteer/logout.php';
        } else {
            window.location.href = '/CharityBridge2/index.html';
        }
    }

    /**
     * Get current session
     */
    getCurrentSession() {
        const storedSession = localStorage.getItem(this.sessionKey);
        return storedSession ? JSON.parse(storedSession) : null;
    }

    /**
     * Clear session
     */
    clearSession() {
        localStorage.removeItem(this.sessionKey);
        sessionStorage.clear();
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
        }
    }

    /**
     * Generate unique tab ID
     */
    generateTabId() {
        return 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        const storedSession = localStorage.getItem(this.sessionKey);
        const currentTabId = sessionStorage.getItem('charitybridge_tab_id');

        if (storedSession && currentTabId) {
            const session = JSON.parse(storedSession);
            return session.tabId === currentTabId;
        }
        return false;
    }
}

// Initialize session manager
const sessionManager = new SessionManager();

// Export for use in other scripts
window.sessionManager = sessionManager;
