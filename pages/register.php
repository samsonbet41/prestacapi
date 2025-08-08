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
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <?php include 'includes/header.php'; ?>
    
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-content">
                <div class="auth-visual">
                    <div class="visual-content">
                        <h2 class="visual-title">Rejoignez PrestaCapi</h2>
                        <p class="visual-subtitle">Plus de 10 000 clients nous font déjà confiance</p>
                        
                        <div class="benefits-list">
                            <div class="benefit-item">
                                <i class="icon-check"></i>
                                <span>Inscription gratuite en 3 minutes</span>
                            </div>
                            <div class="benefit-item">
                                <i class="icon-check"></i>
                                <span>Réponse rapide sous 24-48h</span>
                            </div>
                            <div class="benefit-item">
                                <i class="icon-check"></i>
                                <span>Processus 100% digital</span>
                            </div>
                            <div class="benefit-item">
                                <i class="icon-check"></i>
                                <span>Montants jusqu'à 50 000€</span>
                            </div>
                        </div>
                        
                        <div class="testimonial-preview">
                            <blockquote>
                                "Processus simple et rapide. J'ai eu ma réponse en 24h !"
                            </blockquote>
                            <cite>
                                <strong>Marie D.</strong>
                                <span>⭐⭐⭐⭐⭐</span>
                            </cite>
                        </div>
                    </div>
                </div>
                
                <div class="auth-form-section">
                    <div class="form-header">
                        <h1 class="form-title"><?php echo $lang->get('auth_register_title'); ?></h1>
                        <p class="form-subtitle">Créez votre compte et commencez votre demande</p>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="icon-check-circle"></i>
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
                                    <i class="icon-alert-circle"></i>
                                    <ul class="error-list">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
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
                                            <span class="step-label">Informations</span>
                                        </div>
                                        <div class="step" data-step="3">
                                            <span class="step-number">3</span>
                                            <span class="step-label">Adresse</span>
                                        </div>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 33%"></div>
                                    </div>
                                </div>
                                
                                <div class="form-step active" data-step="1">
                                    <h3 class="step-title">Créer votre compte</h3>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="first_name"><?php echo $lang->get('auth_first_name'); ?> *</label>
                                            <input type="text" 
                                                   id="first_name" 
                                                   name="first_name" 
                                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                                                   required 
                                                   autocomplete="given-name">
                                            <div class="form-feedback"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="last_name"><?php echo $lang->get('auth_last_name'); ?> *</label>
                                            <input type="text" 
                                                   id="last_name" 
                                                   name="last_name" 
                                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                                                   required 
                                                   autocomplete="family-name">
                                            <div class="form-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email"><?php echo $lang->get('auth_email'); ?> *</label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               required 
                                               autocomplete="email">
                                        <div class="form-feedback"></div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="password"><?php echo $lang->get('auth_password'); ?> *</label>
                                            <div class="password-input">
                                                <input type="password" 
                                                       id="password" 
                                                       name="password" 
                                                       required 
                                                       autocomplete="new-password"
                                                       minlength="8">
                                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                                    <i class="icon-eye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength" id="passwordStrength"></div>
                                            <div class="form-feedback"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="password_confirm"><?php echo $lang->get('auth_password_confirm'); ?> *</label>
                                            <div class="password-input">
                                                <input type="password" 
                                                       id="password_confirm" 
                                                       name="password_confirm" 
                                                       required 
                                                       autocomplete="new-password">
                                                <button type="button" class="password-toggle" onclick="togglePassword('password_confirm')">
                                                    <i class="icon-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-step" data-step="2">
                                    <h3 class="step-title">Vos informations personnelles</h3>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="phone"><?php echo $lang->get('auth_phone'); ?> *</label>
                                            <input type="tel" 
                                                   id="phone" 
                                                   name="phone" 
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                                   required 
                                                   autocomplete="tel"
                                                   placeholder="+33 6 12 34 56 78">
                                            <div class="form-feedback"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="whatsapp"><?php echo $lang->get('auth_whatsapp'); ?></label>
                                            <input type="tel" 
                                                   id="whatsapp" 
                                                   name="whatsapp" 
                                                   value="<?php echo htmlspecialchars($_POST['whatsapp'] ?? ''); ?>"
                                                   autocomplete="tel"
                                                   placeholder="+33 6 12 34 56 78">
                                            <div class="form-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="date_of_birth"><?php echo $lang->get('profile_date_of_birth'); ?> *</label>
                                        <input type="date" 
                                               id="date_of_birth" 
                                               name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                                               required 
                                               max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                        <div class="form-feedback"></div>
                                        <div class="form-hint">Vous devez être majeur(e) pour utiliser nos services</div>
                                    </div>
                                </div>
                                
                                <div class="form-step" data-step="3">
                                    <h3 class="step-title">Votre adresse</h3>
                                    
                                    <div class="form-group">
                                        <label for="address"><?php echo $lang->get('profile_address'); ?></label>
                                        <input type="text" 
                                               id="address" 
                                               name="address" 
                                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                                               autocomplete="street-address"
                                               placeholder="123 rue de la République">
                                        <div class="form-feedback"></div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="city"><?php echo $lang->get('profile_city'); ?></label>
                                            <input type="text" 
                                                   id="city" 
                                                   name="city" 
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
                                                   autocomplete="address-level2"
                                                   placeholder="Paris">
                                            <div class="form-feedback"></div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="postal_code"><?php echo $lang->get('profile_postal_code'); ?></label>
                                            <input type="text" 
                                                   id="postal_code" 
                                                   name="postal_code" 
                                                   value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>"
                                                   autocomplete="postal-code"
                                                   placeholder="75001">
                                            <div class="form-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="country"><?php echo $lang->get('profile_country'); ?></label>
                                        <select id="country" name="country" autocomplete="country">
                                            <?php foreach ($countries as $country): ?>
                                                <option value="<?php echo htmlspecialchars($country); ?>" 
                                                        <?php echo (($_POST['country'] ?? 'France') === $country) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($country); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-feedback"></div>
                                    </div>
                                    
                                    <div class="form-consent">
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="terms_consent" name="terms_consent" required>
                                            <label for="terms_consent">
                                                J'accepte les <a href="<?php echo $lang->pageUrl('terms'); ?>" target="_blank">conditions d'utilisation</a> 
                                                et la <a href="<?php echo $lang->pageUrl('privacy'); ?>" target="_blank">politique de confidentialité</a>
                                            </label>
                                        </div>
                                        
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="newsletter_consent" name="newsletter_consent">
                                            <label for="newsletter_consent">
                                                Je souhaite recevoir les actualités et conseils de PrestaCapi
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-outline" id="prevBtn" onclick="previousStep()" style="display: none;">
                                        Précédent
                                    </button>
                                    
                                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                                        Suivant
                                    </button>
                                    
                                    <button type="submit" class="btn btn-primary btn-loading" id="submitBtn" style="display: none;">
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
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="/js/modules/forms.js"></script>
    <script src="/js/modules/auth.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 3;
        
        function updateStepDisplay() {
            document.querySelectorAll('.form-step').forEach((step, index) => {
                step.classList.toggle('active', index + 1 === currentStep);
            });
            
            document.querySelectorAll('.progress-steps .step').forEach((step, index) => {
                step.classList.toggle('active', index + 1 <= currentStep);
            });
            
            const progressPercent = (currentStep / totalSteps) * 100;
            document.querySelector('.progress-fill').style.width = progressPercent + '%';
            
            document.getElementById('prevBtn').style.display = currentStep > 1 ? 'block' : 'none';
            document.getElementById('nextBtn').style.display = currentStep < totalSteps ? 'block' : 'none';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'block' : 'none';
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
            const requiredInputs = currentStepElement.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });
            
            if (currentStep === 1) {
                const password = document.getElementById('password').value;
                const passwordConfirm = document.getElementById('password_confirm').value;
                
                if (password !== passwordConfirm) {
                    isValid = false;
                    document.getElementById('password_confirm').classList.add('error');
                }
            }
            
            return isValid;
        }
        
        function submitRegistration(event) {
            event.preventDefault();
            
            if (!validateCurrentStep()) {
                return false;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            
            setTimeout(() => {
                event.target.submit();
            }, 500);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateStepDisplay();
            initPasswordStrength();
            initPhoneValidation();
        });
    </script>
</body>
</html>