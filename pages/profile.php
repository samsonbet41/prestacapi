<?php
require_once 'includes/auth-check.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';

$lang = Language::getInstance();
$seo = new SEO();

$pageTitle = $lang->get('profile_title') . ' - ' . $lang->get('site_name');
$pageDescription = 'Gérez vos informations personnelles, mettez à jour vos coordonnées et sécurisez votre compte PrestaCapi.';

$errors = [];
$success = false;
$activeTab = $_GET['tab'] ?? 'personal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'date_of_birth' => $_POST['date_of_birth'] ?? '',
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'country' => trim($_POST['country'] ?? '')
        ];
        
        $result = $user->updateProfile($currentUser['id'], $data);
        
        if ($result['success']) {
            $success = true;
            $_SESSION['profile_updated'] = $result['message'];
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = $lang->get('validation_passwords_match');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = $lang->get('validation_min_length', ['min' => 8]);
        } else {
            $result = $user->changePassword($currentUser['id'], $currentPassword, $newPassword);
            
            if ($result['success']) {
                $success = true;
                $_SESSION['password_changed'] = $result['message'];
                header('Location: ' . $_SERVER['REQUEST_URI'] . '?tab=security');
                exit;
            } else {
                $errors[] = $result['message'];
                $activeTab = 'security';
            }
        }
    }
}

if (isset($_SESSION['profile_updated'])) {
    $success = true;
    $successMessage = $_SESSION['profile_updated'];
    unset($_SESSION['profile_updated']);
}

if (isset($_SESSION['password_changed'])) {
    $success = true;
    $successMessage = $_SESSION['password_changed'];
    unset($_SESSION['password_changed']);
}

$countries = [
    'France', 'Belgique', 'Suisse', 'Luxembourg', 'Monaco',
    'Canada', 'Allemagne', 'Espagne', 'Italie', 'Portugal',
    'Maroc', 'Tunisie', 'Algérie', 'Sénégal', 'Côte d\'Ivoire'
];

