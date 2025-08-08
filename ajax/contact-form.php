<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

require_once '../classes/Database.php';
require_once '../classes/Mailer.php';
require_once '../classes/Language.php';

session_start();

try {
    $lang = Language::getInstance();
    $db = Database::getInstance();
    
    $requiredFields = ['name', 'email', 'subject', 'message'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
            exit;
        }
    }
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $category = $_POST['category'] ?? 'general';
    
    if (strlen($name) < 2) {
        echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 2 caractères']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Adresse email invalide']);
        exit;
    }
    
    if (strlen($subject) < 5) {
        echo json_encode(['success' => false, 'message' => 'Le sujet doit contenir au moins 5 caractères']);
        exit;
    }
    
    if (strlen($message) < 20) {
        echo json_encode(['success' => false, 'message' => 'Le message doit contenir au moins 20 caractères']);
        exit;
    }
    
    if (strlen($message) > 2000) {
        echo json_encode(['success' => false, 'message' => 'Le message ne doit pas dépasser 2000 caractères']);
        exit;
    }
    
    if (!empty($phone) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide']);
        exit;
    }
    
    $validCategories = ['general', 'support', 'loan', 'partnership', 'complaint', 'other'];
    if (!in_array($category, $validCategories)) {
        $category = 'general';
    }
    
    if (isset($_SESSION['last_contact_time']) && (time() - $_SESSION['last_contact_time']) < 300) {
        echo json_encode(['success' => false, 'message' => 'Veuillez attendre 5 minutes entre chaque message']);
        exit;
    }
    
    if (containsSpam($message) || containsSpam($subject)) {
        echo json_encode(['success' => false, 'message' => 'Message détecté comme indésirable']);
        exit;
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $contactData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'subject' => $subject,
        'message' => $message,
        'category' => $category,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'language' => $lang->getCurrentLanguage(),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $db->insert('contact_messages', $contactData);
    } catch (Exception $e) {
        $createTableQuery = "
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            company VARCHAR(255),
            subject VARCHAR(500) NOT NULL,
            message TEXT NOT NULL,
            category ENUM('general', 'support', 'loan', 'partnership', 'complaint', 'other') DEFAULT 'general',
            ip_address VARCHAR(45),
            user_agent TEXT,
            language VARCHAR(5),
            status ENUM('new', 'read', 'replied', 'closed') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->query($createTableQuery);
        $db->insert('contact_messages', $contactData);
    }
    
    $mailer = new Mailer();
    
    $adminSubject = "Nouveau message de contact - PrestaCapi";
    $adminMessage = buildAdminContactEmail($contactData);
    $mailer->send('contact@prestacapi.com', $adminSubject, $adminMessage, [], true);
    
    $userSubject = "Votre message a été reçu - PrestaCapi";
    $userMessage = buildUserContactEmail($contactData);
    $mailer->send($email, $userSubject, $userMessage, [], true);
    
    $_SESSION['last_contact_time'] = time();
    
    $db->logActivity(null, null, 'contact_form_submitted', "Message de contact reçu de: $email");
    
    echo json_encode([
        'success' => true,
        'message' => 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
        'estimated_response' => '24-48 heures'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur AJAX contact-form: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'envoi']);
}

