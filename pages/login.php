<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';

$lang = Language::getInstance();
$user = new User();
$seo = new SEO();

if ($user->isLoggedIn()) {
    header('Location: ' . $lang->pageUrl('dashboard'));
    exit;
}

$pageKey = 'login';
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

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/register.css"> <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/responsive.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
</head>
<body class="auth-page">
    <?php include 'includes/auth_header.php'; ?>
    
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-form-section">
                <div class="form-header">
                    <h1 class="form-title"><?php echo $lang->get('auth_login_title'); ?></h1>
                    <p class="form-subtitle"><?php echo $lang->get('login_subtitle'); ?></p>
                </div>

                <div class="alert alert-error" id="login-alert" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <h3 id="login-alert-title"><?php echo $lang->get('error_message'); ?></h3>
                        <p id="login-alert-message"></p>
                    </div>
                </div>

                <form class="auth-form" id="login-form" method="POST" onsubmit="handleLogin(event)" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label"><?php echo $lang->get('auth_email'); ?></label>
                        <input type="email" id="email" name="email" class="form-input" required autocomplete="email" placeholder="<?php echo $lang->get('login_placeholder_email'); ?>">
                        <div class="form-feedback"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label"><?php echo $lang->get('auth_password'); ?></label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password" placeholder="<?php echo $lang->get('login_placeholder_password'); ?>">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="form-feedback"></div>
                    </div>

                    <div class="form-options" style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-sm); margin-bottom: var(--spacing-lg);">
                        <div class="checkbox-group">
                             <label for="remember_me" class="checkbox-label">
                                <input type="checkbox" id="remember_me" name="remember_me">
                                <span class="checkbox-custom"></span>
                                <span><?php echo $lang->get('auth_remember_me'); ?></span>
                            </label>
                        </div>
                        <a href="#" class="auth-link" style="font-size: var(--font-sm);" onclick="showForgotPasswordModal(event)"><?php echo $lang->get('auth_forgot_password'); ?></a>
                    </div>
                    
                    <div class="form-actions" style="border-top: none; padding-top: 0;">
                        <button type="submit" class="btn btn-primary" id="submitBtn" style="width: 100%;">
                            <span class="btn-text"><?php echo $lang->get('auth_login_btn'); ?></span>
                            <div class="btn-loader"></div>
                        </button>
                    </div>
                </form>
                
                <div class="form-footer">
                    <p class="auth-switch">
                        <?php echo $lang->get('auth_no_account'); ?>
                        <a href="<?php echo $lang->pageUrl('register'); ?>" class="auth-link">
                            <?php echo $lang->get('auth_signup_here'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <div class="modal" id="forgotPasswordModal" style="display: none;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3><?php echo $lang->get('forgot_password_title'); ?></h3>
                <button class="modal-close" onclick="closeForgotPasswordModal(event)">&times;</button>
            </div>
            <div class="modal-body">
                <form onsubmit="handleForgotPassword(event)">
                    <p style="text-align: center; margin-top: 0; color: var(--text-color);"><?php echo $lang->get('forgot_password_instructions'); ?></p>
                    <div class="form-group">
                        <label for="forgotEmail" class="form-label"><?php echo $lang->get('auth_email'); ?></label>
                        <input type="email" id="forgotEmail" name="email" class="form-input" placeholder="<?php echo $lang->get('login_placeholder_email'); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <?php echo $lang->get('forgot_password_submit_btn'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function handleLogin(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const form = event.target;
            const alertBox = document.getElementById('login-alert');
            const alertMessage = document.getElementById('login-alert-message');

            alertBox.style.display = 'none';
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            const formData = new FormData(form);

            fetch('/ajax/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || '<?php echo $lang->pageUrl("dashboard"); ?>';
                } else {
                    alertMessage.textContent = data.message || '<?php echo $lang->get("js_error_occurred"); ?>';
                    alertBox.style.display = 'flex';
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Login Fetch Error:', error);
                alertMessage.textContent = '<?php echo $lang->get("js_error_occurred"); ?>';
                alertBox.style.display = 'flex';
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            });
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        const modal = document.getElementById('forgotPasswordModal');

        function showForgotPasswordModal(event) {
            event.preventDefault();
            modal.style.display = 'flex';
        }

        function closeForgotPasswordModal(event) {
            event.preventDefault();
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function handleForgotPassword(event) {
            event.preventDefault();
            alert('Instructions de réinitialisation envoyées !');
            closeForgotPasswordModal(event);
        }
    </script>
</body>
</html>