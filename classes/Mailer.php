<?php
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("Erreur : vendor/autoload.php introuvable à $autoloadPath");
}
require $autoloadPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $fromEmail = "no-reply@prestacapi.com";
    private $fromName = "PrestaCapi";
    private $adminEmail = "support@prestacapi.com";
    private $smtpDebug = false;
    private $useSmtp = true;
    private $lastError = '';
    
    private $smtpConfig = [
        'host' => 'smtp.hostinger.com',
        'username' => 'no-reply@prestacapi.com',
        'password' => '6^uAW!2d=',
        'port' => 465,
        'encryption' => 'ssl',
        'timeout' => 30
    ];

    private $lang;
    
    public function __construct() {
        if (class_exists('Language')) {
            $this->lang = Language::getInstance();
        }
        $this->loadConfigFromDatabase();
        $this->log("Mailer PrestaCapi initialisé");
    }

    
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [PRESTACAPI-MAILER-$level] $message";
        error_log($logMessage);
        
        if ($this->smtpDebug) {
            echo $logMessage . "<br>\n";
        }
    }
    
    private function loadConfigFromDatabase() {
        try {
            if (class_exists('Database')) {
                $db = Database::getInstance();
                
                $emailSettings = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'");
                
                foreach ($emailSettings as $setting) {
                    switch ($setting['setting_key']) {
                        case 'email_from':
                            $this->fromEmail = $setting['setting_value'];
                            break;
                        case 'email_from_name':
                            $this->fromName = $setting['setting_value'];
                            break;
                        case 'email_admin':
                            $this->adminEmail = $setting['setting_value'];
                            break;
                        case 'email_smtp_host':
                            $this->smtpConfig['host'] = $setting['setting_value'];
                            break;
                        case 'email_smtp_username':
                            $this->smtpConfig['username'] = $setting['setting_value'];
                            break;
                        case 'email_smtp_password':
                            $this->smtpConfig['password'] = $setting['setting_value'];
                            break;
                        case 'email_smtp_port':
                            $this->smtpConfig['port'] = (int) $setting['setting_value'];
                            break;
                        case 'email_use_smtp':
                            $this->useSmtp = (bool) $setting['setting_value'];
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->log("Impossible de charger la config email: " . $e->getMessage(), 'WARNING');
        }
    }
    
    private function send($to, $subject, $message, $attachments = [], $isHtml = true) {
        $this->lastError = '';
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = "Adresse email destinataire invalide: $to";
            $this->log($this->lastError, 'ERROR');
            return false;
        }
        
        if (empty($subject) || empty($message)) {
            $this->lastError = "Sujet ou message vide";
            $this->log($this->lastError, 'ERROR');
            return false;
        }
        
        if ($this->useSmtp) {
            $this->log("Tentative d'envoi via SMTP vers: $to");
            if ($this->sendViaSMTP($to, $subject, $message, $attachments, $isHtml)) {
                $this->log("Email envoyé avec succès via SMTP vers: $to", 'SUCCESS');
                return true;
            }
            $this->log("Échec SMTP, tentative avec mail() PHP", 'WARNING');
        }
        
        if ($this->sendViaMailFunction($to, $subject, $message, $isHtml)) {
            $this->log("Email envoyé avec succès via mail() vers: $to", 'SUCCESS');
            return true;
        }
        
        $this->log("Échec total d'envoi vers: $to", 'ERROR');
        return false;
    }
    
    private function sendViaSMTP($to, $subject, $message, $attachments = [], $isHtml = true) {
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->lastError = "PHPMailer non disponible";
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpConfig['username'];
            $mail->Password = $this->smtpConfig['password'];
            $mail->SMTPSecure = $this->smtpConfig['encryption'];
            $mail->Port = $this->smtpConfig['port'];
            $mail->Timeout = $this->smtpConfig['timeout'];
            
            if ($this->smtpDebug) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    $this->log("PHPMailer [$level]: $str", 'DEBUG');
                };
            } else {
                $mail->SMTPDebug = 0;
            }
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            $mail->addReplyTo($this->fromEmail, $this->fromName);
            
            $mail->isHTML($isHtml);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if ($isHtml) {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
            }
            
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    if (is_array($attachment)) {
                        $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                    } else {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            return $mail->send();
            
        } catch (Exception $e) {
            $this->lastError = "Erreur PHPMailer: " . $e->getMessage();
            $this->log($this->lastError, 'ERROR');
            return false;
        }
    }
    
    private function sendViaMailFunction($to, $subject, $message, $isHtml = true) {
        try {
            $headers = "From: {$this->fromName} <{$this->fromEmail}>
";
            $headers .= "Reply-To: {$this->fromEmail}
";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            
            return mail($to, $subject, $message, $headers);
            
        } catch (Exception $e) {
            $this->lastError = "Erreur mail(): " . $e->getMessage();
            $this->log($this->lastError, 'ERROR');
            return false;
        }
    }
    
    public function sendWelcomeEmail($email, $firstName, $languageCode) {
        try {
            $subject = $this->lang->get('email_welcome_subject', [], $languageCode);
            
            $message = $this->buildWelcomeTemplate($firstName, $languageCode);
            
            $result = $this->send($email, $subject, $message);
            
            if ($result) {
                $this->log("Email de bienvenue ($languageCode) envoyé vers: $email", 'SUCCESS');
            } else {
                $this->log("Échec envoi email de bienvenue ($languageCode) vers: $email", 'ERROR');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erreur envoi email de bienvenue: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function buildWelcomeTemplate($firstName, $languageCode) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #F5F7FA; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .welcome-box { background: #e8f4fd; border: 1px solid #00B8D9; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; text-align: center; }
            .button { display: inline-block; background: #00B8D9; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 1rem 0; font-weight: bold; }
            .footer { background: #1F3B73; color: white; padding: 1.5rem; text-align: center; font-size: 0.9rem; }
            .features { display: flex; flex-wrap: wrap; gap: 1rem; margin: 2rem 0; }
            .feature { flex: 1; min-width: 200px; text-align: center; padding: 1rem; background: #F5F7FA; border-radius: 8px; }
        </style>
        ";

        $header = $this->lang->get('email_welcome_header', [], $languageCode);
        $subheader = $this->lang->get('email_welcome_subheader', [], $languageCode);
        $greeting = $this->lang->get('email_welcome_greeting', ['name' => htmlspecialchars($firstName)], $languageCode);
        $boxTitle = $this->lang->get('email_welcome_box_title', [], $languageCode);
        $boxBody = $this->lang->get('email_welcome_box_body', [], $languageCode);
        $ctaButton = $this->lang->get('email_welcome_cta_button', [], $languageCode);
        $question = $this->lang->get('email_welcome_question', [], $languageCode);
        $supportText = $this->lang->get('email_welcome_support_text', [], $languageCode);
        $footerBrand = $this->lang->get('email_footer_brand', [], $languageCode);
        $footerNotice = $this->lang->get('email_footer_notice', [], $languageCode);
        
        return " 
        <!DOCTYPE html>
        <html lang='{$languageCode}'>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->lang->get('email_welcome_subject', [], $languageCode)}</title>
            {$styles}
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$header}</h1>
                    <p>{$subheader}</p>
                </div>
                
                <div class='content'>
                    <h2>{$greeting}</h2>
                    
                    <div class='welcome-box'>
                        <h3>{$boxTitle}</h3>
                        <p>{$boxBody}</p>
                    </div>
                    
                    <div style='text-align: center; margin: 2rem 0;'>
                        <a href='https://prestacapi.com/{$languageCode}/dashboard' class='button'>
                            {$ctaButton}
                        </a>
                    </div>
                    
                    <h3>{$question}</h3>
                    <p>
                        {$supportText}<br>
                        <strong>📞 Téléphone :</strong> +33 7 45 50 52 07<br>
                        <strong>📧 Email :</strong> support@prestacapi.com<br>
                        <strong>💬 WhatsApp :</strong> +33 7 45 50 52 07
                    </p>
                </div>
                
                <div class='footer'>
                    <p><strong>{$footerBrand}</strong></p>
                    <p><small>{$footerNotice}</small></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    public function sendLoanRequestConfirmation($userData, $loanData, $languageCode) {
        try {
            $subject = $this->lang->get('email_loan_request_subject', ['id' => $loanData['id']], $languageCode);
            $message = $this->buildLoanRequestTemplate($userData, $loanData, $languageCode);
            
            $result = $this->send($userData['email'], $subject, $message);
            
            if ($result) {
                $this->log("Email confirmation demande prêt envoyé vers: " . $userData['email'], 'SUCCESS');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erreur envoi confirmation demande prêt: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function buildLoanRequestTemplate($userData, $loanData, $languageCode) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #F5F7FA; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #1F3B73 0%, #00B8D9 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .info-box { background: #e8f4fd; border: 1px solid #00B8D9; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .amount-highlight { background: #4CAF50; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; display: inline-block; }
            .timeline { background: #F5F7FA; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .footer { background: #1F3B73; color: white; padding: 1.5rem; text-align: center; font-size: 0.9rem; }
        </style>
        ";
        
        $amountFormatted = number_format($loanData['amount'], 0, ',', ' ') . " €";
        $durationFormatted = $loanData['duration'] . " " . $this->lang->get('loan_duration_months', [], $languageCode);
        $dateFormatted = date('d/m/Y H:i', strtotime($loanData['created_at']));

        return " 
        <!DOCTYPE html>
        <html lang='{$languageCode}'>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->lang->get('email_loan_request_subject', ['id' => $loanData['id']], $languageCode)}</title>
            {$styles}
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->lang->get('email_loan_request_header', [], $languageCode)}</h1>
                    <p>{$this->lang->get('email_loan_request_ref', [], $languageCode)} : #" . htmlspecialchars($loanData['id']) . "</p>
                </div>
                
                <div class='content'>
                    <h2>{$this->lang->get('email_loan_request_greeting', ['name' => htmlspecialchars($userData['first_name'])], $languageCode)}</h2>
                    
                    <p>{$this->lang->get('email_loan_request_intro', [], $languageCode)}</p>
                    
                    <div class='info-box'>
                        <h3>{$this->lang->get('email_loan_request_summary_title', [], $languageCode)}</h3>
                        <p><strong>{$this->lang->get('email_loan_request_amount', [], $languageCode)} :</strong> <span class='amount-highlight'>{$amountFormatted}</span></p>
                        <p><strong>{$this->lang->get('email_loan_request_duration', [], $languageCode)} :</strong> {$durationFormatted}</p>
                        <p><strong>{$this->lang->get('email_loan_request_purpose', [], $languageCode)} :</strong> " . htmlspecialchars($loanData['purpose']) . "</p>
                        <p><strong>{$this->lang->get('email_loan_request_date', [], $languageCode)} :</strong> {$dateFormatted}</p>
                    </div>
                    
                    <div class='timeline'>
                        <h3>{$this->lang->get('email_loan_request_process_title', [], $languageCode)}</h3>
                        <ul>
                            <li>{$this->lang->get('email_loan_request_step1', [], $languageCode)} - " . date('d/m/Y') . "</li>
                            <li>{$this->lang->get('email_loan_request_step2', [], $languageCode)}</li>
                            <li>{$this->lang->get('email_loan_request_step3', [], $languageCode)}</li>
                            <li>{$this->lang->get('email_loan_request_step4', [], $languageCode)}</li>
                        </ul>
                    </div>
                    
                    <h3>{$this->lang->get('email_loan_request_docs_title', [], $languageCode)}</h3>
                    <p>{$this->lang->get('email_loan_request_docs_intro', [], $languageCode)}</p>
                    <ul>
                        <li>{$this->lang->get('email_loan_request_doc1', [], $languageCode)}</li>
                        <li>{$this->lang->get('email_loan_request_doc2', [], $languageCode)}</li>
                        <li>{$this->lang->get('email_loan_request_doc3', [], $languageCode)}</li>
                        <li>{$this->lang->get('email_loan_request_doc4', [], $languageCode)}</li>
                    </ul>
                    
                    <h3>{$this->lang->get('email_contact_title', [], $languageCode)}</h3>
                    <p>
                        <strong>Téléphone :</strong> +33 7 45 50 52 07<br>
                        <strong>Email :</strong> support@prestacapi.com<br>
                        <strong>WhatsApp :</strong> +33 7 45 50 52 07
                    </p>
                </div>
                
                <div class='footer'>
                    <p><strong>{$this->lang->get('email_footer_brand', [], $languageCode)}</strong></p>
                    <p><small>{$this->lang->get('email_loan_request_ref', [], $languageCode)} : #" . htmlspecialchars($loanData['id']) . "</small></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    public function sendLoanApprovalEmail($userData, $loanData, $languageCode) {
        try {
            // Le sujet est maintenant multilingue
            $subject = $this->lang->get('email_approval_subject', ['id' => $loanData['id']], $languageCode);
            
            // On passe la langue à la méthode de construction
            $message = $this->buildLoanApprovalTemplate($userData, $loanData, $languageCode);
            
            $result = $this->send($userData['email'], $subject, $message);
            
            if ($result) {
                $this->log("Email approbation prêt envoyé vers: " . $userData['email'], 'SUCCESS');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erreur envoi approbation prêt: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function buildLoanApprovalTemplate($userData, $loanData, $languageCode) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #F5F7FA; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #4CAF50 0%, #00B8D9 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .success-box { background: #d4edda; border: 1px solid #4CAF50; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; text-align: center; }
            .amount-big { font-size: 2rem; font-weight: bold; color: #4CAF50; margin: 1rem 0; }
            .bank-info { background: #e8f4fd; border: 1px solid #00B8D9; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .button { display: inline-block; background: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 1rem 0; font-weight: bold; }
            .footer { background: #1F3B73; color: white; padding: 1.5rem; text-align: center; font-size: 0.9rem; }
        </style>
        ";
        
        return " 
        <!DOCTYPE html>
        <html lang='{$languageCode}'>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->lang->get('email_approval_subject', ['id' => $loanData['id']], $languageCode)}</title>
            {$styles}
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->lang->get('email_approval_header', [], $languageCode)}</h1>
                    <h2>{$this->lang->get('email_approval_subheader', [], $languageCode)}</h2>
                </div>
                
                <div class='content'>
                    <h2>{$this->lang->get('email_approval_greeting', ['name' => htmlspecialchars($userData['first_name'])], $languageCode)}</h2>
                    
                    <div class='success-box'>
                        <h3>{$this->lang->get('email_approval_box_title', [], $languageCode)}</h3>
                        <div class='amount-big'>" . number_format($loanData['approved_amount'], 0, ',', ' ') . " €</div>
                        <p>{$this->lang->get('email_approval_box_body', [], $languageCode)}</p>
                    </div>
                    
                    <div class='bank-info'>
                        <h3>{$this->lang->get('email_approval_details_title', [], $languageCode)}</h3>
                        <p><strong>{$this->lang->get('email_ref', [], $languageCode)}:</strong> #" . htmlspecialchars($loanData['id']) . "</p>
                        <p><strong>{$this->lang->get('email_approval_amount', [], $languageCode)}:</strong> " . number_format($loanData['approved_amount'], 0, ',', ' ') . " €</p>
                        <p><strong>{$this->lang->get('email_approval_partner', [], $languageCode)}:</strong> " . htmlspecialchars($loanData['partner_bank']) . "</p>
                        <p><strong>{$this->lang->get('email_approval_date', [], $languageCode)}:</strong> " . date('d/m/Y H:i') . "</p>
                        <p><strong>{$this->lang->get('email_approval_new_balance', [], $languageCode)}:</strong> " . number_format($userData['balance'], 2, ',', ' ') . " €</p>
                    </div>
                    
                    <h3>{$this->lang->get('email_approval_next_steps_title', [], $languageCode)}</h3>
                    <ol>
                        <li>{$this->lang->get('email_approval_step1', [], $languageCode)}</li>
                        <li>{$this->lang->get('email_approval_step2', [], $languageCode)}</li>
                        <li>{$this->lang->get('email_approval_step3', [], $languageCode)}</li>
                    </ol>
                    
                    <div style='text-align: center; margin: 2rem 0;'>
                        <a href='https://prestacapi.com/{$languageCode}/dashboard' class='button'>
                            {$this->lang->get('email_approval_cta_button', [], $languageCode)}
                        </a>
                    </div>
                    
                    <h3>{$this->lang->get('email_support_title', [], $languageCode)}</h3>
                    <p>{$this->lang->get('email_support_body', [], $languageCode)}</p>
                </div>
                
                <div class='footer'>
                    <p><strong>{$this->lang->get('email_approval_footer_brand', [], $languageCode)}</strong></p>
                    <p><small>{$this->lang->get('email_ref', [], $languageCode)}: #" . htmlspecialchars($loanData['id']) . "</small></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    public function sendLoanRejectionEmail($userData, $loanData, $languageCode) {
        try {
            $subject = $this->lang->get('email_rejection_subject', ['id' => $loanData['id']], $languageCode);
            
            $message = $this->buildLoanRejectionTemplate($userData, $loanData, $languageCode);
            
            $result = $this->send($userData['email'], $subject, $message);
            
            if ($result) {
                $this->log("Email refus prêt envoyé vers: " . $userData['email'], 'SUCCESS');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erreur envoi refus prêt: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function buildLoanRejectionTemplate($userData, $loanData, $languageCode) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #F5F7FA; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #E53935 0%, #1F3B73 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .info-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .alternatives { background: #e8f4fd; border: 1px solid #00B8D9; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .button { display: inline-block; background: #00B8D9; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; margin: 1rem 0; font-weight: bold; }
            .footer { background: #1F3B73; color: white; padding: 1.5rem; text-align: center; font-size: 0.9rem; }
        </style>
        ";
        
        return " 
        <!DOCTYPE html>
        <html lang='{$languageCode}'>
        <head>
            <meta charset='UTF-8'>
            <title>{$this->lang->get('email_rejection_subject', ['id' => $loanData['id']], $languageCode)}</title>
            {$styles}
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->lang->get('email_rejection_header', [], $languageCode)}</h1>
                    <p>{$this->lang->get('email_ref', [], $languageCode)}: #" . htmlspecialchars($loanData['id']) . "</p>
                </div>
                
                <div class='content'>
                    <h2>{$this->lang->get('email_rejection_greeting', ['name' => htmlspecialchars($userData['first_name'])], $languageCode)}</h2>
                    
                    <p>{$this->lang->get('email_rejection_intro', [], $languageCode)}</p>
                    
                    <div class='info-box'>
                        <h3>{$this->lang->get('email_rejection_details_title', [], $languageCode)}</h3>
                        <p><strong>{$this->lang->get('email_ref', [], $languageCode)}:</strong> #" . htmlspecialchars($loanData['id']) . "</p>
                        <p><strong>{$this->lang->get('loan_amount', [], $languageCode)}:</strong> " . number_format($loanData['amount'], 0, ',', ' ') . " €</p>
                        <p><strong>{$this->lang->get('email_rejection_reason', [], $languageCode)}:</strong> " . htmlspecialchars($loanData['rejection_reason']) . "</p>
                    </div>
                    
                    <div class='alternatives'>
                        <h3>{$this->lang->get('email_rejection_reco_title', [], $languageCode)}</h3>
                        <ul>
                            <li>{$this->lang->get('email_rejection_reco1', [], $languageCode)}</li>
                            <li>{$this->lang->get('email_rejection_reco2', [], $languageCode)}</li>
                            <li>{$this->lang->get('email_rejection_reco3', [], $languageCode)}</li>
                            <li>{$this->lang->get('email_rejection_reco4', [], $languageCode)}</li>
                        </ul>
                    </div>
                    
                    <h3>{$this->lang->get('email_rejection_help_title', [], $languageCode)}</h3>
                    <p>{$this->lang->get('email_rejection_help_body', [], $languageCode)}</p>
                    
                    <div style='text-align: center; margin: 2rem 0;'>
                        <a href='https://prestacapi.com/{$languageCode}/contact' class='button'>
                            {$this->lang->get('email_rejection_cta_button', [], $languageCode)}
                        </a>
                    </div>
                </div>
                
                <div class='footer'>
                    <p><strong>{$this->lang->get('email_rejection_footer_brand', [], $languageCode)}</strong></p>
                    <p><small>{$this->lang->get('email_ref', [], $languageCode)}: #" . htmlspecialchars($loanData['id']) . "</small></p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    public function sendWithdrawalConfirmation($userData, $withdrawalData) {
        try {
            $subject = "Demande de retrait confirmée - " . number_format($withdrawalData['amount'], 2, ',', ' ') . " €";
            $message = $this->buildWithdrawalTemplate($userData, $withdrawalData);
            
            return $this->send($userData['email'], $subject, $message);
            
        } catch (Exception $e) {
            $this->log("Erreur envoi confirmation retrait: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    private function buildWithdrawalTemplate($userData, $withdrawalData) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #F5F7FA; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #4CAF50 0%, #00B8D9 100%); color: white; padding: 2rem; text-align: center; }
            .content { padding: 2rem; }
            .amount-box { background: #d4edda; border: 1px solid #4CAF50; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; text-align: center; }
            .bank-details { background: #F5F7FA; border-radius: 8px; padding: 1.5rem; margin: 1.5rem 0; }
            .footer { background: #1F3B73; color: white; padding: 1.5rem; text-align: center; font-size: 0.9rem; }
        </style>
        ";
        
        return " 
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Demande de retrait confirmée</title>
            {$styles}
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💸 Demande de retrait reçue</h1>
                </div>
                
                <div class='content'>
                    <h2>Bonjour " . htmlspecialchars($userData['first_name']) . " !</h2>
                    
                    <p>Nous avons bien reçu votre demande de retrait. Votre virement sera traité dans les plus brefs délais.</p>
                    
                    <div class='amount-box'>
                        <h3>💰 Montant à virer :</h3>
                        <h2 style='color: #4CAF50; margin: 0;'>" . number_format($withdrawalData['amount'], 2, ',', ' ') . " €</h2>
                    </div>
                    
                    <div class='bank-details'>
                        <h3>🏦 Coordonnées bancaires :</h3>
                        <p><strong>Bénéficiaire :</strong> " . htmlspecialchars($withdrawalData['account_holder_name']) . "</p>
                        <p><strong>Banque :</strong> " . htmlspecialchars($withdrawalData['bank_name']) . "</p>
                        <p><strong>Numéro de compte :</strong> " . htmlspecialchars($withdrawalData['account_number']) . "</p>
                        " . (!empty($withdrawalData['iban']) ? "<p><strong>IBAN :</strong> " . htmlspecialchars($withdrawalData['iban']) . "</p>" : "") . "
                        " . (!empty($withdrawalData['swift_code']) ? "<p><strong>Code SWIFT :</strong> " . htmlspecialchars($withdrawalData['swift_code']) . "</p>" : "") . "
                    </div>
                    
                    <h3>⏱️ Délais de traitement :</h3>
                    <ul>
                        <li>Vérification et validation : 6-24h</li>
                        <li>Traitement du virement : 24-48h</li>
                        <li>Réception sur votre compte : 48-72h</li>
                    </ul>
                    
                    <p>Vous recevrez une notification dès que votre virement aura été traité.</p>
                </div>
                
                <div class='footer'>
                    <p><strong>PrestaCapi</strong> - Service de retrait</p>
                </div>
            </div>
        </body>
        </html>";
    }


    public function sendDocumentReceivedEmail($userData, $documentType, $fileName, $languageCode) {
        try {
            $subject = $this->lang->get('email_doc_received_subject', [], $languageCode);
            $message = $this->buildDocumentReceivedTemplate($userData, $documentType, $fileName, $languageCode);
            
            $result = $this->send($userData['email'], $subject, $message);
            
            if ($result) {
                $this->log("Email de confirmation d'upload ($languageCode) envoyé à : " . $userData['email'], 'SUCCESS');
            } else {
                $this->log("Échec de l'envoi de l'email de confirmation d'upload ($languageCode) à : " . $userData['email'], 'ERROR');
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->log("Erreur lors de l'envoi de l'email de confirmation de document : " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function buildDocumentReceivedTemplate($userData, $documentType, $fileName, $languageCode) {
        $styles = "
        <style>
            body { font-family: 'Arial', sans-serif; } .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
            .header { background: #1F3B73; color: white; padding: 1rem; text-align: center; }
            .content { padding: 1rem; } .footer { text-align: center; font-size: 0.8rem; color: #888; }
        </style>";

        return "
        <!DOCTYPE html><html lang='{$languageCode}'><head><meta charset='UTF-8'><title>{$this->lang->get('email_doc_received_subject', [], $languageCode)}</title>{$styles}</head>
        <body>
            <div class='container'>
                <div class='header'><h1>{$this->lang->get('email_doc_received_header', [], $languageCode)}</h1></div>
                <div class='content'>
                    <h2>{$this->lang->get('email_doc_received_greeting', ['name' => htmlspecialchars($userData['first_name'])], $languageCode)}</h2>
                    <p>{$this->lang->get('email_doc_received_intro', [], $languageCode)}</p>
                    <ul>
                        <li><strong>{$this->lang->get('document_type', [], $languageCode)}:</strong> {$documentType}</li>
                        <li><strong>{$this->lang->get('file_name', [], $languageCode)}:</strong> " . htmlspecialchars($fileName) . "</li>
                    </ul>
                    <p>{$this->lang->get('email_doc_received_next_steps', [], $languageCode)}</p>
                </div>
                <div class='footer'><p>PrestaCapi</p></div>
            </div>
        </body></html>";
    }
    
    public function sendPasswordResetEmail($email, $token) {
        try {
            $subject = "Réinitialisation de votre mot de passe PrestaCapi";
            $resetUrl = "https://prestacapi.com/reset-password?token=" . $token;
            
            $message = "
            <h2>Réinitialisation de mot de passe</h2>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le lien suivant pour créer un nouveau mot de passe :</p>
            <p><a href='{$resetUrl}' style='display: inline-block; background: #00B8D9; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px;'>Réinitialiser mon mot de passe</a></p>
            <p>Ce lien expire dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            ";
            
            return $this->send($email, $subject, $message);
            
        } catch (Exception $e) {
            $this->log("Erreur envoi reset password: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function sendAdminNotification($type, $data) {
        try {
            $subjects = [
                'new_user' => 'Nouvel utilisateur inscrit',
                'new_loan_request' => 'Nouvelle demande de prêt',
                'new_withdrawal' => 'Nouvelle demande de retrait',
                'document_uploaded' => 'Nouveau document uploadé'
            ];
            
            $subject = $subjects[$type] ?? 'Notification PrestaCapi';
            $message = $this->buildAdminNotificationTemplate($type, $data);

            // AJOUT : Gestion de la pièce jointe
            $attachments = [];
            if ($type === 'document_uploaded' && isset($data['file_path'])) {
                $attachments[] = [
                    'path' => $data['file_path'],
                    'name' => $data['original_name'] ?? 'document.dat'
                ];
            }
            
            // On passe les pièces jointes à la méthode send
            return $this->send($this->adminEmail, $subject, $message, $attachments);
            
        } catch (Exception $e) {
            $this->log("Erreur notification admin: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }


    public function sendDocumentVerifiedEmail($userData, $documentTypeName, $languageCode) {
        try {
            $subject = $this->lang->get('email_doc_verified_subject', ['type' => $documentTypeName], $languageCode);
            $message = $this->buildSimpleDocumentStatusTemplate(
                $languageCode,
                $subject,
                $this->lang->get('email_doc_verified_header', [], $languageCode),
                $this->lang->get('email_doc_verified_greeting', ['name' => $userData['first_name']], $languageCode),
                $this->lang->get('email_doc_verified_body', ['type' => $documentTypeName], $languageCode)
            );
            return $this->send($userData['email'], $subject, $message);
        } catch (Exception $e) {
            $this->log("Erreur envoi email document vérifié: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    public function sendDocumentRejectedEmail($userData, $documentTypeName, $notes, $languageCode) {
        try {
            $subject = $this->lang->get('email_doc_rejected_subject', ['type' => $documentTypeName], $languageCode);
            $rejectionReasonText = $this->lang->get('email_doc_rejected_reason', [], $languageCode);
            $body = $this->lang->get('email_doc_rejected_body', ['type' => $documentTypeName], $languageCode);
            if (!empty($notes)) {
                $body .= "<p><strong>{$rejectionReasonText}:</strong> " . htmlspecialchars($notes) . "</p>";
            }
            
            $message = $this->buildSimpleDocumentStatusTemplate(
                $languageCode,
                $subject,
                $this->lang->get('email_doc_rejected_header', [], $languageCode),
                $this->lang->get('email_doc_rejected_greeting', ['name' => $userData['first_name']], $languageCode),
                $body
            );
            return $this->send($userData['email'], $subject, $message);
        } catch (Exception $e) {
            $this->log("Erreur envoi email document rejeté: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    private function buildSimpleDocumentStatusTemplate($langCode, $title, $header, $greeting, $body) {
        return "<!DOCTYPE html>
        <html lang='{$langCode}'>
        <head><title>{$title}</title></head>
        <body style='font-family: Arial, sans-serif; padding: 20px;'>
            <div style='max-width: 600px; margin: auto; border: 1px solid #ddd;'>
                <div style='background: #007bff; color: white; padding: 10px; text-align: center;'><h1>{$header}</h1></div>
                <div style='padding: 20px;'>
                    <h2>{$greeting}</h2>
                    {$body}
                    <p>{$this->lang->get('email_sincerely', [], $langCode)}<br>PrestaCapi</p>
                </div>
            </div>
        </body></html>";
    }
    
    private function buildAdminNotificationTemplate($type, $data) {
        $content = "";
        
        switch ($type) {
            case 'new_user':
                $content = "
                <h3>Nouvel utilisateur inscrit</h3>
                <p><strong>Nom :</strong> " . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . "</p>
                <p><strong>Email :</strong> " . htmlspecialchars($data['email']) . "</p>
                <p><strong>Date d'inscription :</strong> " . date('d/m/Y H:i') . "</p>
                ";
                break;
                
            case 'new_loan_request':
                $content = "
                <h3>Nouvelle demande de prêt</h3>
                <p><strong>Utilisateur :</strong> " . htmlspecialchars($data['user_name']) . "</p>
                <p><strong>Montant :</strong> " . number_format($data['amount'], 0, ',', ' ') . " €</p>
                <p><strong>Durée :</strong> " . $data['duration'] . " mois</p>
                <p><strong>Objectif :</strong> " . htmlspecialchars($data['purpose']) . "</p>
                ";
                break;
                
            case 'new_withdrawal':
                $content = "
                <h3>Nouvelle demande de retrait</h3>
                <p><strong>Utilisateur :</strong> " . htmlspecialchars($data['user_name']) . "</p>
                <p><strong>Montant :</strong> " . number_format($data['amount'], 2, ',', ' ') . " €</p>
                <p><strong>Banque :</strong> " . htmlspecialchars($data['bank_name']) . "</p>
                ";
                break;
        }
        
        return " 
        <h2>PrestaCapi - Notification Admin</h2>
        {$content}
        <p><a href='https://prestacapi.com/admin'>Accéder à l'interface admin</a></p>
        ";
    }
    
    public function sendTestEmail($to = null) {
        $to = $to ?: $this->adminEmail;
        
        $subject = "✅ Test Email - PrestaCapi";
        $message = "
        <h2>🧪 Test Email PrestaCapi</h2>
        <p>Ceci est un email de test pour vérifier la configuration.</p>
        <p><strong>Heure d'envoi:</strong> " . date('d/m/Y H:i:s') . "</p>
        <p>Si vous recevez cet email, la configuration fonctionne ! ✅</p>
        ";
        
        return $this->send($to, $subject, $message);
    }
    
    public function setDebugMode($enabled = true) {
        $this->smtpDebug = $enabled;
        $this->log("Mode debug " . ($enabled ? "activé" : "désactivé"));
    }
    
    public function getLastError() {
        return $this->lastError;
    }
}