$userStats = $user->getDashboardStats($currentUser['id']);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>
    
    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-content">
                <div class="page-header">
                    <div class="page-title-section">
                        <h1 class="page-title"><?php echo $lang->get('profile_title'); ?></h1>
                        <p class="page-subtitle">Gérez vos informations personnelles et paramètres de sécurité</p>
                    </div>
                    
                    <div class="profile-summary">
                        <div class="profile-avatar">
                            <div class="avatar-image">
                                <?php echo strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1)); ?>
                            </div>
                            <div class="avatar-status">
                                <i class="icon-check-circle"></i>
                            </div>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-name">
                                <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                            </h2>
                            <p class="profile-email"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                            <div class="profile-badges">
                                <span class="badge verified">Compte vérifié</span>
                                <?php if ($currentUser['email_verified']): ?>
                                    <span class="badge email-verified">Email vérifié</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($success && isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <i class="icon-check-circle"></i>
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                <?php endif; ?>
                
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
                
                <div class="profile-content">
                    <div class="profile-tabs">
                        <button class="tab-button <?php echo $activeTab === 'personal' ? 'active' : ''; ?>" 
                                onclick="switchTab('personal')">
                            <i class="icon-user"></i>
                            <span><?php echo $lang->get('profile_personal_info'); ?></span>
                        </button>
                        
                        <button class="tab-button <?php echo $activeTab === 'contact' ? 'active' : ''; ?>" 
                                onclick="switchTab('contact')">
                            <i class="icon-phone"></i>
                            <span><?php echo $lang->get('profile_contact_info'); ?></span>
                        </button>
                        
                        <button class="tab-button <?php echo $activeTab === 'address' ? 'active' : ''; ?>" 
                                onclick="switchTab('address')">
                            <i class="icon-location"></i>
                            <span><?php echo $lang->get('profile_address_info'); ?></span>
                        </button>
                        
                        <button class="tab-button <?php echo $activeTab === 'security' ? 'active' : ''; ?>" 
                                onclick="switchTab('security')">
                            <i class="icon-shield"></i>
                            <span><?php echo $lang->get('security_title'); ?></span>
                        </button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-panel <?php echo $activeTab === 'personal' ? 'active' : ''; ?>" data-tab="personal">
                            <div class="panel-header">
                                <h3 class="panel-title">Informations personnelles</h3>
                                <p class="panel-subtitle">Vos informations de base et date de naissance</p>
                            </div>
                            
                            <form class="profile-form" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name"><?php echo $lang->get('auth_first_name'); ?> *</label>
                                        <input type="text" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="<?php echo htmlspecialchars($currentUser['first_name']); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="last_name"><?php echo $lang->get('auth_last_name'); ?> *</label>
                                        <input type="text" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="<?php echo htmlspecialchars($currentUser['last_name']); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="date_of_birth"><?php echo $lang->get('profile_date_of_birth'); ?></label>
                                    <input type="date" 
                                           id="date_of_birth" 
                                           name="date_of_birth" 
                                           value="<?php echo htmlspecialchars($currentUser['date_of_birth']); ?>"
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                    <div class="form-hint">Utilisé pour vérifier votre identité</div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $lang->get('profile_update_btn'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-panel <?php echo $activeTab === 'contact' ? 'active' : ''; ?>" data-tab="contact">
                            <div class="panel-header">
                                <h3 class="panel-title">Informations de contact</h3>
                                <p class="panel-subtitle">Email, téléphone et WhatsApp</p>
                            </div>
                            
                            <form class="profile-form" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" 
                                           id="email" 
                                           value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                           disabled>
                                    <div class="form-hint">
                                        <i class="icon-info"></i>
                                        Pour changer votre email, contactez notre support
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="phone"><?php echo $lang->get('auth_phone'); ?> *</label>
                                        <input type="tel" 
                                               id="phone" 
                                               name="phone" 
                                               value="<?php echo htmlspecialchars($currentUser['phone']); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="whatsapp"><?php echo $lang->get('auth_whatsapp'); ?></label>
                                        <input type="tel" 
                                               id="whatsapp" 
                                               name="whatsapp" 
                                               value="<?php echo htmlspecialchars($currentUser['whatsapp']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $lang->get('profile_update_btn'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-panel <?php echo $activeTab === 'address' ? 'active' : ''; ?>" data-tab="address">
                            <div class="panel-header">
                                <h3 class="panel-title">Adresse</h3>
                                <p class="panel-subtitle">Votre adresse de résidence</p>
                            </div>
                            
                            <form class="profile-form" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="address"><?php echo $lang->get('profile_address'); ?></label>
                                    <input type="text" 
                                           id="address" 
                                           name="address" 
                                           value="<?php echo htmlspecialchars($currentUser['address']); ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city"><?php echo $lang->get('profile_city'); ?></label>
                                        <input type="text" 
                                               id="city" 
                                               name="city" 
                                               value="<?php echo htmlspecialchars($currentUser['city']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="postal_code"><?php echo $lang->get('profile_postal_code'); ?></label>
                                        <input type="text" 
                                               id="postal_code" 
                                               name="postal_code" 
                                               value="<?php echo htmlspecialchars($currentUser['postal_code']); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="country"><?php echo $lang->get('profile_country'); ?></label>
                                    <select id="country" name="country">
                                        <?php foreach ($countries as $country): ?>
                                            <option value="<?php echo htmlspecialchars($country); ?>" 
                                                    <?php echo ($currentUser['country'] === $country) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($country); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo $lang->get('profile_update_btn'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <div class="tab-panel <?php echo $activeTab === 'security' ? 'active' : ''; ?>" data-tab="security">
                            <div class="panel-header">
                                <h3 class="panel-title">Sécurité</h3>
                                <p class="panel-subtitle">Mot de passe et paramètres de sécurité</p>
                            </div>
                            
                            <div class="security-section">
                                <div class="security-item">
                                    <div class="security-info">
                                        <h4>Mot de passe</h4>
                                        <p>Dernière modification : <?php echo $lang->formatDate($currentUser['updated_at']); ?></p>
                                    </div>
                                    <button class="btn btn-outline" onclick="togglePasswordForm()">
                                        Changer le mot de passe
                                    </button>
                                </div>
                                
                                <form class="password-form" id="passwordForm" method="POST" style="display: none;">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="form-group">
                                        <label for="current_password"><?php echo $lang->get('profile_current_password'); ?></label>
                                        <div class="password-input">
                                            <input type="password" id="current_password" name="current_password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                                <i class="icon-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password"><?php echo $lang->get('profile_new_password'); ?></label>
                                        <div class="password-input">
                                            <input type="password" id="new_password" name="new_password" required minlength="8">
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="icon-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="passwordStrengthNew"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password"><?php echo $lang->get('profile_confirm_new_password'); ?></label>
                                        <div class="password-input">
                                            <input type="password" id="confirm_password" name="confirm_password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="icon-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn-outline" onclick="togglePasswordForm()">
                                            Annuler
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            Mettre à jour
                                        </button>
                                    </div>
                                </form>
                                
                                <div class="security-item">
                                    <div class="security-info">
                                        <h4>Connexions récentes</h4>
                                        <p>Surveillez l'activité de votre compte</p>
                                    </div>
                                    <button class="btn btn-outline" onclick="showLoginHistory()">
                                        Voir l'historique
                                    </button>
                                </div>
                                
                                <div class="security-item">
                                    <div class="security-info">
                                        <h4>Authentification à deux facteurs</h4>
                                        <p>Sécurité renforcée (bientôt disponible)</p>
                                    </div>
                                    <button class="btn btn-outline" disabled>
                                        Configurer 2FA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="/js/modules/dashboard.js"></script>
    <script src="/js/modules/forms.js"></script>
    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }
        
        function togglePasswordForm() {
            const form = document.getElementById('passwordForm');
            const isVisible = form.style.display !== 'none';
            form.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                document.getElementById('current_password').focus();
            }
        }
        
        function showLoginHistory() {
            console.log('Affichage historique connexions');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initPasswordStrength();
            loadActivityTimeline();
        });
        
        function loadActivityTimeline() {
            setTimeout(() => {
                const timeline = document.getElementById('activityTimeline');
                timeline.innerHTML = `
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Connexion au compte</h5>
                            <p>Dernière connexion aujourd'hui</p>
                            <span class="timeline-date">Aujourd'hui</span>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Profil mis à jour</h5>
                            <p>Informations personnelles modifiées</p>
                            <span class="timeline-date">Il y a 2 jours</span>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h5>Compte créé</h5>
                            <p>Inscription sur PrestaCapi</p>
                            <span class="timeline-date">${new Date('<?php echo $currentUser['created_at']; ?>').toLocaleDateString('fr-FR')}</span>
                        </div>
                    </div>
                `;
            }, 1000);
        }
    </script>
</body>
</html>