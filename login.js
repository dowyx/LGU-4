// Login functionality
class LoginManager {
    constructor() {
        this.form = document.getElementById('loginForm');
        this.emailInput = document.getElementById('email');
        this.passwordInput = document.getElementById('password');
        this.passwordToggle = document.getElementById('passwordToggle');
        this.rememberMe = document.getElementById('rememberMe');
        this.demoBtn = document.getElementById('demoLoginBtn');
        this.loadingOverlay = document.getElementById('loadingOverlay');
        
        this.demoCredentials = {
            email: 'demo@safetycampaign.org',
            password: 'password'
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadRememberedCredentials();
        this.setupFormValidation();
    }
    
    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleLogin(e));
        
        // Password toggle
        this.passwordToggle.addEventListener('click', () => this.togglePassword());
        
        // Demo login
        this.demoBtn.addEventListener('click', () => this.loadDemoCredentials());
        
        // Input validation
        this.emailInput.addEventListener('blur', () => this.validateEmail());
        this.passwordInput.addEventListener('input', () => this.validatePassword());
        
        // Enter key on inputs
        [this.emailInput, this.passwordInput].forEach(input => {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.handleLogin(e);
                }
            });
        });
        
        // Forgot password link
        document.querySelector('.forgot-password').addEventListener('click', (e) => {
            e.preventDefault();
            this.showForgotPasswordModal();
        });
        
        // Contact administrator link
        document.querySelector('.signup-link').addEventListener('click', (e) => {
            e.preventDefault();
            this.showContactInfo();
        });
    }
    
    togglePassword() {
        const type = this.passwordInput.type === 'password' ? 'text' : 'password';
        this.passwordInput.type = type;
        
        const icon = this.passwordToggle.querySelector('i');
        icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
    
    loadDemoCredentials() {
        this.emailInput.value = this.demoCredentials.email;
        this.passwordInput.value = this.demoCredentials.password;
        
        // Add visual feedback
        this.emailInput.style.borderColor = 'var(--success-color)';
        this.passwordInput.style.borderColor = 'var(--success-color)';
        
        // Reset border colors after a moment
        setTimeout(() => {
            this.emailInput.style.borderColor = '';
            this.passwordInput.style.borderColor = '';
        }, 2000);
        
        this.showNotification('Demo credentials loaded!', 'success');
    }
    
    validateEmail() {
        const email = this.emailInput.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.showInputError(this.emailInput, 'Please enter a valid email address');
            return false;
        } else {
            this.clearInputError(this.emailInput);
            return true;
        }
    }
    
    validatePassword() {
        const password = this.passwordInput.value;
        
        if (password && password.length < 6) {
            this.showInputError(this.passwordInput, 'Password must be at least 6 characters');
            return false;
        } else {
            this.clearInputError(this.passwordInput);
            return true;
        }
    }
    
    showInputError(input, message) {
        input.style.borderColor = 'var(--danger-color)';
        
        // Remove existing error message
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.cssText = `
            color: var(--danger-color);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        `;
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i>${message}`;
        
        input.parentNode.appendChild(errorDiv);
    }
    
    clearInputError(input) {
        input.style.borderColor = '';
        const errorMessage = input.parentNode.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }
    
    setupFormValidation() {
        // Real-time validation
        this.emailInput.addEventListener('input', () => {
            if (this.emailInput.value) {
                this.validateEmail();
            }
        });
        
        this.passwordInput.addEventListener('input', () => {
            if (this.passwordInput.value) {
                this.validatePassword();
            }
        });
    }
    
    async handleLogin(e) {
        e.preventDefault();
        
        const email = this.emailInput.value.trim();
        const password = this.passwordInput.value.trim();
        
        // Validate inputs
        if (!email || !password) {
            this.showNotification('Please fill in all fields', 'error');
            return;
        }
        
        if (!this.validateEmail() || !this.validatePassword()) {
            this.showNotification('Please correct the errors above', 'error');
            return;
        }
        
        // Show loading
        this.showLoading(true);
        
        try {
            // Simulate API call
            await this.authenticateUser(email, password);
            
            // Save credentials if remember me is checked
            if (this.rememberMe.checked) {
                this.saveCredentials(email);
            } else {
                this.clearSavedCredentials();
            }
            
            // Redirect to dashboard with smooth transition
            this.showNotification('Login successful! Redirecting...', 'success');
            
            setTimeout(() => {
                // Add fade out effect before redirect
                document.body.style.transition = 'opacity 0.5s ease-out';
                document.body.style.opacity = '0';
                
                setTimeout(() => {
                    window.location.href = 'home.php';
                }, 500);
            }, 1000);
            
        } catch (error) {
            this.showNotification(error.message || 'Login failed. Please try again.', 'error');
        } finally {
            this.showLoading(false);
        }
    }
    
    async authenticateUser(email, password) {
        try {
            const response = await fetch('backend/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Store token and user data for future requests
                localStorage.setItem('auth_token', data.data.token);
                localStorage.setItem('user_data', JSON.stringify(data.data.user));
                
                // Store login status
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('loginTime', new Date().toISOString());
                
                return { success: true, user: data.data.user };
            } else {
                throw new Error(data.message || 'Login failed');
            }
        } catch (error) {
            console.warn('API not available, trying demo authentication:', error.message);
            
            // Fallback to demo authentication if API is not available
            return this.demoAuthentication(email, password);
        }
    }
    
    demoAuthentication(email, password) {
        // Demo users for fallback authentication
        const demoUsers = [
            { 
                email: 'demo@safetycampaign.org', 
                password: 'password', 
                id: 1,
                first_name: 'John', 
                last_name: 'Doe', 
                role: 'Campaign Manager',
                department: 'Public Safety',
                phone: '+1 (555) 123-4567',
                location: 'New York, NY',
                timezone: 'Eastern Time (ET)',
                language: 'English',
                email_notifications: true,
                push_notifications: true,
                sms_notifications: false,
                avatar: 'JD'
            },
            { 
                email: 'sarah@safetycampaign.org', 
                password: 'password', 
                id: 2,
                first_name: 'Sarah', 
                last_name: 'Johnson', 
                role: 'Content Creator',
                department: 'Communications',
                phone: '+1 (555) 234-5678',
                location: 'Los Angeles, CA',
                timezone: 'Pacific Time (PT)',
                language: 'English',
                email_notifications: true,
                push_notifications: true,
                sms_notifications: true,
                avatar: 'SJ'
            },
            { 
                email: 'michael@safetycampaign.org', 
                password: 'password', 
                id: 3,
                first_name: 'Michael', 
                last_name: 'Brown', 
                role: 'Data Analyst',
                department: 'Analytics',
                phone: '+1 (555) 345-6789',
                location: 'Chicago, IL',
                timezone: 'Central Time (CT)',
                language: 'English',
                email_notifications: true,
                push_notifications: false,
                sms_notifications: false,
                avatar: 'MB'
            },
            { 
                email: 'emily@safetycampaign.org', 
                password: 'password', 
                id: 4,
                first_name: 'Emily', 
                last_name: 'Davis', 
                role: 'Community Coordinator',
                department: 'Community Relations',
                phone: '+1 (555) 456-7890',
                location: 'Washington, DC',
                timezone: 'Eastern Time (ET)',
                language: 'English',
                email_notifications: true,
                push_notifications: true,
                sms_notifications: true,
                avatar: 'ED'
            },
            { 
                email: 'admin@safetycampaign.org', 
                password: 'password', 
                id: 5,
                first_name: 'Admin', 
                last_name: 'User', 
                role: 'Administrator',
                department: 'IT',
                phone: '+1 (555) 567-8901',
                location: 'Washington, DC',
                timezone: 'Eastern Time (ET)',
                language: 'English',
                email_notifications: true,
                push_notifications: true,
                sms_notifications: true,
                avatar: 'AU'
            }
        ];
        
        const user = demoUsers.find(u => u.email === email && u.password === password);
        if (user) {
            // Generate demo token
            const demoToken = btoa(JSON.stringify({
                user_id: user.id,
                timestamp: Date.now(),
                expires: Date.now() + (24 * 60 * 60 * 1000) // 24 hours
            }));
            
            // Store data similar to API response
            localStorage.setItem('auth_token', demoToken);
            localStorage.setItem('user_data', JSON.stringify(user));
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('loginTime', new Date().toISOString());
            localStorage.setItem('demoMode', 'true');
            
            return { success: true, user };
        }
        
        throw new Error('Invalid email or password');
    }
    
    showLoading(show) {
        this.loadingOverlay.classList.toggle('show', show);
    }
    
    saveCredentials(email) {
        localStorage.setItem('rememberedEmail', email);
        localStorage.setItem('rememberMe', 'true');
    }
    
    loadRememberedCredentials() {
        const rememberedEmail = localStorage.getItem('rememberedEmail');
        const rememberMe = localStorage.getItem('rememberMe') === 'true';
        
        if (rememberedEmail && rememberMe) {
            this.emailInput.value = rememberedEmail;
            this.rememberMe.checked = true;
        }
    }
    
    clearSavedCredentials() {
        localStorage.removeItem('rememberedEmail');
        localStorage.removeItem('rememberMe');
    }
    
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification-toast');
        existingNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification-toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const colors = {
            success: 'var(--success-color)',
            error: 'var(--danger-color)',
            warning: 'var(--warning-color)',
            info: 'var(--info-color)'
        };
        
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: white;
            color: var(--text-dark);
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
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
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: var(--text-light); cursor: pointer; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOutRight 0.3s ease-out';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }
    }
    
    showForgotPasswordModal() {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1002;
            backdrop-filter: blur(5px);
        `;
        
        modal.innerHTML = `
            <div class="modal-content" style="
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                max-width: 400px;
                width: 90%;
                box-shadow: var(--shadow-lg);
            ">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <i class="fas fa-key" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                    <h3 style="color: var(--text-dark); margin-bottom: 0.5rem;">Reset Password</h3>
                    <p style="color: var(--text-medium); font-size: 0.875rem;">
                        Password reset functionality is currently handled by your system administrator.
                    </p>
                </div>
                
                <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                    <h4 style="color: var(--text-dark); margin-bottom: 1rem; font-size: 1rem;">Contact Information:</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-envelope" style="color: var(--primary-color); width: 16px;"></i>
                            <span>admin@safetycampaign.org</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-phone" style="color: var(--primary-color); width: 16px;"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fas fa-clock" style="color: var(--primary-color); width: 16px;"></i>
                            <span>Mon-Fri, 9:00 AM - 5:00 PM</span>
                        </div>
                    </div>
                </div>
                
                <button onclick="this.closest('.modal-overlay').remove()" class="btn-primary" style="
                    width: 100%;
                    background: var(--primary-color);
                    color: white;
                    border: none;
                    padding: 0.75rem;
                    border-radius: 0.5rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                ">
                    <i class="fas fa-check"></i>
                    Got it
                </button>
            </div>
        `;
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.body.appendChild(modal);
    }
    
    showContactInfo() {
        this.showNotification('Please contact your system administrator at admin@safetycampaign.org for account access.', 'info');
    }
}

// Add CSS animations
const styles = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
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
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Initialize login manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new LoginManager();
});