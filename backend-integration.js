/**
/**
 * Backend Integration Script
 * Handles API integration and authentication for the frontend
 */

class BackendIntegration {
    constructor() {
        this.apiBaseUrl = 'backend/api/';
        this.authToken = localStorage.getItem('auth_token');
        this.userData = JSON.parse(localStorage.getItem('user_data') || '{}');
        this.isAuthenticated = localStorage.getItem('isLoggedIn') === 'true';
        this.demoMode = localStorage.getItem('demoMode') === 'true';
        
        this.init();
    }
    
    async init() {
        console.log('BackendIntegration init called');
        // Check if user is authenticated
        await this.checkAuthStatus();
        
        // If not authenticated, redirect to login (except for login page)
        if (!this.isAuthenticated && !window.location.pathname.includes('login.php')) {
            this.redirectToLogin();
            return;
        }
        
        // Initialize user interface if authenticated
        if (this.isAuthenticated) {
            this.updateUserInterface();
            await this.loadDashboardData();
            this.loadNotifications();
        }
        
        // Setup event listeners
        console.log('Calling setupEventListeners');
        this.setupEventListeners();
        
        // Setup dropdown handlers
        console.log('Calling setupDropdownHandlers');
        // Use setTimeout to ensure DOM is fully ready
        setTimeout(() => {
            this.setupDropdownHandlers();
        }, 0);
    }
    