function containsSpam($text) {
    $spamKeywords = [
        'viagra', 'casino', 'lottery', 'winner', 'congratulations',
        'million dollars', 'nigerian prince', 'click here now',
        'free money', 'get rich quick', 'amazing offer',
        'limited time', 'act now', 'no obligation'
    ];
    
    $text = strtolower($text);
    
    foreach ($spamKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }
    
    if (preg_match('/https?:\/\/[^\s]+/', $text)) {
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/', $text);
        if ($linkCount > 2) {
            return true;
        }
    }
    
    $suspiciousPatterns = [
        '/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/',
        '/\b[A-Z]{2,}\s+[A-Z]{2,}\s+[A-Z]{2,}\b/',
        '/\$\d+[\.,]?\d*\s*(million|billion|thousand)/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    
    return false;
}

function buildAdminContactEmail($data) {
    $categoryNames = [
        'general' => 'Demande générale',
        'support' => 'Support technique',
        'loan' => 'Question sur les prêts',
        'partnership' => 'Partenariat',
        'complaint' => 'Réclamation',
        'other' => 'Autre'
    ];
    
    $categoryName = $categoryNames[$data['category']] ?? 'Non spécifié';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Nouveau message de contact</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1F3B73; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; margin-top: 5px; }
            .message-box { background: white; padding: 15px; border-left: 4px solid #00B8D9; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nouveau message de contact</h1>
                <p>PrestaCapi.com</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Nom :</div>
                    <div class='value'>" . htmlspecialchars($data['name']) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Email :</div>
                    <div class='value'>" . htmlspecialchars($data['email']) . "</div>
                </div>
                " . (!empty($data['phone']) ? "
                <div class='field'>
                    <div class='label'>Téléphone :</div>
                    <div class='value'>" . htmlspecialchars($data['phone']) . "</div>
                </div>" : "") . "
                " . (!empty($data['company']) ? "
                <div class='field'>
                    <div class='label'>Entreprise :</div>
                    <div class='value'>" . htmlspecialchars($data['company']) . "</div>
                </div>" : "") . "
                <div class='field'>
                    <div class='label'>Catégorie :</div>
                    <div class='value'>{$categoryName}</div>
                </div>
                <div class='field'>
                    <div class='label'>Sujet :</div>
                    <div class='value'>" . htmlspecialchars($data['subject']) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Message :</div>
                    <div class='message-box'>" . nl2br(htmlspecialchars($data['message'])) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Date :</div>
                    <div class='value'>" . date('d/m/Y H:i:s') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>IP :</div>
                    <div class='value'>{$data['ip_address']}</div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}

function buildUserContactEmail($data) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Message reçu - PrestaCapi</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%); color: white; padding: 30px 20px; text-align: center; }
            .content { padding: 30px 20px; }
            .message-box { background: #e8f4fd; border: 1px solid #00B8D9; border-radius: 8px; padding: 20px; margin: 20px 0; }
            .footer { background: #1F3B73; color: white; padding: 20px; text-align: center; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Message bien reçu !</h1>
                <p>Merci de nous avoir contactés</p>
            </div>
            <div class='content'>
                <p>Bonjour " . htmlspecialchars($data['name']) . ",</p>
                
                <p>Nous avons bien reçu votre message concernant : <strong>" . htmlspecialchars($data['subject']) . "</strong></p>
                
                <div class='message-box'>
                    <h3>Récapitulatif de votre message :</h3>
                    <p>" . nl2br(htmlspecialchars($data['message'])) . "</p>
                </div>
                
                <p>Notre équipe vous répondra dans les <strong>24-48 heures</strong> suivant votre demande.</p>
                
                <p>En attendant, n'hésitez pas à consulter :</p>
                <ul>
                    <li><a href='https://prestacapi.com/faq'>Notre FAQ</a></li>
                    <li><a href='https://prestacapi.com/blog'>Nos conseils financiers</a></li>
                    <li><a href='https://prestacapi.com/dashboard'>Votre espace personnel</a> (si vous avez un compte)</li>
                </ul>
                
                <p>Pour toute urgence, vous pouvez nous contacter :</p>
                <ul>
                    <li>📞 Téléphone : +33 1 23 45 67 89</li>
                    <li>💬 WhatsApp : +33 6 12 34 56 78</li>
                </ul>
                
                <p>Cordialement,<br>
                L'équipe PrestaCapi</p>
            </div>
            <div class='footer'>
                <p><strong>PrestaCapi</strong> - Votre partenaire financier de confiance depuis 2008</p>
                <p>Cet email a été envoyé automatiquement. Merci de ne pas répondre à cette adresse.</p>
            </div>
        </div>
    </body>
    </html>";
}