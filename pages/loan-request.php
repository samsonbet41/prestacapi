<?php
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Language.php';
require_once 'classes/LoanRequest.php';
require_once 'classes/Document.php';
require_once 'classes/SEO.php';

$lang = Language::getInstance();
$user = new User();
$loanRequest = new LoanRequest();
$document = new Document();
$seo = new SEO();

$user->requireAuth();

$currentUser = $user->getCurrentUser();
$userId = $currentUser['id'];

$existingLoanRequest = $loanRequest->getUserLoanRequests($userId, 1);
$hasActiveLoan = !empty($existingLoanRequest) && in_array($existingLoanRequest[0]['status'], ['pending', 'under_review']);

$documentStatus = $document->getUserDocumentStatus($userId);
$requiredDocs = $document->getMissingDocuments($userId);

$pageTitle = $lang->get('loan_request_title');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/images/favicon/favicon.ico">
</head>
<body class="loan-request-page">
    <?php include 'includes/header.php'; ?>
    
    <main class="loan-request-main">
        <div class="container">
            <?php if ($hasActiveLoan): ?>
                <div class="alert alert-info">
                    <div class="alert-icon">ℹ️</div>
                    <div class="alert-content">
                        <h3>Demande de prêt en cours</h3>
                        <p>Vous avez déjà une demande de prêt en cours de traitement. Vous ne pouvez pas soumettre une nouvelle demande tant que celle-ci n'est pas finalisée.</p>
                        <div class="alert-actions">
                            <a href="<?php echo $lang->pageUrl('dashboard'); ?>" class="btn btn-outline">
                                Voir le statut de ma demande
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                
                <div class="loan-request-header">
                    <div class="page-breadcrumb">
                        <a href="<?php echo $lang->pageUrl('dashboard'); ?>">Tableau de bord</a>
                        <span class="breadcrumb-separator">→</span>
                        <span class="breadcrumb-current">Demande de prêt</span>
                    </div>
                    
                    <h1 class="page-title"><?php echo $lang->get('loan_request_title'); ?></h1>
                    <p class="page-subtitle">Remplissez ce formulaire pour soumettre votre demande de financement</p>
                    
                    <div class="loan-progress">
                        <div class="progress-step active" data-step="1">
                            <div class="step-number">1</div>
                            <div class="step-label">Projet</div>
                        </div>
                        <div class="progress-step" data-step="2">
                            <div class="step-number">2</div>
                            <div class="step-label">Finances</div>
                        </div>
                        <div class="progress-step" data-step="3">
                            <div class="step-number">3</div>
                            <div class="step-label">Professionnel</div>
                        </div>
                        <div class="progress-step" data-step="4">
                            <div class="step-number">4</div>
                            <div class="step-label">Validation</div>
                        </div>
                    </div>
                </div>
                
                <div class="loan-request-content">
                    <div class="loan-form-container">
                        <form id="loanRequestForm" class="loan-form" onsubmit="handleLoanSubmit(event)">
                            
                            <div class="form-step active" data-step="1">
                                <div class="step-header">
                                    <h2 class="step-title">Votre projet de financement</h2>
                                    <p class="step-description">Décrivez-nous votre projet et le montant souhaité</p>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="loanAmount" class="form-label">
                                            <?php echo $lang->get('loan_amount'); ?> *
                                        </label>
                                        <div class="amount-input-wrapper">
                                            <input type="number" 
                                                   id="loanAmount" 
                                                   name="amount" 
                                                   class="form-input amount-input" 
                                                   min="500" 
                                                   max="50000" 
                                                   step="100"
                                                   placeholder="10000"
                                                   required>
                                            <span class="amount-currency">€</span>
                                        </div>
                                        <div class="form-help">Montant entre 500€ et 50 000€</div>
                                        <div class="form-error" id="amountError"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="loanDuration" class="form-label">
                                            <?php echo $lang->get('loan_duration'); ?> *
                                        </label>
                                        <select id="loanDuration" name="duration" class="form-select" required>
                                            <option value="">Sélectionnez une durée</option>
                                            <option value="6">6 mois</option>
                                            <option value="12">12 mois</option>
                                            <option value="18">18 mois</option>
                                            <option value="24">24 mois</option>
                                            <option value="36">36 mois</option>
                                            <option value="48">48 mois</option>
                                            <option value="60">60 mois</option>
                                        </select>
                                        <div class="form-error" id="durationError"></div>
                                    </div>
                                </div>
                                
                                <div class="loan-simulation" id="loanSimulation">
                                    <div class="simulation-header">
                                        <h3>Simulation de votre prêt</h3>
                                    </div>
                                    <div class="simulation-content">
                                        <div class="simulation-item">
                                            <span class="simulation-label">Mensualité estimée</span>
                                            <span class="simulation-value" id="monthlyPayment">-</span>
                                        </div>
                                        <div class="simulation-item">
                                            <span class="simulation-label">Coût total</span>
                                            <span class="simulation-value" id="totalCost">-</span>
                                        </div>
                                        <div class="simulation-item">
                                            <span class="simulation-label">Taux estimé</span>
                                            <span class="simulation-value">3.9% - 12.9%</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="loanPurpose" class="form-label">
                                        <?php echo $lang->get('loan_purpose'); ?> *
                                    </label>
                                    <textarea id="loanPurpose" 
                                              name="purpose" 
                                              class="form-textarea" 
                                              rows="4" 
                                              placeholder="<?php echo $lang->get('loan_purpose_placeholder'); ?>"
                                              required></textarea>
                                    <div class="form-help">Décrivez précisément l'utilisation de ce financement</div>
                                    <div class="form-error" id="purposeError"></div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="2">
                                <div class="step-header">
                                    <h2 class="step-title">Votre situation financière</h2>
                                    <p class="step-description">Ces informations nous aident à évaluer votre capacité de remboursement</p>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="monthlyIncome" class="form-label">
                                            <?php echo $lang->get('loan_monthly_income'); ?> *
                                        </label>
                                        <div class="amount-input-wrapper">
                                            <input type="number" 
                                                   id="monthlyIncome" 
                                                   name="monthly_income" 
                                                   class="form-input amount-input" 
                                                   min="0" 
                                                   step="10"
                                                   placeholder="3000"
                                                   required>
                                            <span class="amount-currency">€/mois</span>
                                        </div>
                                        <div class="form-help">Revenus nets mensuels</div>
                                        <div class="form-error" id="monthlyIncomeError"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="monthlyExpenses" class="form-label">
                                            <?php echo $lang->get('loan_monthly_expenses'); ?> *
                                        </label>
                                        <div class="amount-input-wrapper">
                                            <input type="number" 
                                                   id="monthlyExpenses" 
                                                   name="monthly_expenses" 
                                                   class="form-input amount-input" 
                                                   min="0" 
                                                   step="10"
                                                   placeholder="1500"
                                                   required>
                                            <span class="amount-currency">€/mois</span>
                                        </div>
                                        <div class="form-help">Charges fixes mensuelles (loyer, crédits...)</div>
                                        <div class="form-error" id="monthlyExpensesError"></div>
                                    </div>
                                </div>
                                
                                <div class="financial-analysis" id="financialAnalysis">
                                    <div class="analysis-header">
                                        <h3>Analyse de votre capacité financière</h3>
                                    </div>
                                    <div class="analysis-content">
                                        <div class="analysis-item">
                                            <span class="analysis-label">Reste à vivre mensuel</span>
                                            <span class="analysis-value" id="remainingIncome">-</span>
                                        </div>
                                        <div class="analysis-item">
                                            <span class="analysis-label">Taux d'endettement</span>
                                            <span class="analysis-value" id="debtRatio">-</span>
                                        </div>
                                        <div class="analysis-indicator" id="analysisIndicator">
                                            <div class="indicator-bar">
                                                <div class="indicator-fill"></div>
                                            </div>
                                            <div class="indicator-text">Capacité d'emprunt</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="otherLoans" class="form-label">
                                        Autres crédits en cours
                                    </label>
                                    <div class="amount-input-wrapper">
                                        <input type="number" 
                                               id="otherLoans" 
                                               name="other_loans" 
                                               class="form-input amount-input" 
                                               min="0" 
                                               step="10"
                                               placeholder="0">
                                        <span class="amount-currency">€/mois</span>
                                    </div>
                                    <div class="form-help">Mensualités d'autres crédits (optionnel)</div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="3">
                                <div class="step-header">
                                    <h2 class="step-title">Votre situation professionnelle</h2>
                                    <p class="step-description">Informations sur votre emploi et stabilité professionnelle</p>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="employmentStatus" class="form-label">
                                            <?php echo $lang->get('loan_employment_status'); ?> *
                                        </label>
                                        <select id="employmentStatus" name="employment_status" class="form-select" required>
                                            <option value="">Sélectionnez votre statut</option>
                                            <option value="employee"><?php echo $lang->get('employment_status_employee'); ?></option>
                                            <option value="self_employed"><?php echo $lang->get('employment_status_self_employed'); ?></option>
                                            <option value="freelance"><?php echo $lang->get('employment_status_freelance'); ?></option>
                                            <option value="retired"><?php echo $lang->get('employment_status_retired'); ?></option>
                                            <option value="other"><?php echo $lang->get('employment_status_other'); ?></option>
                                        </select>
                                        <div class="form-error" id="employmentStatusError"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="employmentDuration" class="form-label">
                                            <?php echo $lang->get('loan_employment_duration'); ?> *
                                        </label>
                                        <select id="employmentDuration" name="employment_duration" class="form-select" required>
                                            <option value="">Sélectionnez la durée</option>
                                            <option value="3">Moins de 6 mois</option>
                                            <option value="6">6 à 12 mois</option>
                                            <option value="12">1 à 2 ans</option>
                                            <option value="24">2 à 5 ans</option>
                                            <option value="60">Plus de 5 ans</option>
                                        </select>
                                        <div class="form-error" id="employmentDurationError"></div>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="employerName" class="form-label">
                                            <?php echo $lang->get('loan_employer_name'); ?>
                                        </label>
                                        <input type="text" 
                                               id="employerName" 
                                               name="employer_name" 
                                               class="form-input" 
                                               placeholder="Nom de votre employeur">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="employerPhone" class="form-label">
                                            <?php echo $lang->get('loan_employer_phone'); ?>
                                        </label>
                                        <input type="tel" 
                                               id="employerPhone" 
                                               name="employer_phone" 
                                               class="form-input" 
                                               placeholder="+33 1 23 45 67 89">
                                    </div>
                                </div>
                                
                                <div class="additional-info-section">
                                    <h3>Informations complémentaires (optionnel)</h3>
                                    
                                    <div class="form-group">
                                        <label for="collateral" class="form-label">
                                            <?php echo $lang->get('loan_collateral'); ?>
                                        </label>
                                        <textarea id="collateral" 
                                                  name="collateral" 
                                                  class="form-textarea" 
                                                  rows="3" 
                                                  placeholder="Décrivez les garanties que vous pouvez apporter (biens immobiliers, véhicules...)"></textarea>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="coSignerName" class="form-label">
                                                <?php echo $lang->get('loan_co_signer_name'); ?>
                                            </label>
                                            <input type="text" 
                                                   id="coSignerName" 
                                                   name="co_signer_name" 
                                                   class="form-input" 
                                                   placeholder="Nom du co-signataire">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="coSignerPhone" class="form-label">
                                                <?php echo $lang->get('loan_co_signer_phone'); ?>
                                            </label>
                                            <input type="tel" 
                                                   id="coSignerPhone" 
                                                   name="co_signer_phone" 
                                                   class="form-input" 
                                                   placeholder="+33 1 23 45 67 89">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notes" class="form-label">
                                            <?php echo $lang->get('loan_notes'); ?>
                                        </label>
                                        <textarea id="notes" 
                                                  name="notes" 
                                                  class="form-textarea" 
                                                  rows="4" 
                                                  placeholder="Informations complémentaires que vous souhaitez nous communiquer"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="4">
                                <div class="step-header">
                                    <h2 class="step-title">Validation et soumission</h2>
                                    <p class="step-description">Vérifiez vos informations avant de soumettre votre demande</p>
                                </div>
                                
                                <div class="validation-summary" id="validationSummary">
                                    <div class="summary-section">
                                        <h3>📋 Récapitulatif de votre demande</h3>
                                        <div class="summary-grid">
                                            <div class="summary-item">
                                                <span class="summary-label">Montant demandé</span>
                                                <span class="summary-value" id="summaryAmount">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label">Durée</span>
                                                <span class="summary-value" id="summaryDuration">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label">Mensualité estimée</span>
                                                <span class="summary-value" id="summaryMonthlyPayment">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label">Revenus mensuels</span>
                                                <span class="summary-value" id="summaryIncome">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-section">
                                        <h3>📄 État des documents</h3>
                                        <div class="documents-status">
                                            <div class="doc-progress">
                                                <div class="doc-progress-bar">
                                                    <div class="doc-progress-fill" style="width: <?php echo $documentStatus['completion_percentage']; ?>%"></div>
                                                </div>
                                                <span class="doc-progress-text"><?php echo $documentStatus['verified']; ?>/<?php echo $documentStatus['total_required']; ?> documents vérifiés</span>
                                            </div>
                                            
                                            <?php if (!empty($requiredDocs)): ?>
                                                <div class="missing-docs">
                                                    <h4>Documents manquants :</h4>
                                                    <ul>
                                                        <?php foreach ($requiredDocs as $doc): ?>
                                                            <li><?php echo $doc['name']; ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <p class="doc-note">
                                                        ⚠️ Vous pouvez soumettre votre demande maintenant et compléter vos documents après.
                                                    </p>
                                                </div>
                                            <?php else: ?>
                                                <div class="docs-complete">
                                                    ✅ Tous vos documents requis sont présents et vérifiés !
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="consent-section">
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="consent_verification" id="consentVerification" required>
                                            <span class="checkbox-custom"></span>
                                            J'autorise PrestaCapi à vérifier mes informations auprès de ses partenaires et organismes de crédit
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="consent_processing" id="consentProcessing" required>
                                            <span class="checkbox-custom"></span>
                                            J'accepte que ma demande soit transmise aux partenaires financiers de PrestaCapi
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="consent_terms" id="consentTerms" required>
                                            <span class="checkbox-custom"></span>
                                            J'ai lu et j'accepte les <a href="<?php echo $lang->pageUrl('terms'); ?>" target="_blank">conditions générales</a> 
                                            et la <a href="<?php echo $lang->pageUrl('privacy'); ?>" target="_blank">politique de confidentialité</a>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="prevStepBtn" class="btn btn-outline" onclick="previousStep()" style="display: none;">
                                    Précédent
                                </button>
                                
                                <button type="button" id="nextStepBtn" class="btn btn-primary" onclick="nextStep()">
                                    Suivant
                                </button>
                                
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-large" style="display: none;">
                                    <span class="btn-text">Soumettre ma demande</span>
                                    <span class="btn-loader"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="loan-sidebar">
                        <div class="help-card">
                            <h3>💡 Conseils pour votre demande</h3>
                            <ul>
                                <li>Soyez précis dans la description de votre projet</li>
                                <li>Indiquez vos revenus nets réels</li>
                                <li>N'oubliez pas de mentionner tous vos crédits en cours</li>
                                <li>Préparez vos documents à l'avance</li>
                            </ul>
                        </div>
                        
                        <div class="process-card">
                            <h3>⏱️ Processus de traitement</h3>
                            <div class="process-steps">
                                <div class="process-step-item">
                                    <span class="step-number">1</span>
                                    <div class="step-content">
                                        <strong>Soumission</strong>
                                        <small>Votre demande nous parvient instantanément</small>
                                    </div>
                                </div>
                                <div class="process-step-item">
                                    <span class="step-number">2</span>
                                    <div class="step-content">
                                        <strong>Analyse</strong>
                                        <small>Étude de votre dossier sous 24h</small>
                                    </div>
                                </div>
                                <div class="process-step-item">
                                    <span class="step-number">3</span>
                                    <div class="step-content">
                                        <strong>Partenaires</strong>
                                        <small>Transmission aux institutions financières</small>
                                    </div>
                                </div>
                                <div class="process-step-item">
                                    <span class="step-number">4</span>
                                    <div class="step-content">
                                        <strong>Réponse</strong>
                                        <small>Notification sous 48-72h maximum</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-card">
                            <h3>❓ Besoin d'aide ?</h3>
                            <p>Notre équipe est disponible pour vous accompagner</p>
                            <div class="contact-options">
                                <a href="tel:+33123456789" class="contact-option">
                                    <span class="contact-icon">📞</span>
                                    <span>+33 1 23 45 67 89</span>
                                </a>
                                <a href="mailto:support@prestacapi.com" class="contact-option">
                                    <span class="contact-icon">📧</span>
                                    <span>support@prestacapi.com</span>
                                </a>
                                <a href="https://wa.me/33123456789" class="contact-option">
                                    <span class="contact-icon">💬</span>
                                    <span>WhatsApp</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="/js/main.js"></script>
    <script src="/js/modules/forms.js"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 4;
        
        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepDisplay();
                }
            }
        }
        
        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
            }
        }
        
        function updateStepDisplay() {
            document.querySelectorAll('.form-step').forEach((step, index) => {
                step.classList.toggle('active', index + 1 === currentStep);
            });
            
            document.querySelectorAll('.progress-step').forEach((step, index) => {
                step.classList.toggle('active', index + 1 <= currentStep);
                step.classList.toggle('completed', index + 1 < currentStep);
            });
            
            document.getElementById('prevStepBtn').style.display = currentStep > 1 ? 'inline-block' : 'none';
            document.getElementById('nextStepBtn').style.display = currentStep < totalSteps ? 'inline-block' : 'none';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'inline-block' : 'none';
            
            if (currentStep === 4) {
                updateValidationSummary();
            }
        }
        
        function validateCurrentStep() {
            let isValid = true;
            const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const requiredInputs = currentStepElement.querySelectorAll('input[required], select[required], textarea[required]');
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    showFieldError(input, 'Ce champ est obligatoire');
                    isValid = false;
                } else {
                    clearFieldError(input);
                }
            });
            
            if (currentStep === 1) {
                const amount = parseFloat(document.getElementById('loanAmount').value);
                if (amount < 500 || amount > 50000) {
                    showFieldError(document.getElementById('loanAmount'), 'Le montant doit être entre 500€ et 50 000€');
                    isValid = false;
                }
            }
            
            if (currentStep === 2) {
                const income = parseFloat(document.getElementById('monthlyIncome').value);
                const expenses = parseFloat(document.getElementById('monthlyExpenses').value);
                
                if (expenses >= income) {
                    showFieldError(document.getElementById('monthlyExpenses'), 'Vos charges ne peuvent pas être supérieures ou égales à vos revenus');
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function showFieldError(field, message) {
            const errorElement = document.getElementById(field.id + 'Error') || field.parentNode.querySelector('.form-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
            field.classList.add('error');
        }
        
        function clearFieldError(field) {
            const errorElement = document.getElementById(field.id + 'Error') || field.parentNode.querySelector('.form-error');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
            field.classList.remove('error');
        }
        
        function updateLoanSimulation() {
            const amount = parseFloat(document.getElementById('loanAmount').value) || 0;
            const duration = parseInt(document.getElementById('loanDuration').value) || 24;
            
            if (amount > 0 && duration > 0) {
                const monthlyRate = 0.06 / 12;
                const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, duration)) / 
                                    (Math.pow(1 + monthlyRate, duration) - 1);
                const totalCost = monthlyPayment * duration;
                
                document.getElementById('monthlyPayment').textContent = Math.round(monthlyPayment) + '€';
                document.getElementById('totalCost').textContent = Math.round(totalCost) + '€';
            }
        }
        
        function updateFinancialAnalysis() {
            const income = parseFloat(document.getElementById('monthlyIncome').value) || 0;
            const expenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
            const otherLoans = parseFloat(document.getElementById('otherLoans').value) || 0;
            
            if (income > 0) {
                const remaining = income - expenses - otherLoans;
                const debtRatio = ((expenses + otherLoans) / income * 100);
                
                document.getElementById('remainingIncome').textContent = remaining + '€';
                document.getElementById('debtRatio').textContent = debtRatio.toFixed(1) + '%';
                
                const indicator = document.querySelector('.indicator-fill');
                const indicatorText = document.querySelector('.indicator-text');
                
                if (debtRatio < 33) {
                    indicator.style.width = '80%';
                    indicator.style.background = '#4CAF50';
                    indicatorText.textContent = 'Excellente capacité';
                } else if (debtRatio < 50) {
                    indicator.style.width = '60%';
                    indicator.style.background = '#FF9800';
                    indicatorText.textContent = 'Capacité correcte';
                } else {
                    indicator.style.width = '30%';
                    indicator.style.background = '#E53935';
                    indicatorText.textContent = 'Capacité limitée';
                }
            }
        }
        
        function updateValidationSummary() {
            const amount = document.getElementById('loanAmount').value;
            const duration = document.getElementById('loanDuration').value;
            const income = document.getElementById('monthlyIncome').value;
            const monthlyPayment = document.getElementById('monthlyPayment').textContent;
            
            document.getElementById('summaryAmount').textContent = amount ? amount + '€' : '-';
            document.getElementById('summaryDuration').textContent = duration ? duration + ' mois' : '-';
            document.getElementById('summaryMonthlyPayment').textContent = monthlyPayment || '-';
            document.getElementById('summaryIncome').textContent = income ? income + '€' : '-';
        }
        
        function handleLoanSubmit(event) {
            event.preventDefault();
            
            if (!validateCurrentStep()) {
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            
            const formData = new FormData(event.target);
            
            fetch('/ajax/loan-request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.classList.remove('loading');
                
                if (data.success) {
                    showToast('Demande soumise avec succès !', 'success');
                    setTimeout(() => {
                        window.location.href = '<?php echo $lang->pageUrl('dashboard'); ?>';
                    }, 2000);
                } else {
                    showToast(data.message || 'Erreur lors de la soumission', 'error');
                }
            })
            .catch(error => {
                submitBtn.classList.remove('loading');
                showToast('Une erreur est survenue', 'error');
            });
        }
        
        function showToast(message, type) {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('loanAmount');
            const durationInput = document.getElementById('loanDuration');
            const incomeInput = document.getElementById('monthlyIncome');
            const expensesInput = document.getElementById('monthlyExpenses');
            const otherLoansInput = document.getElementById('otherLoans');
            
            [amountInput, durationInput].forEach(input => {
                input?.addEventListener('input', updateLoanSimulation);
            });
            
            [incomeInput, expensesInput, otherLoansInput].forEach(input => {
                input?.addEventListener('input', updateFinancialAnalysis);
            });
            
            updateStepDisplay();
        });
    </script>
    
    <style>
        .loan-request-main {
            padding: 2rem 0;
            background: var(--secondary-color);
            min-height: calc(100vh - 70px);
        }
        
        .loan-request-header {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .page-breadcrumb {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-separator {
            margin: 0 0.5rem;
        }
        
        .breadcrumb-current {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #666;
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }
        
        .loan-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }
        
        .loan-progress::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .step-number {
            background: var(--accent-1);
            color: white;
        }
        
        .progress-step.completed .step-number {
            background: var(--success-color);
            color: white;
        }
        
        .step-label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
        }
        
        .progress-step.active .step-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .loan-request-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .loan-form-container {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .form-step {
            display: none;
        }
        
        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-header {
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .step-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .step-description {
            color: #666;
            font-size: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .amount-input-wrapper {
            position: relative;
        }
        
        .amount-currency {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-weight: 500;
        }
        
        .loan-simulation {
            background: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .simulation-header h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .simulation-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .simulation-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
        }
        
        .simulation-label {
            display: block;
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .simulation-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .financial-analysis {
            background: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .analysis-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .analysis-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
        }
        
        .analysis-label {
            display: block;
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .analysis-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .analysis-indicator {
            text-align: center;
        }
        
        .indicator-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }
        
        .indicator-fill {
            height: 100%;
            background: var(--success-color);
            transition: all 0.3s ease;
            width: 0%;
        }
        
        .indicator-text {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .additional-info-section {
            border-top: 1px solid #e0e0e0;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        
        .additional-info-section h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .validation-summary {
            margin-bottom: 2rem;
        }
        
        .summary-section {
            background: var(--secondary-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-section h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: white;
            border-radius: var(--border-radius);
        }
        
        .summary-label {
            font-weight: 500;
            color: #666;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .documents-status {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }
        
        .doc-progress {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .doc-progress-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .doc-progress-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s ease;
        }
        
        .doc-progress-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .missing-docs ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }
        
        .doc-note {
            font-size: 0.875rem;
            color: #666;
            font-style: italic;
        }
        
        .docs-complete {
            color: var(--success-color);
            font-weight: 500;
            text-align: center;
            padding: 1rem;
        }
        
        .consent-section {
            background: #fff8e1;
            border: 1px solid #ffb300;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .loan-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .help-card,
        .process-card,
        .contact-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        
        .help-card h3,
        .process-card h3,
        .contact-card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .help-card ul {
            list-style: none;
            padding: 0;
        }
        
        .help-card li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .help-card li:last-child {
            border-bottom: none;
        }
        
        .process-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .process-step-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .process-step-item .step-number {
            width: 30px;
            height: 30px;
            background: var(--accent-1);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .contact-options {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .contact-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: background-color 0.3s ease;
        }
        
        .contact-option:hover {
            background-color: var(--secondary-color);
        }
        
        @media (max-width: 1024px) {
            .loan-request-content {
                grid-template-columns: 1fr;
            }
            
            .loan-sidebar {
                order: -1;
            }
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .simulation-content {
                grid-template-columns: 1fr;
            }
            
            .analysis-content {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .loan-progress {
                flex-wrap: wrap;
                gap: 1rem;
            }
        }
    </style>
</body>
</html>