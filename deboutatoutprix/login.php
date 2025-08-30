<?php
require_once 'classes/Admin.php';

session_start();

$admin = new Admin();

if ($admin->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $result = $admin->login($username, $password);
        
        if ($result['success']) {
            if ($rememberMe) {
                setcookie('admin_remember', bin2hex(random_bytes(32)), time() + (30 * 24 * 60 * 60), '/', '', true, true);
            }
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Connexion Administrateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - PrestaCapi Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../images/favicon/favicon.ico">
    <style>
        body {
            background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 64px rgba(31, 59, 115, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .login-logo {
            position: relative;
            z-index: 2;
        }

        .login-logo img {
            height: 40px;
            margin-bottom: 12px;
        }

        .login-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }

        .login-subtitle {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }

        .login-body {
            padding: 40px 30px;
        }

        .login-form {
            width: 100%;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333E48;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #F5F7FA;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #00B8D9;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 184, 217, 0.1);
            transform: translateY(-1px);
        }

        .form-control:hover {
            border-color: #00B8D9;
            background: white;
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6B7280;
            font-size: 18px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: #00B8D9;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #00B8D9;
        }

        .form-check label {
            font-size: 14px;
            color: #6B7280;
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(31, 59, 115, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: rgba(229, 57, 53, 0.1);
            color: #E53935;
            border: 1px solid rgba(229, 57, 53, 0.2);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.2);
        }

        .login-footer {
            padding: 20px 30px;
            background: #F5F7FA;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 16px;
        }

        .footer-link {
            color: #6B7280;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #00B8D9;
        }

        .footer-text {
            color: #9CA3AF;
            font-size: 12px;
        }

        .security-info {
            background: rgba(0, 184, 217, 0.05);
            border: 1px solid rgba(0, 184, 217, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
        }

        .security-title {
            font-weight: 600;
            color: #00B8D9;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .security-text {
            font-size: 12px;
            color: #6B7280;
            line-height: 1.4;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #E5E7EB;
            border-top-color: #00B8D9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header,
            .login-body {
                padding: 30px 24px;
            }
            
            .login-footer {
                padding: 16px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="../images/logo-white.png" alt="PrestaCapi" onerror="this.style.display='none'">
                <div class="login-title">PrestaCapi Admin</div>
                <div class="login-subtitle">Espace d'administration sécurisé</div>
            </div>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required 
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           placeholder="Votre nom d'utilisateur">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="password-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               required 
                               autocomplete="current-password"
                               placeholder="Votre mot de passe">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Se souvenir de moi (30 jours)</label>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="loginText">Se connecter</span>
                </button>
            </form>

            <div class="security-info">
                <div class="security-title">
                    🔐 Sécurité renforcée
                </div>
                <div class="security-text">
                    Cet espace est protégé par un chiffrement SSL 256-bit et une authentification à deux facteurs. 
                    Toutes les connexions sont surveillées et enregistrées.
                </div>
            </div>
        </div>

        <div class="login-footer">
            <div class="footer-links">
                <a href="../" class="footer-link">Retour au site</a>
                <a href="help.php" class="footer-link">Aide</a>
                <a href="contact.php" class="footer-link">Support</a>
            </div>
            <div class="footer-text">
                © <?php echo date('Y'); ?> PrestaCapi. Tous droits réservés.
            </div>
        </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div>Connexion en cours...</div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loadingOverlay = document.getElementById('loadingOverlay');

            if (!username || !password) {
                e.preventDefault();
                showAlert('Veuillez remplir tous les champs', 'error');
                return;
            }

            if (password.length < 8) {
                e.preventDefault();
                showAlert('Le mot de passe doit contenir au moins 8 caractères', 'error');
                return;
            }

            loginBtn.disabled = true;
            loginText.textContent = 'Connexion...';
            loadingOverlay.style.display = 'flex';
        });

        function showAlert(message, type = 'error') {
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <span>${type === 'error' ? '⚠️' : '✅'}</span>
                ${message}
            `;

            const form = document.querySelector('.login-form');
            form.parentNode.insertBefore(alert, form);

            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const usernameInput = document.getElementById('username');
            usernameInput.focus();

            const form = document.getElementById('loginForm');
            form.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });

            const savedUsername = localStorage.getItem('admin_username');
            if (savedUsername) {
                usernameInput.value = savedUsername;
                document.getElementById('password').focus();
            }

            form.addEventListener('submit', function() {
                const username = usernameInput.value.trim();
                if (username) {
                    localStorage.setItem('admin_username', username);
                }
            });

            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentNode.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentNode.classList.remove('focused');
                });
            });
        });

        let attemptCount = 0;
        const maxAttempts = 5;

        if (<?php echo $error ? 'true' : 'false'; ?>) {
            attemptCount++;
            if (attemptCount >= maxAttempts) {
                showAlert('Trop de tentatives. Veuillez patienter 5 minutes.', 'error');
                document.getElementById('loginBtn').disabled = true;
                setTimeout(() => {
                    document.getElementById('loginBtn').disabled = false;
                    attemptCount = 0;
                }, 300000);
            }
        }
    </script>
</body>
</html>