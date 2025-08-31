<?php
// 1. Inclure les classes nécessaires
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';

// 2. Créer les instances des objets
$lang = Language::getInstance();
$user = new User();
$seo = new SEO();


if ($user->isLoggedIn()) {
    header('Location: ' . $lang->pageUrl('dashboard'));
    exit;
}

// Déterminer si on est en mode 'login' ou 'register'
$mode = isset($_GET['mode']) && $_GET['mode'] === 'register' ? 'register' : 'login';

// 4. Définir les variables pour la vue (HTML)
$pageKey = $mode; // Utiliser $mode pour que le titre change dynamiquement
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="canonical" href="<?php echo $seo->generateCanonicalUrl($lang->pageUrl($pageKey)); ?>">
    
    <?php echo $seo->generateAlternateLinks(); ?>
    
    <?php echo $seo->generateOpenGraphTags(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php echo $seo->generateTwitterCard(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    
    <?php echo $seo->generateMetaTags(); ?>

    <?php echo $seo->generateStructuredData('webpage', ['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php // Optionnel: Ajouter un Breadcrumb si pertinent
    /*
    echo $seo->generateStructuredData('breadcrumb', ['items' => [
        ['name' => $lang->get('home'), 'url' => $lang->url('home')],
        ['name' => $pageTitle]
    ]]);
    */
    ?>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/login.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
</head>
<body class="auth-page">
    <?php include 'includes/auth_header.php'; ?>
    
    <div class="auth-container">
        <div class="auth-background">
            <div class="auth-gradient"></div>
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        
        <div class="auth-content">
            <div class="auth-card">
                <div class="auth-toggle">
                    <button class="toggle-btn <?php echo $mode === 'login' ? 'active' : ''; ?>" 
                            onclick="switchMode('login')" id="loginToggle">
                        <?php echo $lang->get('login'); ?>
                    </button>
                    <button class="toggle-btn <?php echo $mode === 'register' ? 'active' : ''; ?>" 
                            onclick="switchMode('register')" id="registerToggle">
                        <?php echo $lang->get('register'); ?>
                    </button>
                    <div class="toggle-indicator" id="toggleIndicator"></div>
                </div>
                
                <div class="auth-form-container">
                    <form id="loginForm" class="auth-form <?php echo $mode === 'login' ? 'active' : ''; ?>" 
                          onsubmit="handleLogin(event)">
                        <div class="form-header">
                            <h1 class="form-title"><?php echo $lang->get('auth_login_title'); ?></h1>
                            <p class="form-subtitle">Accédez à votre espace personnel PrestaCapi</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="loginEmail" class="form-label">
                                <?php echo $lang->get('auth_email'); ?>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">📧</span>
                                <input type="email" 
                                       id="loginEmail" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="votre@email.com"
                                       required>
                            </div>
                            <div class="form-error" id="loginEmailError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="loginPassword" class="form-label">
                                <?php echo $lang->get('auth_password'); ?>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" 
                                       id="loginPassword" 
                                       name="password" 
                                       class="form-input" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                    👁️
                                </button>
                            </div>
                            <div class="form-error" id="loginPasswordError"></div>
                        </div>
                        
                        <div class="form-group form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember_me" id="rememberMe">
                                <span class="checkbox-custom"></span>
                                <?php echo $lang->get('auth_remember_me'); ?>
                            </label>
                            <a href="#" class="forgot-password" onclick="showForgotPassword()">
                                <?php echo $lang->get('auth_forgot_password'); ?>
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
                            <span class="btn-text"><?php echo $lang->get('auth_login_btn'); ?></span>
                            <span class="btn-loader"></span>
                        </button>
                        
                        <div class="form-footer">
                            <p><?php echo $lang->get('auth_no_account'); ?></p>
                            <button type="button" class="link-btn" onclick="switchMode('register')">
                                <?php echo $lang->get('auth_signup_here'); ?>
                            </button>
                        </div>
                    </form>
                    
                    <form id="registerForm" class="auth-form <?php echo $mode === 'register' ? 'active' : ''; ?>" 
                          onsubmit="handleRegister(event)">
                        <div class="form-header">
                            <h1 class="form-title"><?php echo $lang->get('auth_register_title'); ?></h1>
                            <p class="form-subtitle">Rejoignez la communauté PrestaCapi</p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName" class="form-label">
                                    <?php echo $lang->get('auth_first_name'); ?>
                                </label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👤</span>
                                    <input type="text" 
                                           id="firstName" 
                                           name="first_name" 
                                           class="form-input" 
                                           placeholder="Prénom"
                                           required>
                                </div>
                                <div class="form-error" id="firstNameError"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName" class="form-label">
                                    <?php echo $lang->get('auth_last_name'); ?>
                                </label>
                                <div class="input-wrapper">
                                    <span class="input-icon">👤</span>
                                    <input type="text" 
                                           id="lastName" 
                                           name="last_name" 
                                           class="form-input" 
                                           placeholder="Nom de famille"
                                           required>
                                </div>
                                <div class="form-error" id="lastNameError"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="registerEmail" class="form-label">
                                <?php echo $lang->get('auth_email'); ?>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">📧</span>
                                <input type="email" 
                                       id="registerEmail" 
                                       name="email" 
                                       class="form-input" 
                                       placeholder="votre@email.com"
                                       required>
                            </div>
                            <div class="form-error" id="registerEmailError"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <?php echo $lang->get('auth_phone'); ?> <span class="optional">(<?php echo $lang->get('form_optional'); ?>)</span>
                                </label>
                                <div class="input-wrapper">
                                    <span class="input-icon">📱</span>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           class="form-input" 
                                           placeholder="+33 1 23 45 67 89">
                                </div>
                                <div class="form-error" id="phoneError"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="whatsapp" class="form-label">
                                    <?php echo $lang->get('auth_whatsapp'); ?> <span class="optional">(<?php echo $lang->get('form_optional'); ?>)</span>
                                </label>
                                <div class="input-wrapper">
                                    <span class="input-icon">💬</span>
                                    <input type="tel" 
                                           id="whatsapp" 
                                           name="whatsapp" 
                                           class="form-input" 
                                           placeholder="+33 6 12 34 56 78">
                                </div>
                                <div class="form-error" id="whatsappError"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="registerPassword" class="form-label">
                                <?php echo $lang->get('auth_password'); ?>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" 
                                       id="registerPassword" 
                                       name="password" 
                                       class="form-input" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePassword('registerPassword')">
                                    👁️
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                                <div class="strength-text">Force du mot de passe</div>
                            </div>
                            <div class="form-error" id="registerPasswordError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword" class="form-label">
                                <?php echo $lang->get('auth_password_confirm'); ?>
                            </label>
                            <div class="input-wrapper">
                                <span class="input-icon">🔒</span>
                                <input type="password" 
                                       id="confirmPassword" 
                                       name="password_confirm" 
                                       class="form-input" 
                                       placeholder="••••••••"
                                       required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                    👁️
                                </button>
                            </div>
                            <div class="form-error" id="confirmPasswordError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms" id="acceptTerms" required>
                                <span class="checkbox-custom"></span>
                                J'accepte les <a href="<?php echo $lang->pageUrl('terms'); ?>" target="_blank">conditions d'utilisation</a> 
                                et la <a href="<?php echo $lang->pageUrl('privacy'); ?>" target="_blank">politique de confidentialité</a>
                            </label>
                            <div class="form-error" id="termsError"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full" id="registerBtn">
                            <span class="btn-text"><?php echo $lang->get('auth_register_btn'); ?></span>
                            <span class="btn-loader"></span>
                        </button>
                        
                        <div class="form-footer">
                            <p><?php echo $lang->get('auth_has_account'); ?></p>
                            <button type="button" class="link-btn" onclick="switchMode('login')">
                                <?php echo $lang->get('auth_signin_here'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="auth-features">
                    <div class="feature-item">
                        <div class="feature-icon">⚡</div>
                        <div class="feature-text">
                            <strong>Réponse en 24h</strong>
                            <span>Traitement rapide de votre demande</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        <div class="feature-text">
                            <strong>100% Sécurisé</strong>
                            <span>Vos données sont protégées</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">💰</div>
                        <div class="feature-text">
                            <strong>Jusqu'à 50 000€</strong>
                            <span>Montants flexibles selon votre profil</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="auth-help">
            <div class="help-content">
                <h3>Besoin d'aide ?</h3>
                <p>Notre équipe support est disponible 7j/7</p>
                <div class="help-contacts">
                    <a href="tel:+33123456789" class="help-contact">
                        <span class="contact-icon">📞</span>
                        <span>+33 1 23 45 67 89</span>
                    </a>
                    <a href="mailto:support@prestacapi.com" class="help-contact">
                        <span class="contact-icon">📧</span>
                        <span>support@prestacapi.com</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal" id="forgotPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Réinitialiser le mot de passe</h3>
                <button class="modal-close" onclick="closeForgotPasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handleForgotPassword(event)">
                    <div class="form-group">
                        <label for="forgotEmail" class="form-label">Adresse email</label>
                        <input type="email" 
                               id="forgotEmail" 
                               name="email" 
                               class="form-input" 
                               placeholder="votre@email.com"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        Envoyer le lien de réinitialisation
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast">
        <div class="toast-content">
            <div class="toast-icon" id="toastIcon"></div>
            <div class="toast-message" id="toastMessage"></div>
        </div>
    </div>
    
    <script>
        let currentMode = '<?php echo $mode; ?>';
        
        function switchMode(mode) {
            currentMode = mode;
            
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const loginToggle = document.getElementById('loginToggle');
            const registerToggle = document.getElementById('registerToggle');
            const indicator = document.getElementById('toggleIndicator');
            
            if (mode === 'login') {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
                loginToggle.classList.add('active');
                registerToggle.classList.remove('active');
                indicator.style.transform = 'translateX(0)';
                
                history.replaceState(null, null, '<?php echo $lang->pageUrl("login"); ?>');
            } else {
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
                registerToggle.classList.add('active');
                loginToggle.classList.remove('active');
                indicator.style.transform = 'translateX(100%)';
                
                history.replaceState(null, null, '<?php echo $lang->pageUrl("register"); ?>');
            }
        }
        
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.nextElementSibling;
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.textContent = '🙈';
            } else {
                input.type = 'password';
                toggle.textContent = '👁️';
            }
        }
        
        function handleLogin(event) {
            event.preventDefault();
            
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            
            const formData = new FormData(event.target);
            
            fetch('/ajax/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.classList.remove('loading');
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '<?php echo $lang->pageUrl("dashboard"); ?>';
                    }, 1000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                btn.classList.remove('loading');
                showToast('Une erreur est survenue', 'error');
            });
        }
        
        function handleRegister(event) {
            event.preventDefault();
            
            const btn = document.getElementById('registerBtn');
            btn.classList.add('loading');
            
            const formData = new FormData(event.target);
            
            if (!validateRegisterForm(formData)) {
                btn.classList.remove('loading');
                return;
            }
            
            fetch('/ajax/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.classList.remove('loading');
                
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        switchMode('login');
                        document.getElementById('loginEmail').value = formData.get('email');
                    }, 2000);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                btn.classList.remove('loading');
                showToast('Une erreur est survenue', 'error');
            });
        }
        
        function validateRegisterForm(formData) {
            const password = formData.get('password');
            const confirmPassword = formData.get('password_confirm');
            
            if (password !== confirmPassword) {
                showToast('Les mots de passe ne correspondent pas', 'error');
                return false;
            }
            
            if (password.length < 8) {
                showToast('Le mot de passe doit contenir au moins 8 caractères', 'error');
                return false;
            }
            
            return true;
        }
        
        function showForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.add('show');
        }
        
        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.remove('show');
        }
        
        function handleForgotPassword(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('/ajax/forgot-password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeForgotPasswordModal();
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            const messageEl = document.getElementById('toastMessage');
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            icon.textContent = icons[type] || icons.info;
            messageEl.textContent = message;
            toast.className = `toast show ${type}`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const registerPasswordInput = document.getElementById('registerPassword');
            const strengthIndicator = document.getElementById('passwordStrength');
            
            registerPasswordInput?.addEventListener('input', function() {
                const password = this.value;
                const strength = calculatePasswordStrength(password);
                
                const strengthFill = strengthIndicator.querySelector('.strength-fill');
                const strengthText = strengthIndicator.querySelector('.strength-text');
                
                const levels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
                const colors = ['#ff4444', '#ff8800', '#ffdd00', '#88dd00', '#00dd44'];
                
                strengthFill.style.width = (strength * 20) + '%';
                strengthFill.style.backgroundColor = colors[strength - 1] || '#ddd';
                strengthText.textContent = levels[strength - 1] || 'Entrez un mot de passe';
                
                strengthIndicator.style.opacity = password.length > 0 ? '1' : '0';
            });
            
            <?php if ($mode === 'register'): ?>
                setTimeout(() => switchMode('register'), 100);
            <?php endif; ?>
        });
        
        function calculatePasswordStrength(password) {
            if (password.length < 4) return 1;
            if (password.length < 8) return 2;
            
            let strength = 2;
            
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return Math.min(strength, 5);
        }
        
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('forgotPasswordModal');
            if (e.target === modal) {
                closeForgotPasswordModal();
            }
        });
    </script>
</body>
</html>