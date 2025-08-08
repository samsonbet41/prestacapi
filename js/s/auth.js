class Auth {
    constructor(element, app) {
        this.element = element;
        this.app = app;
        this.isLoggedIn = document.body.classList.contains('logged-in');
        this.currentUser = this.getCurrentUser();
        
        this.init();
    }
    
    init() {
        this.setupLoginForm();
        this.setupRegisterForm();
        this.setupLogoutButtons();
        this.setupPasswordToggle();
        this.setupPasswordStrength();
        this.setupSocialAuth();
        this.checkSessionTimeout();
    }
    
    setupLoginForm() {
        const loginForm = document.getElementById('loginForm');
        if (!loginForm) return;
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin(loginForm);
        });
        
        const rememberMe = loginForm.querySelector('#rememberMe');
        if (rememberMe) {
            rememberMe.checked = localStorage.getItem('rememberMe') === 'true';
        }
    }
    
    setupRegisterForm() {
        const registerForm = document.getElementById('registerForm');
        if (!registerForm) return;
        
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleRegister(registerForm);
        });
        
        this.setupPasswordConfirmation(registerForm);
    }
    
    setupLogoutButtons() {
        document.querySelectorAll('[data-logout]').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        });
    }
    
    setupPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', () => {
                const input = toggle.parentNode.querySelector('input[type="password"], input[type="text"]');
                const icon = toggle.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });
    }
    
    setupPasswordStrength() {
        const passwordInputs = document.querySelectorAll('#password, #newPassword');
        
        passwordInputs.forEach(input => {
            let strengthIndicator = input.parentNode.querySelector('.password-strength');
            
            if (!strengthIndicator) {
                strengthIndicator = document.createElement('div');
                strengthIndicator.className = 'password-strength';
                strengthIndicator.innerHTML = `
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <div class="strength-text">Force du mot de passe</div>
                `;
                input.parentNode.appendChild(strengthIndicator);
            }
            
            input.addEventListener('input', () => {
                this.updatePasswordStrength(input, strengthIndicator);
            });
        });
    }
    
    setupPasswordConfirmation(form) {
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirmPassword');
        
        if (!password || !confirmPassword) return;
        
        const checkMatch = () => {
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                confirmPassword.classList.add('error');
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('error');
            }
        };
        
        password.addEventListener('input', checkMatch);
        confirmPassword.addEventListener('input', checkMatch);
    }
    
    setupSocialAuth() {
        document.querySelectorAll('.social-auth-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const provider = button.dataset.provider;
                this.handleSocialAuth(provider);
            });
        });
    }
    
    async handleLogin(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.textContent;
        
        try {
            this.setLoadingState(submitButton, true);
            this.clearFormErrors(form);
            
            const response = await fetch('/ajax/login.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(form, data.message);
                
                const rememberMe = form.querySelector('#rememberMe');
                if (rememberMe) {
                    localStorage.setItem('rememberMe', rememberMe.checked);
                }
                
                this.app.emit('userLoggedIn', { user: data.user });
                
                setTimeout(() => {
                    window.location.href = data.redirect || '/dashboard';
                }, 1000);
                
            } else {
                this.showError(form, data.message);
                
                if (data.errors) {
                    this.showFormErrors(form, data.errors);
                }
                
                if (data.captcha_required) {
                    this.showCaptcha(form);
                }
            }
            
        } catch (error) {
            console.error('Login error:', error);
            this.showError(form, 'Erreur de connexion. Veuillez réessayer.');
        } finally {
            this.setLoadingState(submitButton, false, originalText);
        }
    }
    
    async handleRegister(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.textContent;
        
        try {
            this.setLoadingState(submitButton, true);
            this.clearFormErrors(form);
            
            if (!this.validateRegisterForm(form)) {
                return;
            }
            
            const response = await fetch('/ajax/register.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(form, data.message);
                
                this.app.emit('userRegistered', { user: data.user });
                
                setTimeout(() => {
                    window.location.href = data.redirect || '/login';
                }, 2000);
                
            } else {
                this.showError(form, data.message);
                
                if (data.errors) {
                    this.showFormErrors(form, data.errors);
                }
            }
            
        } catch (error) {
            console.error('Register error:', error);
            this.showError(form, 'Erreur d\'inscription. Veuillez réessayer.');
        } finally {
            this.setLoadingState(submitButton, false, originalText);
        }
    }
    
    async handleLogout() {
        try {
            const response = await fetch('/ajax/logout.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.app.emit('userLoggedOut');
                window.location.href = '/';
            }
            
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = '/';
        }
    }
    
    async handleSocialAuth(provider) {
        const popup = window.open(
            `/auth/${provider}`,
            'socialAuth',
            'width=600,height=600,scrollbars=yes,resizable=yes'
        );
        
        const checkClosed = setInterval(() => {
            if (popup.closed) {
                clearInterval(checkClosed);
                this.checkAuthStatus();
            }
        }, 1000);
        
        window.addEventListener('message', (event) => {
            if (event.origin !== window.location.origin) return;
            
            if (event.data.type === 'SOCIAL_AUTH_SUCCESS') {
                popup.close();
                clearInterval(checkClosed);
                this.app.emit('userLoggedIn', { user: event.data.user });
                window.location.href = event.data.redirect || '/dashboard';
            }
            
            if (event.data.type === 'SOCIAL_AUTH_ERROR') {
                popup.close();
                clearInterval(checkClosed);
                this.showError(document.body, event.data.message);
            }
        });
    }
    
    validateRegisterForm(form) {
        let isValid = true;
        
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Ce champ est obligatoire');
                isValid = false;
            }
        });
        
        const email = form.querySelector('#email');
        if (email && email.value && !this.isValidEmail(email.value)) {
            this.showFieldError(email, 'Adresse email invalide');
            isValid = false;
        }
        
        const password = form.querySelector('#password');
        if (password && password.value.length < 8) {
            this.showFieldError(password, 'Le mot de passe doit contenir au moins 8 caractères');
            isValid = false;
        }
        
        const confirmPassword = form.querySelector('#confirmPassword');
        if (confirmPassword && password && confirmPassword.value !== password.value) {
            this.showFieldError(confirmPassword, 'Les mots de passe ne correspondent pas');
            isValid = false;
        }
        
        const phone = form.querySelector('#phone');
        if (phone && phone.value && !this.isValidPhone(phone.value)) {
            this.showFieldError(phone, 'Numéro de téléphone invalide');
            isValid = false;
        }
        
        const terms = form.querySelector('#terms');
        if (terms && !terms.checked) {
            this.showFieldError(terms, 'Vous devez accepter les conditions d\'utilisation');
            isValid = false;
        }
        
        return isValid;
    }
    
    updatePasswordStrength(input, indicator) {
        const password = input.value;
        const strength = this.calculatePasswordStrength(password);
        
        const fill = indicator.querySelector('.strength-fill');
        const text = indicator.querySelector('.strength-text');
        
        const levels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
        const colors = ['#e53935', '#ff9800', '#ffc107', '#4caf50', '#2e7d32'];
        
        fill.style.width = `${(strength / 4) * 100}%`;
        fill.style.backgroundColor = colors[strength];
        text.textContent = levels[strength];
        text.style.color = colors[strength];
    }
    
    calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z\d]/.test(password)) strength++;
        
        return strength;
    }
    
    checkSessionTimeout() {
        if (!this.isLoggedIn) return;
        
        const lastActivity = localStorage.getItem('lastActivity');
        const sessionTimeout = 2 * 60 * 60 * 1000;
        
        if (lastActivity && (Date.now() - parseInt(lastActivity)) > sessionTimeout) {
            this.handleSessionExpired();
            return;
        }
        
        this.updateLastActivity();
        
        setInterval(() => {
            this.updateLastActivity();
        }, 60000);
        
        document.addEventListener('click', () => this.updateLastActivity());
        document.addEventListener('keypress', () => this.updateLastActivity());
    }
    
    updateLastActivity() {
        localStorage.setItem('lastActivity', Date.now().toString());
    }
    
    handleSessionExpired() {
        this.showSessionExpiredModal();
    }
    
    showSessionExpiredModal() {
        const modal = document.createElement('div');
        modal.className = 'modal session-expired-modal show';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Session expirée</h3>
                </div>
                <div class="modal-body">
                    <p>Votre session a expiré pour des raisons de sécurité. Veuillez vous reconnecter.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="window.location.href='/login'">
                        Se reconnecter
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        document.body.classList.add('modal-open');
    }
    
    async checkAuthStatus() {
        try {
            const response = await fetch('/ajax/check-auth.php', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.authenticated !== this.isLoggedIn) {
                window.location.reload();
            }
            
        } catch (error) {
            console.error('Auth check error:', error);
        }
    }
    
    getCurrentUser() {
        const userElement = document.querySelector('[data-user]');
        if (userElement) {
            try {
                return JSON.parse(userElement.dataset.user);
            } catch (error) {
                console.error('Error parsing user data:', error);
            }
        }
        return null;
    }
    
    showCaptcha(form) {
        const captchaContainer = document.createElement('div');
        captchaContainer.className = 'captcha-container';
        captchaContainer.innerHTML = `
            <div class="form-group">
                <label for="captcha">Code de vérification</label>
                <div class="captcha-wrapper">
                    <img src="/ajax/captcha.php" alt="Captcha" class="captcha-image" onclick="this.src='/ajax/captcha.php?'+Math.random()">
                    <input type="text" id="captcha" name="captcha" class="form-control" required>
                </div>
                <small class="form-help">Cliquez sur l'image pour renouveler le code</small>
            </div>
        `;
        
        const submitButton = form.querySelector('[type="submit"]');
        submitButton.parentNode.insertBefore(captchaContainer, submitButton);
    }
    
    setLoadingState(button, loading, originalText = '') {
        if (loading) {
            button.disabled = true;
            button.classList.add('loading');
            button.innerHTML = '<span class="btn-loader"></span><span class="btn-text" style="opacity: 0">Chargement...</span>';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            button.innerHTML = originalText;
        }
    }
    
    clearFormErrors(form) {
        form.querySelectorAll('.form-error').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
        form.querySelectorAll('.form-alert').forEach(alert => alert.remove());
    }
    
    showFormErrors(form, errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                this.showFieldError(field, errors[fieldName]);
            }
        });
    }
    
    showFieldError(field, message) {
        field.classList.add('error');
        
        let errorElement = field.parentNode.querySelector('.form-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    showSuccess(container, message) {
        this.showAlert(container, message, 'success');
    }
    
    showError(container, message) {
        this.showAlert(container, message, 'error');
    }
    
    showAlert(container, message, type) {
        const alert = document.createElement('div');
        alert.className = `form-alert ${type}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        alert.innerHTML = `
            <div class="form-alert-icon">
                <i class="${icons[type]}"></i>
            </div>
            <div class="form-alert-content">
                <div class="form-alert-message">${message}</div>
            </div>
        `;
        
        container.insertBefore(alert, container.firstChild);
        
        setTimeout(() => {
            alert.classList.add('show');
        }, 100);
        
        if (type === 'success') {
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    }
    
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    isValidPhone(phone) {
        return /^[\+]?[\d\s\-\(\)]{8,20}$/.test(phone);
    }
}

window.Auth = Auth;