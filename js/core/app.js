class App {
    constructor() {
        this.config = {
            apiUrl: '/ajax/',
            debug: false,
            lang: document.documentElement.lang || 'fr',
            theme: localStorage.getItem('theme') || 'light',
            animations: !window.matchMedia('(prefers-reduced-motion: reduce)').matches
        };
        
        this.modules = new Map();
        this.eventBus = new EventTarget();
        
        this.init();
    }
    
    init() {
        this.setupTheme();
        this.setupAnimations();
        this.setupGlobalEvents();
        this.initializeModules();
        this.setupErrorHandling();
        
        if (this.config.debug) {
            console.log('PrestaCapi App initialized', this.config);
        }
    }
    
    setupTheme() {
        document.documentElement.setAttribute('data-theme', this.config.theme);
        
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.classList.toggle('dark', this.config.theme === 'dark');
            themeToggle.addEventListener('click', () => this.toggleTheme());
        }
        
        const themeSelector = document.querySelector('.theme-selector');
        if (themeSelector) {
            themeSelector.addEventListener('click', (e) => {
                if (e.target.classList.contains('theme-option')) {
                    this.setTheme(e.target.dataset.theme);
                }
            });
        }
    }
    
    toggleTheme() {
        const newTheme = this.config.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
    }
    
    setTheme(theme) {
        this.config.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        
        const themeToggle = document.querySelector('.theme-toggle');
        if (themeToggle) {
            themeToggle.classList.toggle('dark', theme === 'dark');
        }
        
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.toggle('active', option.dataset.theme === theme);
        });
        
        this.emit('themeChanged', { theme });
    }
    
    setupAnimations() {
        if (!this.config.animations) {
            document.body.classList.add('reduced-motion');
            return;
        }
        
        this.observeAnimations();
        this.setupIntersectionObserver();
    }
    
    observeAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    
                    if (entry.target.classList.contains('stagger-animation')) {
                        this.animateStagger(entry.target);
                    }
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        document.querySelectorAll('.animate-on-scroll, .stagger-animation').forEach(el => {
            observer.observe(el);
        });
    }
    
    setupIntersectionObserver() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    
                    if (element.dataset.src) {
                        element.src = element.dataset.src;
                        element.removeAttribute('data-src');
                    }
                    
                    if (element.dataset.background) {
                        element.style.backgroundImage = `url(${element.dataset.background})`;
                        element.removeAttribute('data-background');
                    }
                    
                    observer.unobserve(element);
                }
            });
        });
        
        document.querySelectorAll('[data-src], [data-background]').forEach(el => {
            observer.observe(el);
        });
    }
    
    animateStagger(container) {
        const children = container.children;
        Array.from(children).forEach((child, index) => {
            setTimeout(() => {
                child.style.animationDelay = `${index * 0.1}s`;
                child.classList.add('animate-fade-up');
            }, index * 100);
        });
    }
    
    setupGlobalEvents() {
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        document.addEventListener('submit', this.handleFormSubmit.bind(this));
        window.addEventListener('resize', this.debounce(this.handleResize.bind(this), 250));
        window.addEventListener('scroll', this.throttle(this.handleScroll.bind(this), 16));
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModals();
                this.closeMobileMenus();
            }
        });
    }
    
    handleGlobalClick(e) {
        if (e.target.matches('[data-ripple]')) {
            this.createRippleEffect(e.target, e);
        }
        
        if (e.target.matches('[data-modal]')) {
            e.preventDefault();
            this.openModal(e.target.dataset.modal);
        }
        
        if (e.target.matches('[data-modal-close]')) {
            this.closeModal(e.target.closest('.modal'));
        }
        
        if (e.target.matches('[data-confirm]')) {
            if (!confirm(e.target.dataset.confirm)) {
                e.preventDefault();
            }
        }
        
        if (e.target.matches('[data-toggle]')) {
            e.preventDefault();
            this.toggleElement(e.target.dataset.toggle);
        }
        
        if (e.target.matches('.mobile-menu-toggle, .admin-mobile-menu-toggle')) {
            e.preventDefault();
            this.toggleMobileMenu();
        }
    }
    
    handleFormSubmit(e) {
        const form = e.target;
        
        if (form.classList.contains('ajax-form')) {
            e.preventDefault();
            this.submitAjaxForm(form);
        }
        
        if (form.noValidate === false) {
            if (!this.validateForm(form)) {
                e.preventDefault();
            }
        }
    }
    
    handleResize() {
        this.emit('resize', { 
            width: window.innerWidth, 
            height: window.innerHeight 
        });
        
        this.updateMobileLayout();
    }
    
    handleScroll() {
        const scrollY = window.scrollY;
        
        this.emit('scroll', { scrollY });
        
        document.body.classList.toggle('scrolled', scrollY > 100);
        
        const header = document.querySelector('.header');
        if (header) {
            header.classList.toggle('header-scrolled', scrollY > 50);
        }
    }
    
    createRippleEffect(element, event) {
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        `;
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    }
    
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        modal.classList.add('show');
        document.body.classList.add('modal-open');
        
        const focusElement = modal.querySelector('[autofocus]') || modal.querySelector('input, button, select, textarea');
        if (focusElement) {
            focusElement.focus();
        }
        
        this.emit('modalOpened', { modalId, modal });
    }
    
    closeModal(modal) {
        if (!modal) return;
        
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        
        this.emit('modalClosed', { modal });
    }
    
    closeModals() {
        document.querySelectorAll('.modal.show').forEach(modal => {
            this.closeModal(modal);
        });
    }
    
    toggleElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.toggle('active');
            element.classList.toggle('open');
        }
    }
    
    toggleMobileMenu() {
        const sidebar = document.querySelector('.dashboard-sidebar, .admin-sidebar');
        const overlay = document.querySelector('.mobile-overlay') || this.createMobileOverlay();
        
        if (sidebar) {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
            document.body.classList.toggle('mobile-menu-open');
        }
    }
    
    closeMobileMenus() {
        document.querySelectorAll('.dashboard-sidebar, .admin-sidebar').forEach(sidebar => {
            sidebar.classList.remove('open');
        });
        
        const overlay = document.querySelector('.mobile-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
        
        document.body.classList.remove('mobile-menu-open');
    }
    
    createMobileOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'mobile-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        `;
        
        overlay.addEventListener('click', () => this.closeMobileMenus());
        document.body.appendChild(overlay);
        
        return overlay;
    }
    
    updateMobileLayout() {
        const isMobile = window.innerWidth < 992;
        document.body.classList.toggle('mobile-layout', isMobile);
        
        if (!isMobile) {
            this.closeMobileMenus();
        }
    }
    
    submitAjaxForm(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton?.textContent;
        
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.classList.add('loading');
            submitButton.innerHTML = '<span class="btn-loader"></span><span class="btn-text" style="opacity: 0">' + originalText + '</span>';
        }
        
        this.showFormAlert(form, '', 'loading');
        
        fetch(form.action, {
            method: form.method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showFormAlert(form, data.message, 'success');
                
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
                
                if (data.reset) {
                    form.reset();
                }
            } else {
                this.showFormAlert(form, data.message, 'error');
                this.showFormErrors(form, data.errors);
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            this.showFormAlert(form, 'Une erreur est survenue. Veuillez réessayer.', 'error');
        })
        .finally(() => {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('loading');
                submitButton.innerHTML = originalText;
            }
        });
    }
    
    showFormAlert(form, message, type) {
        let alertContainer = form.querySelector('.form-alert');
        
        if (!alertContainer && message) {
            alertContainer = document.createElement('div');
            alertContainer.className = 'form-alert';
            form.insertBefore(alertContainer, form.firstChild);
        }
        
        if (alertContainer) {
            if (!message) {
                alertContainer.remove();
                return;
            }
            
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ',
                loading: ''
            };
            
            alertContainer.className = `form-alert ${type}`;
            alertContainer.innerHTML = `
                <div class="form-alert-icon">${icons[type] || ''}</div>
                <div class="form-alert-content">
                    <div class="form-alert-message">${message}</div>
                </div>
            `;
            
            if (type === 'loading') {
                alertContainer.querySelector('.form-alert-icon').innerHTML = '<div class="form-loading-spinner"></div>';
            }
        }
    }
    
    showFormErrors(form, errors) {
        if (!errors || typeof errors !== 'object') return;
        
        Object.keys(errors).forEach(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                
                let errorElement = field.parentNode.querySelector('.form-error');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'form-error';
                    field.parentNode.appendChild(errorElement);
                }
                
                errorElement.textContent = errors[fieldName];
            }
        });
    }
    
    validateForm(form) {
        let isValid = true;
        
        form.querySelectorAll('.form-error').forEach(error => error.remove());
        form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));
        
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Ce champ est obligatoire');
                isValid = false;
            }
        });
        
        const emailFields = form.querySelectorAll('[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Adresse email invalide');
                isValid = false;
            }
        });
        
        const phoneFields = form.querySelectorAll('[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !this.isValidPhone(field.value)) {
                this.showFieldError(field, 'Numéro de téléphone invalide');
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    showFieldError(field, message) {
        field.classList.add('error');
        
        let errorElement = field.parentNode.querySelector('.form-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }
    
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    isValidPhone(phone) {
        return /^[\+]?[\d\s\-\(\)]{8,20}$/.test(phone);
    }
    
    initializeModules() {
        const moduleElements = document.querySelectorAll('[data-module]');
        
        moduleElements.forEach(element => {
            const moduleName = element.dataset.module;
            
            if (window[moduleName] && typeof window[moduleName] === 'function') {
                try {
                    const moduleInstance = new window[moduleName](element, this);
                    this.modules.set(moduleName, moduleInstance);
                } catch (error) {
                    console.error(`Failed to initialize module ${moduleName}:`, error);
                }
            }
        });
    }
    
    setupErrorHandling() {
        window.addEventListener('error', (event) => {
            if (this.config.debug) {
                console.error('Global error:', event.error);
            }
            
            this.emit('error', {
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                error: event.error
            });
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            if (this.config.debug) {
                console.error('Unhandled promise rejection:', event.reason);
            }
            
            this.emit('unhandledRejection', {
                reason: event.reason
            });
        });
    }
    
    emit(eventName, data = {}) {
        const event = new CustomEvent(eventName, { detail: data });
        this.eventBus.dispatchEvent(event);
        
        if (this.config.debug) {
            console.log(`Event emitted: ${eventName}`, data);
        }
    }
    
    on(eventName, callback) {
        this.eventBus.addEventListener(eventName, callback);
    }
    
    off(eventName, callback) {
        this.eventBus.removeEventListener(eventName, callback);
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    formatCurrency(amount) {
        return new Intl.NumberFormat(this.config.lang, {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }
    
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        
        return new Intl.DateTimeFormat(this.config.lang, { ...defaultOptions, ...options }).format(new Date(date));
    }
    
    formatNumber(number, options = {}) {
        return new Intl.NumberFormat(this.config.lang, options).format(number);
    }
    
    getModule(name) {
        return this.modules.get(name);
    }
    
    hasModule(name) {
        return this.modules.has(name);
    }
    
    reload() {
        window.location.reload();
    }
    
    redirect(url) {
        window.location.href = url;
    }
    
    back() {
        window.history.back();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.App = new App();
});