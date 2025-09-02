<?php
require_once 'classes/Database.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';
require_once 'classes/User.php';

$lang = Language::getInstance();
$seo = new SEO();
$user = new User();

if ($user->isLoggedIn()) {
    header('Location: ' . $lang->pageUrl('dashboard'));
    exit;
}

$pageKey = 'register';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

$countries = [
    'France', 'Belgique', 'Suisse', 'Luxembourg', 'Monaco',
    'Canada', 'Allemagne', 'Espagne', 'Italie', 'Portugal',
    'Maroc', 'Tunisie', 'Algérie', 'Sénégal', 'Côte d\'Ivoire'
];
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
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/register.css">
    <link rel="stylesheet" href="/css/auth.css">
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
                    <h1 class="form-title"><?php echo $lang->get('auth_register_title'); ?></h1>
                    <p class="form-subtitle"><?php echo $lang->get('register_subtitle'); ?></p>
                </div>

                <div class="alert alert-success" style="display: none;">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <h3><?php echo $lang->get('register_success_title'); ?></h3>
                        <p><?php echo $lang->get('register_success_message'); ?></p>
                    </div>
                </div>

                <div class="alert alert-error" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <h3><?php echo $lang->get('register_error_title'); ?></h3>
                        <ul class="error-list" style="list-style: none; padding-left: 0;"></ul>
                    </div>
                </div>
                
                <form class="auth-form" method="POST" onsubmit="submitRegistration(event)" novalidate>
                    <div class="form-progress">
                        <div class="progress-steps">
                            <div class="step active" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label"><?php echo $lang->get('register_step_account'); ?></span>
                            </div>
                            <div class="step" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label"><?php echo $lang->get('register_step_info'); ?></span>
                            </div>
                            <div class="step" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label"><?php echo $lang->get('register_step_address'); ?></span>
                            </div>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%;"></div>
                        </div>
                    </div>
                    
                    <div class="form-step active" data-step="1">
                        <h3 class="step-title"><?php echo $lang->get('register_step_title_account'); ?></h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label"><?php echo $lang->get('auth_first_name'); ?> <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-input" required autocomplete="given-name">
                                <div class="form-feedback"></div>
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="form-label"><?php echo $lang->get('auth_last_name'); ?> <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-input" required autocomplete="family-name">
                                <div class="form-feedback"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label"><?php echo $lang->get('auth_email'); ?> <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" required autocomplete="email">
                            <div class="form-feedback"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label"><?php echo $lang->get('auth_password'); ?> <span class="required">*</span></label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" class="form-input" required autocomplete="new-password" minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <div class="form-feedback"></div>
                            </div>
                            <div class="form-group">
                                <label for="password_confirm" class="form-label"><?php echo $lang->get('auth_password_confirm'); ?> <span class="required">*</span></label>
                                <div class="password-input">
                                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required autocomplete="new-password">
                                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')"><i class="fas fa-eye"></i></button>
                                </div>
                                <div class="form-feedback"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-step" data-step="2">
                        <h3 class="step-title"><?php echo $lang->get('register_step_title_info'); ?></h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label"><?php echo $lang->get('auth_phone'); ?> <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-input" required autocomplete="tel" placeholder="<?php echo $lang->get('register_placeholder_phone'); ?>">
                                <div class="form-feedback"></div>
                            </div>
                            <div class="form-group">
                                <label for="whatsapp" class="form-label"><?php echo $lang->get('auth_whatsapp'); ?></label>
                                <input type="tel" id="whatsapp" name="whatsapp" class="form-input" autocomplete="tel" placeholder="<?php echo $lang->get('register_placeholder_whatsapp'); ?>">
                                <div class="form-feedback"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth" class="form-label"><?php echo $lang->get('profile_date_of_birth'); ?> <span class="required">*</span></label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                            <div class="form-feedback"></div>
                            <div class="form-hint"><?php echo $lang->get('register_age_requirement_hint'); ?></div>
                        </div>
                    </div>
                    
                    <div class="form-step" data-step="3">
                        <h3 class="step-title"><?php echo $lang->get('register_step_title_address'); ?></h3>
                        <div class="form-group">
                            <label for="address" class="form-label"><?php echo $lang->get('profile_address'); ?></label>
                            <input type="text" id="address" name="address" class="form-input" autocomplete="street-address" placeholder="<?php echo $lang->get('register_placeholder_address'); ?>">
                            <div class="form-feedback"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city" class="form-label"><?php echo $lang->get('profile_city'); ?></label>
                                <input type="text" id="city" name="city" class="form-input" autocomplete="address-level2" placeholder="<?php echo $lang->get('register_placeholder_city'); ?>">
                                <div class="form-feedback"></div>
                            </div>
                            <div class="form-group">
                                <label for="postal_code" class="form-label"><?php echo $lang->get('profile_postal_code'); ?></label>
                                <input type="text" id="postal_code" name="postal_code" class="form-input" autocomplete="postal-code" placeholder="<?php echo $lang->get('register_placeholder_postal_code'); ?>">
                                <div class="form-feedback"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="country" class="form-label"><?php echo $lang->get('profile_country'); ?></label>
                            <select id="country" name="country" class="form-select" autocomplete="country">
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo htmlspecialchars($country); ?>" <?php echo (($_POST['country'] ?? 'France') === $country) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($country); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-feedback"></div>
                        </div>
                        
                        <div class="form-consent">
                            <div class="checkbox-group">
                                <label for="terms_consent" class="checkbox-label">
                                    <input type="checkbox" id="terms_consent" name="terms" required>
                                    <span class="checkbox-custom"></span>
                                    <span><?php echo sprintf($lang->get('register_terms_consent_full'), $lang->pageUrl('terms'), $lang->pageUrl('privacy')); ?></span>
                                </label>
                            </div>
                            <div class="checkbox-group">
                                <label for="newsletter_consent" class="checkbox-label">
                                    <input type="checkbox" id="newsletter_consent" name="newsletter_consent">
                                    <span class="checkbox-custom"></span>
                                    <span><?php echo $lang->get('register_newsletter_consent'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline" id="prevBtn" onclick="previousStep()"><?php echo $lang->get('btn_previous'); ?></button>
                        <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()"><?php echo $lang->get('btn_next'); ?></button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="btn-text"><?php echo $lang->get('auth_register_btn'); ?></span>
                            <div class="btn-loader"></div>
                        </button>
                    </div>
                </form>
                
                <div class="form-footer">
                    <p class="auth-switch">
                        <?php echo $lang->get('auth_has_account'); ?>
                        <a href="<?php echo $lang->pageUrl('login'); ?>" class="auth-link">
                            <?php echo $lang->get('auth_signin_here'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        let currentStep = 1;
        const totalSteps = 3;

        function updateStepDisplay() {
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });
            document.querySelector(`.form-step[data-step="${currentStep}"]`).classList.add('active');

            document.querySelectorAll('.progress-steps .step').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                const stepNumber = index + 1;
                if (stepNumber < currentStep) {
                    step.classList.add('completed');
                }
                if (stepNumber === currentStep) {
                    step.classList.add('active');
                }
            });

            const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.querySelector('.progress-fill').style.width = `${progressPercent}%`;

            document.getElementById('prevBtn').style.display = currentStep > 1 ? 'inline-flex' : 'none';
            document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
        }

        function nextStep() {
            if (validateCurrentStep() && currentStep < totalSteps) {
                currentStep++;
                updateStepDisplay();
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }

        function validateCurrentStep() {
            const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
            let isValid = true;

            inputs.forEach(input => {
                const formGroup = input.closest('.form-group, .checkbox-group');
                let feedbackEl = null;

                if (formGroup && formGroup.classList.contains('form-group')) {
                    feedbackEl = formGroup.querySelector('.form-feedback');
                }

                if (!input.checkValidity()) {
                    isValid = false;
                    if (formGroup) {
                        input.classList.add('error');
                        if (feedbackEl) feedbackEl.textContent = input.validationMessage;
                    }
                } else {
                    if (formGroup) {
                        input.classList.remove('error');
                        input.classList.add('success');
                        if (feedbackEl) feedbackEl.textContent = '';
                    }
                }
            });
            
            if (currentStep === 1) {
                const password = document.getElementById('password');
                const passwordConfirm = document.getElementById('password_confirm');
                const formGroup = passwordConfirm.closest('.form-group');
                const feedbackEl = formGroup ? formGroup.querySelector('.form-feedback') : null;

                if (password.value && passwordConfirm.value && password.value !== passwordConfirm.value) {
                    isValid = false;
                    if (formGroup) {
                        passwordConfirm.classList.add('error');
                        if(feedbackEl) feedbackEl.textContent = '<?php echo $lang->get("js_passwords_do_not_match"); ?>';
                    }
                }
            }

            return isValid;
        }

        function submitRegistration(event) {
            event.preventDefault();
            
            if (!validateCurrentStep()) {
                alert('<?php echo $lang->get("js_form_errors_or_terms"); ?>');
                return false;
            }

            const submitBtn = document.getElementById('submitBtn');
            const form = event.target;
            const alertSuccess = document.querySelector('.alert-success');
            const alertError = document.querySelector('.alert-error');
            const errorList = alertError.querySelector('.error-list');

            alertSuccess.style.display = 'none';
            alertError.style.display = 'none';
            
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            const formData = new FormData(form);

            fetch('/ajax/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    form.style.display = 'none';
                    alertSuccess.style.display = 'flex';
                    
                    setTimeout(() => {
                        window.location.href = '<?php echo $lang->pageUrl('login'); ?>?status=registered';
                    }, 3000);
                } else {
                    errorList.innerHTML = `<li>${data.message || '<?php echo $lang->get("js_error_occurred"); ?>'}</li>`;
                    alertError.style.display = 'flex';
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Registration Fetch Error:', error);
                errorList.innerHTML = '<li><?php echo $lang->get("js_communication_error"); ?></li>';
                alertError.style.display = 'flex';
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

        document.addEventListener('DOMContentLoaded', function() {
            updateStepDisplay();
        });
    </script>
</body>
</html>