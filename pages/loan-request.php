<?php
$pageKey = 'loan_request';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
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
                    
                    <div class="loan-form-progress">
                        <div class="loan-form-progress__step active" data-step="1">
                            <div class="loan-form-progress__step-number">1</div>
                            <div class="loan-form-progress__step-label">Projet</div>
                        </div>
                        <div class="loan-form-progress__step" data-step="2">
                            <div class="loan-form-progress__step-number">2</div>
                            <div class="loan-form-progress__step-label">Finances</div>
                        </div>
                        <div class="loan-form-progress__step" data-step="3">
                            <div class="loan-form-progress__step-number">3</div>
                            <div class="loan-form-progress__step-label">Professionnel</div>
                        </div>
                        <div class="loan-form-progress__step" data-step="4">
                            <div class="loan-form-progress__step-number">4</div>
                            <div class="loan-form-progress__step-label">Validation</div>
                        </div>
                    </div>
                </div>
                
                <div class="loan-request-content">
                    <div class="loan-form-container">
                        <form id="loanRequestForm" class="loan-form" onsubmit="handleLoanSubmit(event)" novalidate>
                            
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
                                               placeholder="+33 7 45 50 52 07">
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
                                                   placeholder="+33 7 45 50 52 07">
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
                            <div class="sidebar-process">
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">1</span>
                                    <div class="sidebar-process__item-content">
                                        <strong>Soumission</strong>
                                        <small>Votre demande nous parvient instantanément</small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">2</span>
                                    <div class="sidebar-process__item-content">
                                        <strong>Analyse</strong>
                                        <small>Étude de votre dossier sous 24h</small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">3</span>
                                    <div class="sidebar-process__item-content">
                                        <strong>Partenaires</strong>
                                        <small>Transmission aux institutions financières</small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">4</span>
                                    <div class="sidebar-process__item-content">
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
                                <a href="tel:+33745505207" class="contact-option">
                                    <span class="contact-icon">📞</span>
                                    <span>+33 7 45 50 52 07</span>
                                </a>
                                <a href="mailto:support@prestacapi.com" class="contact-option">
                                    <span class="contact-icon">📧</span>
                                    <span>support@prestacapi.com</span>
                                </a>
                                <a href="https://wa.me/33745505207" class="contact-option">
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
            
            document.querySelectorAll('.loan-form-progress__step').forEach((step, index) => {
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

                    // 1. Affichez une notification générale
                    showToast(data.message || 'Erreur lors de la soumission', 'error');
                    
                    // 2. Essayez de trouver le champ correspondant à l'erreur
                    const errorMessage = data.message.toLowerCase();
                    let fieldToHighlight = null;

                    if (errorMessage.includes('objectif') || errorMessage.includes('purpose')) {
                        fieldToHighlight = document.getElementById('loanPurpose');
                    } else if (errorMessage.includes('montant') || errorMessage.includes('amount')) {
                        fieldToHighlight = document.getElementById('loanAmount');
                    } else if (errorMessage.includes('revenus') || errorMessage.includes('income')) {
                        fieldToHighlight = document.getElementById('monthlyIncome');
                    } else if (errorMessage.includes('charges') || errorMessage.includes('expenses')) {
                        fieldToHighlight = document.getElementById('monthlyExpenses');
                    }
                    // Ajoutez d'autres conditions pour les autres champs si nécessaire

                    // 3. Si un champ est trouvé, affichez l'erreur sous ce champ
                    if (fieldToHighlight) {
                        showFieldError(fieldToHighlight, data.message);
                        
                        // Optionnel : faites défiler la page jusqu'au champ en erreur
                        fieldToHighlight.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    // -- FIN DE LA MODIFICATION --
                }
            })
            .catch(error => {
                submitBtn.classList.remove('loading');
                showToast('Une erreur est survenue', 'error');
            });
        }
        
        function showToast(message, type = 'success') {
            // Nous nous assurons que l'élément HTML pour la notification existe
            let toast = document.getElementById('toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast';
                toast.innerHTML = `
                    <div class="toast-content">
                        <div class="toast-icon" id="toastIcon"></div>
                        <div class="toast-message" id="toastMessage"></div>
                    </div>`;
                document.body.appendChild(toast);
            }

            const iconEl = document.getElementById('toastIcon');
            const messageEl = document.getElementById('toastMessage');

            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };

            if(iconEl) iconEl.textContent = icons[type] || icons.info;
            if(messageEl) messageEl.textContent = message;

            toast.className = `toast show ${type}`;

            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
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
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>