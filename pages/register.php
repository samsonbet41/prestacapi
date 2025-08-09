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

$pageTitle = $lang->get('auth_register_title') . ' - ' . $lang->get('site_name');
$pageDescription = 'Créez votre compte PrestaCapi en quelques minutes. Financement rapide, processus sécurisé, réponse en 24-48h.';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'country' => trim($_POST['country'] ?? 'France')
    ];
    
    if ($data['password'] !== $data['password_confirm']) {
        $errors[] = $lang->get('validation_passwords_match');
    }
    
    if (strlen($data['password']) < 8) {
        $errors[] = $lang->get('validation_min_length', ['min' => 8]);
    }
    
    if (empty($errors)) {
        $result = $user->register($data);
        
        if ($result['success']) {
            $success = true;
            $_SESSION['registration_success'] = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    
    <?php echo $lang->generateHreflang('register'); ?>
    <link rel="canonical" href="<?php echo $lang->getCanonicalUrl('register'); ?>">
    
    <?php echo $seo->generateOpenGraphTags([
        'title' => $pageTitle,
        'description' => $pageDescription,
        'url' => $lang->getCanonicalUrl('register')
    ]); ?>
    
    <!-- CSS Links -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/register.css">
    <link rel="stylesheet" href="/css/auth.css">
    <link rel="stylesheet" href="/css/responsive.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
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
                    <p class="form-subtitle">Créez votre compte et commencez votre demande</p>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div class="alert-content">
                                <h3>Inscription réussie !</h3>
                                <p>Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.</p>
                                <a href="<?php echo $lang->pageUrl('login'); ?>" class="btn btn-primary">
                                    Se connecter
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <div class="alert-content">
                                    <h3>Erreur</h3>
                                    <ul class="error-list" style="list-style: none; padding-left: 0;">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <form class="auth-form" method="POST" onsubmit="submitRegistration(event)">
                            <div class="form-progress">
                                <div class="progress-steps">
                                    <div class="step active" data-step="1">
                                        <span class="step-number">1</span>
                                        <span class="step-label">Compte</span>
                                    </div>
                                    <div class="step" data-step="2">
                                        <span class="step-number">2</span>
                                        <span class="step-label">Infos</span>
                                    </div>
                                    <div class="step" data-step="3">
                                        <span class="step-number">3</span>
                                        <span class="step-label">Adresse</span>
                                    </div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 33.33%"></div>
                                </div>
                            </div>
                            
                            <!-- Step 1: Account Info -->
                            <div class="form-step active" data-step="1">
                                <h3 class="step-title">Créer votre compte</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label"><?php echo $lang->get('auth_first_name'); ?> <span class="required">*</span></label>
                                        <input type="text" id="first_name" name="first_name" class="form-input" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required autocomplete="given-name">
                                        <div class="form-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name" class="form-label"><?php echo $lang->get('auth_last_name'); ?> <span class="required">*</span></label>
                                        <input type="text" id="last_name" name="last_name" class="form-input" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required autocomplete="family-name">
                                        <div class="form-feedback"></div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php echo $lang->get('auth_email'); ?> <span class="required">*</span></label>
                                    <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
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
                            
                            <!-- Step 2: Personal Info -->
                            <div class="form-step" data-step="2">
                                <h3 class="step-title">Vos informations personnelles</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone" class="form-label"><?php echo $lang->get('auth_phone'); ?> <span class="required">*</span></label>
                                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required autocomplete="tel" placeholder="+33 6 12 34 56 78">
                                        <div class="form-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="whatsapp" class="form-label"><?php echo $lang->get('auth_whatsapp'); ?></label>
                                        <input type="tel" id="whatsapp" name="whatsapp" class="form-input" value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>" autocomplete="tel" placeholder="+33 6 12 34 56 78">
                                        <div class="form-feedback"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="date_of_birth" class="form-label"><?php echo $lang->get('profile_date_of_birth'); ?> <span class="required">*</span></label>
                                    <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                    <div class="form-feedback"></div>
                                    <div class="form-hint">Vous devez être majeur(e) pour utiliser nos services.</div>
                                </div>
                            </div>
                            
                            <!-- Step 3: Address -->
                            <div class="form-step" data-step="3">
                                <h3 class="step-title">Votre adresse</h3>
                                <div class="form-group">
                                    <label for="address" class="form-label"><?php echo $lang->get('profile_address'); ?></label>
                                    <input type="text" id="address" name="address" class="form-input" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" autocomplete="street-address" placeholder="123 rue de la République">
                                    <div class="form-feedback"></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city" class="form-label"><?php echo $lang->get('profile_city'); ?></label>
                                        <input type="text" id="city" name="city" class="form-input" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" autocomplete="address-level2" placeholder="Paris">
                                        <div class="form-feedback"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="postal_code" class="form-label"><?php echo $lang->get('profile_postal_code'); ?></label>
                                        <input type="text" id="postal_code" name="postal_code" class="form-input" value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>" autocomplete="postal-code" placeholder="75001">
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
                                            <input type="checkbox" id="terms_consent" name="terms_consent" required>
                                            <span class="checkbox-custom"></span>
                                            <span>J'accepte les <a href="<?php echo $lang->pageUrl('terms'); ?>" target="_blank">conditions d'utilisation</a> et la <a href="<?php echo $lang->pageUrl('privacy'); ?>" target="_blank">politique de confidentialité</a>.</span>
                                        </label>
                                    </div>
                                    <div class="checkbox-group">
                                        <label for="newsletter_consent" class="checkbox-label">
                                            <input type="checkbox" id="newsletter_consent" name="newsletter_consent">
                                            <span class="checkbox-custom"></span>
                                            <span>Je souhaite recevoir les actualités et conseils de PrestaCapi.</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-outline" id="prevBtn" onclick="previousStep()" style="display: none;">Précédent</button>
                                <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">Suivant</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">
                                    <span class="btn-text"><?php echo $lang->get('auth_register_btn'); ?></span>
                                    <div class="btn-loader"></div>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
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
                if (index < currentStep) {
                    step.classList.add('completed');
                }
                if (index + 1 === currentStep) {
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
                const feedbackEl = input.closest('.form-group').querySelector('.form-feedback');
                if (!input.checkValidity()) {
                    isValid = false;
                    input.classList.add('error');
                    if (feedbackEl) feedbackEl.textContent = input.validationMessage;
                } else {
                    input.classList.remove('error');
                    input.classList.add('success');
                    if (feedbackEl) feedbackEl.textContent = '';
                }
            });
            
            if (currentStep === 1) {
                const password = document.getElementById('password');
                const passwordConfirm = document.getElementById('password_confirm');
                const feedbackEl = passwordConfirm.closest('.form-group').querySelector('.form-feedback');

                if (password.value !== passwordConfirm.value) {
                    isValid = false;
                    passwordConfirm.classList.add('error');
                    if(feedbackEl) feedbackEl.textContent = 'Les mots de passe ne correspondent pas.';
                } else if (passwordConfirm.value) {
                    passwordConfirm.classList.remove('error');
                    passwordConfirm.classList.add('success');
                     if(feedbackEl) feedbackEl.textContent = '';
                }
            }

            return isValid;
        }

        function submitRegistration(event) {
            event.preventDefault();
            if (!validateCurrentStep()) {
                alert('Veuillez corriger les erreurs avant de continuer.');
                return false;
            }
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            setTimeout(() => {
                event.target.submit();
            }, 500);
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