    redirectToLogin() {
        // Clear any invalid auth data
        this.clearAuthData();
        
        // Smooth redirect to login
        document.body.style.transition = 'opacity 0.3s ease-out';
        document.body.style.opacity = '0';
        
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 300);
    }
    
    async checkAuthStatus() {
        if (!this.authToken) {
            this.isAuthenticated = false;
            return;
        }
        
        // If in demo mode, validate demo token
        if (this.demoMode) {
            try {
                const tokenData = JSON.parse(atob(this.authToken));
                if (tokenData.expires > Date.now()) {
                    this.isAuthenticated = true;
                    return;
                } else {
                    this.clearAuthData();
                    this.isAuthenticated = false;
                    return;
                }
            } catch (error) {
                this.clearAuthData();
                this.isAuthenticated = false;
                return;
            }
        }
        
        // Try to verify with API
        try {
            const response = await this.makeRequest('auth.php?action=verify', 'GET');
            if (response.success) {
                this.isAuthenticated = true;
                // Refresh user data
                await this.loadUserProfile();
            } else {
                this.isAuthenticated = false;
                this.clearAuthData();
            }
        } catch (error) {
            console.warn('Auth check failed, checking local storage:', error.message);
            // Fallback: consider authenticated if we have valid stored data
            this.isAuthenticated = this.userData && Object.keys(this.userData).length > 0;
        }
    }
    
    clearAuthData() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('loginTime');
        localStorage.removeItem('demoMode');
        this.authToken = null;
        this.userData = {};
        this.isAuthenticated = false;
        this.demoMode = false;
    }
    
    async loadDashboardData() {
        try {
            if (this.demoMode) {
                // Load demo data
                this.loadDemoData();
                return;
            }
            
            // Load comprehensive dashboard data from multiple endpoints
            await this.loadAllDashboardData();
        } catch (error) {
            console.warn('Failed to load dashboard data, using demo data:', error.message);
            this.loadDemoData();
        }
    }
    
    async loadAllDashboardData() {
        try {
            // Load statistics
            const statsResponse = await this.makeRequest('dashboard.php?action=stats', 'GET');
            if (statsResponse.success) {
                this.updateDashboardStats(statsResponse.data);
            }
            
            // Load campaigns data
            const campaignsResponse = await this.makeRequest('dashboard.php?action=campaigns', 'GET');
            if (campaignsResponse.success && window.dashboard) {
                window.dashboard.data.campaigns = campaignsResponse.data;
                if (typeof window.dashboard.renderCampaignTable === 'function') {
                    window.dashboard.renderCampaignTable();
                }
            }
            
            // Load events data
            const eventsResponse = await this.makeRequest('dashboard.php?action=events', 'GET');
            if (eventsResponse.success && window.dashboard) {
                window.dashboard.data.events = eventsResponse.data;
                if (typeof window.dashboard.renderEventsList === 'function') {
                    window.dashboard.renderEventsList();
                }
            }
            
            // Load performance data for charts
            const performanceResponse = await this.makeRequest('dashboard.php?action=performance', 'GET');
            if (performanceResponse.success && window.dashboard) {
                window.dashboard.updatePerformanceData(performanceResponse.data);
                if (typeof window.dashboard.updateCharts === 'function') {
                    window.dashboard.updateCharts();
                }
            }
            
        } catch (error) {
            console.error('Error loading comprehensive dashboard data:', error);
            this.loadDemoData();
        }
    }
    
    loadDemoData() {
        // Demo data for fallback
        const demoStats = {
            active_campaigns: 5,
            upcoming_events: 12,
            content_items: 47,
            survey_responses: 1243
        };
        
        this.updateDashboardStats(demoStats);
    }
    
    updateDashboardStats(stats) {
        // Update dashboard statistics - handle both API naming conventions
        const mappings = [
            { api: ['activeCampaigns', 'active_campaigns'], selector: '.stat-card:nth-child(1) .stat-value' },
            { api: ['upcomingEvents', 'upcoming_events'], selector: '.stat-card:nth-child(2) .stat-value' },
            { api: ['contentItems', 'content_items'], selector: '.stat-card:nth-child(3) .stat-value' },
            { api: ['surveyResponses', 'survey_responses'], selector: '.stat-card:nth-child(4) .stat-value' }
        ];
        
        mappings.forEach(mapping => {
            const element = document.querySelector(mapping.selector);
            if (element) {
                // Try different possible API key names
                let value = null;
                for (const apiKey of mapping.api) {
                    if (stats[apiKey] !== undefined) {
                        value = stats[apiKey];
                        break;
                    }
                }
                if (value !== null) {
                    element.textContent = value;
                }
            }
        });
        
        // Also update the global dashboard manager if it exists
        if (window.dashboard && window.dashboard.data) {
            Object.assign(window.dashboard.data.stats, stats);
            if (typeof window.dashboard.updateStats === 'function') {
                window.dashboard.updateStats();
            }
        }
    }
    
    updateUserInterface() {
        // Update navbar user info
        const navbarUserName = document.getElementById('navbarUserName');
        const navbarUserAvatar = document.getElementById('navbarUserAvatar');
        const userName = document.getElementById('userName');
        const userRole = document.getElementById('userRole');
        const userEmail = document.getElementById('userEmail');
        
        if (this.userData.first_name && this.userData.last_name) {
            const fullName = `${this.userData.first_name} ${this.userData.last_name}`;
            if (navbarUserName) navbarUserName.textContent = fullName;
            if (userName) userName.textContent = fullName;
            
            // Update avatar initials
            const initials = this.userData.avatar || `${this.userData.first_name[0]}${this.userData.last_name[0]}`;
            if (navbarUserAvatar) navbarUserAvatar.textContent = initials;
            
            // Update dropdown avatar
            const dropdownAvatar = document.getElementById('dropdownUserAvatar');
            if (dropdownAvatar) dropdownAvatar.textContent = initials;
        } else if (this.userData.name) {
            // Fallback for simple name format
            if (navbarUserName) navbarUserName.textContent = this.userData.name;
            if (userName) userName.textContent = this.userData.name;
        }
        
        if (this.userData.role) {
            if (userRole) userRole.textContent = this.userData.role;
        }
        
        if (this.userData.email) {
            if (userEmail) userEmail.textContent = this.userData.email;
        }
    }
    
    async loadNotifications() {
        try {
            const response = await this.makeRequest('dashboard.php?action=notifications', 'GET');
            if (response.success) {
                this.updateNotificationUI(response.data);
            }
        } catch (error) {
            console.warn('Failed to load notifications:', error.message);
            // Load demo notifications as fallback
            this.loadDemoNotifications();
        }
    }
    
    updateNotificationUI(notifications) {
        const notificationList = document.getElementById('notificationList');
        const notificationBadge = document.getElementById('notificationBadge');
        
        if (!notificationList) return;
        
        // Count unread notifications
        const unreadCount = notifications.filter(n => !n.is_read).length;
        if (notificationBadge) {
            notificationBadge.textContent = unreadCount;
            notificationBadge.style.display = unreadCount > 0 ? 'block' : 'none';
        }
        
        // Render notifications
        notificationList.innerHTML = notifications.map(notification => `
            <div class=\"notification-item ${notification.is_read ? 'read' : 'unread'} ${notification.type}\" 
                 data-notification-id=\"${notification.id}\">
                <h4 class=\"notification-title\">${escapeHtml(notification.title)}</h4>
                <p class=\"notification-message\">${escapeHtml(notification.message)}</p>
                <span class=\"notification-time\">${notification.time_ago || notification.formatted_date}</span>
            </div>
        `).join('');
        
        // Add click handlers for notifications
        notificationList.querySelectorAll('.notification-item:not(.read)').forEach(item => {
            item.addEventListener('click', () => {
                this.markNotificationAsRead(item.dataset.notificationId);
                item.classList.add('read');
                item.classList.remove('unread');
            });
        });
    }
    
    loadDemoNotifications() {
        const demoNotifications = [
            {
                id: 1,
                title: 'New Campaign Created',
                message: 'Road Safety Awareness campaign has been created and is ready for review.',
                type: 'success',
                is_read: false,
                time_ago: '2 hours ago'
            },
            {
                id: 2,
                title: 'Event Registration Update',
                message: 'Road Safety Workshop has 45 new registrations.',
                type: 'info',
                is_read: false,
                time_ago: '4 hours ago'
            },
            {
                id: 3,
                title: 'Content Approval Needed',
                message: 'Fire Safety Video is pending your approval.',
                type: 'warning',
                is_read: true,
                time_ago: '1 day ago'
            }
        ];
        
        this.updateNotificationUI(demoNotifications);
    }
    
    async markNotificationAsRead(notificationId) {
        try {
            await this.makeRequest('dashboard.php?action=mark-notification-read', 'POST', {
                notification_id: notificationId
            });
        } catch (error) {
            console.warn('Failed to mark notification as read:', error.message);
        }
    }
    
    setupEventListeners() {
        // Logout functionality
        const logoutButtons = document.querySelectorAll('[data-action="logout"]');
        logoutButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        });
        
        // Profile management
        const profileButtons = document.querySelectorAll('[data-action="view-profile"], [data-action="edit-profile"]');
        profileButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.openProfileModal();
            });
        });
    }
    
    async logout() {
        try {
            // Show confirmation
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            // Try to logout via AJAX
            if (!this.demoMode) {
                await this.makeRequest('', 'POST', {action: 'logout'});
            }
            
            // Clear local data
            this.clearAuthData();
            
            // Show logout message
            this.showNotification('Logged out successfully', 'success');
            
            // Redirect to login
            setTimeout(() => {
                this.redirectToLogin();
            }, 1000);
            
        } catch (error) {
            console.warn('Logout API failed, proceeding with local logout:', error.message);
            // Clear local data anyway
            this.clearAuthData();
            this.redirectToLogin();
        }
    }
    
    openProfileModal() {
        const profileModal = document.getElementById('profileModal');
        if (profileModal) {
            profileModal.style.display = 'flex';
            this.populateProfileForm();
        }
    }
    
    populateProfileForm() {
        // Populate form fields with user data
        const fields = {
            'firstName': this.userData.first_name,
            'lastName': this.userData.last_name,
            'email': this.userData.email,
            'phone': this.userData.phone,
            'role': this.userData.role,
            'department': this.userData.department,
            'location': this.userData.location,
            'timezone': this.userData.timezone,
            'language': this.userData.language
        };
        
        Object.keys(fields).forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && fields[fieldId]) {
                field.value = fields[fieldId];
            }
        });
        
        // Populate checkboxes
        const checkboxes = {
            'emailNotifications': this.userData.email_notifications,
            'pushNotifications': this.userData.push_notifications,
            'smsNotifications': this.userData.sms_notifications
        };
        
        Object.keys(checkboxes).forEach(checkboxId => {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.checked = checkboxes[checkboxId];
            }
        });
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: white;
            color: #1f2937;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid ${colors[type]};
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;
        
        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="color: ${colors[type]}; font-size: 1.25rem;"></i>
            <span style="flex: 1;">${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
    
    setupDropdownHandlers() {
        console.log('setupDropdownHandlers called');
        // Profile dropdown toggle
        const userProfileToggle = document.getElementById('userProfileToggle');
        const profileDropdown = document.getElementById('profileDropdown');
        
        console.log('Profile toggle elements:', userProfileToggle, profileDropdown);
        
        if (userProfileToggle && profileDropdown) {
            userProfileToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                console.log('Profile toggle clicked');
                profileDropdown.classList.toggle('show');
            });
        }
        
        // Notification panel toggle
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationPanel = document.getElementById('notificationPanel');
        
        if (notificationIcon && notificationPanel) {
            notificationIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationPanel.classList.toggle('show');
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            if (profileDropdown) profileDropdown.classList.remove('show');
            if (notificationPanel) notificationPanel.classList.remove('show');
        });
        
        // Profile modal handlers
        const profileModal = document.getElementById('profileModal');
        
        // Logout button
        const logoutButton = document.getElementById('logoutButton');
        console.log('Logout button element:', logoutButton);
        if (logoutButton) {
            // Remove any existing event listeners to prevent duplicates
            logoutButton.removeEventListener('click', this.handleLogoutClick);
            // Store the event handler as a class property for proper binding
            this.handleLogoutClick = (e) => {
                e.preventDefault();
                console.log('Logout button clicked');
                this.directLogout();
            };
            logoutButton.addEventListener('click', this.handleLogoutClick);
            // Mark that we've attached the event listener
            logoutButton.setAttribute('data-event-attached', 'true');
        } else {
            console.error('Logout button not found');
        }
        
        // Mark all notifications as read
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }
    }
    
    showProfileModal(editMode = false) {
        const modal = document.getElementById('profileModal');
        const saveBtn = document.getElementById('saveProfileBtn');
        const form = document.getElementById('profileForm');
        
        if (!modal || !form) return;
        
        // Populate form with current user data
        if (this.userData.first_name) {
            const firstNameInput = form.querySelector('#firstName');
            if (firstNameInput) firstNameInput.value = this.userData.first_name;
        }
        
        if (this.userData.last_name) {
            const lastNameInput = form.querySelector('#lastName');
            if (lastNameInput) lastNameInput.value = this.userData.last_name;
        }
        
        if (this.userData.email) {
            const emailInput = form.querySelector('#email');
            if (emailInput) emailInput.value = this.userData.email;
        }
        
        // Set form to edit or view mode
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = !editMode;
        });
        
        if (saveBtn) {
            saveBtn.style.display = editMode ? 'block' : 'none';
        }
        
        modal.classList.add('show');
    }
    
    clearAuthData() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        localStorage.removeItem('isLoggedIn');
        localStorage.removeItem('loginTime');
        localStorage.removeItem('demoMode');
        this.authToken = null;
        this.userData = {};
        this.isAuthenticated = false;
        this.demoMode = false;
    }
    
    async markAllNotificationsAsRead() {
        // This would require a batch API endpoint
        const unreadNotifications = document.querySelectorAll('.notification-item.unread');
        
        unreadNotifications.forEach(item => {
            item.classList.remove('unread');
        });
        
        // Update badge
        const badge = document.getElementById('notificationBadge');
        if (badge) badge.style.display = 'none';
    }
    
    async makeRequest(endpoint, method = 'GET', data = null) {
        const url = `${this.apiBaseUrl}${endpoint}`;
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Add authentication token if available
        if (this.authToken) {
            options.headers['Authorization'] = `Bearer ${this.authToken}`;
        }
        
        // Add data for POST requests
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            // Handle authentication errors
            if (result.error && result.error.toLowerCase().includes('authentication')) {
                this.clearAuthData();
                window.location.href = 'login.php';
                return;
            }
            
            return result;
        } catch (error) {
            console.error(`API request failed (${method} ${url}):`, error);
            throw error;
        }
    }
    
    // Method to refresh auth token if needed
    async refreshAuthToken() {
        try {
            const response = await this.makeRequest('auth.php?action=refresh', 'POST');
            if (response.success && response.token) {
                this.authToken = response.token;
                localStorage.setItem('auth_token', this.authToken);
                return true;
            }
        } catch (error) {
            console.warn('Token refresh failed:', error.message);
        }
        return false;
    }
    
    // Direct logout function that can be called from HTML
    async directLogout() {
        console.log('Logout function called');
        // Show confirmation
        if (!confirm('Are you sure you want to logout?')) {
            console.log('Logout cancelled by user');
            return;
        }
        
        try {
            console.log('Attempting to logout');
            // Try to logout via AJAX (direct request to current page)
            if (!this.demoMode) {
                // First try a simple approach
                console.log('Trying simple logout approach');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'logout');
                
                const options = {
                    method: 'POST',
                    body: formData
                };
                
                console.log('Sending logout request to:', window.location.href);
                const response = await fetch(window.location.href, options);
                
                console.log('Logout response status:', response.status);
                console.log('Logout response headers:', [...response.headers.entries()]);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Try to parse response as JSON
                let result;
                try {
                    result = await response.json();
                    console.log('Logout response (JSON):', result);
                } catch (parseError) {
                    // If JSON parsing fails, try to get text
                    const text = await response.text();
                    console.log('Logout response (text):', text);
                    result = {success: true, message: 'Logged out successfully'};
                }
                
                if (!result.success) {
                    throw new Error('Logout failed');
                }
            }
            
            // Clear local data
            console.log('Clearing local data');
            this.clearAuthData();
            
            // Show logout message
            this.showNotification('Logged out successfully', 'success');
            
            // Redirect to login
            console.log('Redirecting to login page');
            setTimeout(() => {
                this.redirectToLogin();
            }, 1000);
        } catch (error) {
            console.error('Logout failed:', error);
            console.warn('Logout failed, proceeding with local logout:', error.message);
            // Clear local data anyway
            this.clearAuthData();
            this.redirectToLogin();
        }
    }
    
    // Test function for debugging
    testLogout() {
        console.log('Test logout function called');
        this.directLogout();
    }
}

// Initialize backend integration when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.backendIntegration = new BackendIntegration();
    
    // Add global function for testing
    window.testLogout = function() {
        if (window.backendIntegration) {
            window.backendIntegration.testLogout();
        } else {
            console.error('Backend integration not initialized');
        }
    };
    
    // Add direct logout function for testing
    window.directLogout = function() {
        if (window.backendIntegration) {
            console.log('Calling directLogout from global function');
            window.backendIntegration.directLogout();
        } else {
            console.error('Backend integration not initialized');
        }
    };
    
    // Fallback: Attach logout event listener after a short delay
    setTimeout(() => {
        const logoutButton = document.getElementById('logoutButton');
        console.log('Fallback: Logout button element:', logoutButton);
        if (logoutButton && !logoutButton.hasAttribute('data-event-attached')) {
            // Use the same handler as in setupDropdownHandlers if available
            if (window.backendIntegration && typeof window.backendIntegration.handleLogoutClick === 'function') {
                logoutButton.addEventListener('click', window.backendIntegration.handleLogoutClick);
            } else {
                logoutButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('Fallback: Logout button clicked');
                    if (window.backendIntegration) {
                        window.backendIntegration.directLogout();
                    }
                });
            }
            logoutButton.setAttribute('data-event-attached', 'true');
        }
    }, 1000);
});

// Utility function to escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}