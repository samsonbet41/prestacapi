<?php
$pageKey = 'loan_request';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

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

// Préparation des traductions pour JavaScript
$js_translations = [
    'requiredField' => $lang->get('validation_required'),
    'amountRangeError' => $lang->get('loan_request_validation_amount_range'),
    'expensesVsIncomeError' => $lang->get('loan_request_validation_expenses_income'),
    'capacityExcellent' => $lang->get('loan_request_analysis_capacity_excellent'),
    'capacityGood' => $lang->get('loan_request_analysis_capacity_good'),
    'capacityLimited' => $lang->get('loan_request_analysis_capacity_limited'),
    'monthsSuffix' => $lang->get('loan_duration_months'),
    'submitSuccess' => $lang->get('loan_request_submit_success'),
    'submitError' => $lang->get('loan_request_submit_error_general'),
    'errorOccurred' => $lang->get('js_error_occurred'),
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
                        <h3><?php echo $lang->get('loan_request_active_loan_title'); ?></h3>
                        <p><?php echo $lang->get('loan_request_active_loan_desc'); ?></p>
                        <div class="alert-actions">
                            <a href="<?php echo $lang->pageUrl('dashboard'); ?>" class="btn btn-outline">
                                <?php echo $lang->get('loan_request_active_loan_cta'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                
                <div class="loan-request-header">
                    <div class="page-breadcrumb">
                        <a href="<?php echo $lang->pageUrl('dashboard'); ?>"><?php echo $lang->get('dashboard'); ?></a>
                        <span class="breadcrumb-separator">→</span>
                        <span class="breadcrumb-current"><?php echo $lang->get('loan_request_title'); ?></span>
                    </div>
                    
                    <h1 class="page-title"><?php echo $lang->get('loan_request_title'); ?></h1>
                    <p class="page-subtitle"><?php echo $lang->get('loan_request_subtitle'); ?></p>
                    
                    <div class="loan-form-progress">
                        <div class="loan-form-progress__step active" data-step="1">
                            <div class="loan-form-progress__step-number">1</div>
                            <div class="loan-form-progress__step-label"><?php echo $lang->get('loan_request_step_project'); ?></div>
                        </div>
                        <div class="loan-form-progress__step" data-step="2">
                            <div class="loan-form-progress__step-number">2</div>
                            <div class="loan-form-progress__step-label"><?php echo $lang->get('loan_request_step_finances'); ?></div>
                        </div>
                        <div class="loan-form-progress__step" data-step="3">
                            <div class="loan-form-progress__step-number">3</div>
                            <div class="loan-form-progress__step-label"><?php echo $lang->get('loan_request_step_professional'); ?></div>
                        </div>
                        <div class="loan-form-progress__step" data-step="4">
                            <div class="loan-form-progress__step-number">4</div>
                            <div class="loan-form-progress__step-label"><?php echo $lang->get('loan_request_step_validation'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="loan-request-content">
                    <div class="loan-form-container">
                        <form id="loanRequestForm" class="loan-form" onsubmit="handleLoanSubmit(event)" novalidate>
                            
                            <div class="form-step active" data-step="1">
                                <div class="step-header">
                                    <h2 class="step-title"><?php echo $lang->get('loan_request_step1_title'); ?></h2>
                                    <p class="step-description"><?php echo $lang->get('loan_request_step1_desc'); ?></p>
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
                                        <div class="form-help"><?php echo $lang->get('loan_request_amount_help'); ?></div>
                                        <div class="form-error" id="amountError"></div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="loanDuration" class="form-label">
                                            <?php echo $lang->get('loan_duration'); ?> *
                                        </label>
                                        <select id="loanDuration" name="duration" class="form-select" required>
                                            <option value=""><?php echo $lang->get('loan_request_duration_placeholder'); ?></option>
                                            <option value="6">6 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="12">12 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="18">18 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="24">24 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="36">36 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="48">48 <?php echo $lang->get('loan_duration_months'); ?></option>
                                            <option value="60">60 <?php echo $lang->get('loan_duration_months'); ?></option>
                                        </select>
                                        <div class="form-error" id="durationError"></div>
                                    </div>
                                </div>
                                
                                <div class="loan-simulation" id="loanSimulation">
                                    <div class="simulation-header">
                                        <h3><?php echo $lang->get('loan_request_simulation_title'); ?></h3>
                                    </div>
                                    <div class="simulation-content">
                                        <div class="simulation-item">
                                            <span class="simulation-label"><?php echo $lang->get('loan_request_simulation_monthly'); ?></span>
                                            <span class="simulation-value" id="monthlyPayment">-</span>
                                        </div>
                                        <div class="simulation-item">
                                            <span class="simulation-label"><?php echo $lang->get('loan_request_simulation_total'); ?></span>
                                            <span class="simulation-value" id="totalCost">-</span>
                                        </div>
                                        <div class="simulation-item">
                                            <span class="simulation-label"><?php echo $lang->get('loan_request_simulation_rate'); ?></span>
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
                                    <div class="form-help"><?php echo $lang->get('loan_request_purpose_help'); ?></div>
                                    <div class="form-error" id="purposeError"></div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="2">
                                <div class="step-header">
                                    <h2 class="step-title"><?php echo $lang->get('loan_request_step2_title'); ?></h2>
                                    <p class="step-description"><?php echo $lang->get('loan_request_step2_desc'); ?></p>
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
                                            <span class="amount-currency">€/<?php echo $lang->get('loan_duration_months'); ?></span>
                                        </div>
                                        <div class="form-help"><?php echo $lang->get('loan_request_income_help'); ?></div>
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
                                            <span class="amount-currency">€/<?php echo $lang->get('loan_duration_months'); ?></span>
                                        </div>
                                        <div class="form-help"><?php echo $lang->get('loan_request_expenses_help'); ?></div>
                                        <div class="form-error" id="monthlyExpensesError"></div>
                                    </div>
                                </div>
                                
                                <div class="financial-analysis" id="financialAnalysis">
                                    <div class="analysis-header">
                                        <h3><?php echo $lang->get('loan_request_analysis_title'); ?></h3>
                                    </div>
                                    <div class="analysis-content">
                                        <div class="analysis-item">
                                            <span class="analysis-label"><?php echo $lang->get('loan_request_analysis_remaining'); ?></span>
                                            <span class="analysis-value" id="remainingIncome">-</span>
                                        </div>
                                        <div class="analysis-item">
                                            <span class="analysis-label"><?php echo $lang->get('loan_request_analysis_debt_ratio'); ?></span>
                                            <span class="analysis-value" id="debtRatio">-</span>
                                        </div>
                                        <div class="analysis-indicator" id="analysisIndicator">
                                            <div class="indicator-bar">
                                                <div class="indicator-fill"></div>
                                            </div>
                                            <div class="indicator-text"><?php echo $lang->get('loan_request_analysis_capacity'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="otherLoans" class="form-label">
                                        <?php echo $lang->get('loan_other_loans'); ?>
                                    </label>
                                    <div class="amount-input-wrapper">
                                        <input type="number" 
                                               id="otherLoans" 
                                               name="other_loans" 
                                               class="form-input amount-input" 
                                               min="0" 
                                               step="10"
                                               placeholder="0">
                                        <span class="amount-currency">€/<?php echo $lang->get('loan_duration_months'); ?></span>
                                    </div>
                                    <div class="form-help"><?php echo $lang->get('loan_request_other_loans_help'); ?></div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="3">
                                <div class="step-header">
                                    <h2 class="step-title"><?php echo $lang->get('loan_request_step3_title'); ?></h2>
                                    <p class="step-description"><?php echo $lang->get('loan_request_step3_desc'); ?></p>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="employmentStatus" class="form-label">
                                            <?php echo $lang->get('loan_employment_status'); ?> *
                                        </label>
                                        <select id="employmentStatus" name="employment_status" class="form-select" required>
                                            <option value=""><?php echo $lang->get('loan_request_employment_status_placeholder'); ?></option>
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
                                            <option value=""><?php echo $lang->get('loan_request_employment_duration_placeholder'); ?></option>
                                            <option value="3"><?php echo $lang->get('loan_request_employment_duration_option_1'); ?></option>
                                            <option value="6"><?php echo $lang->get('loan_request_employment_duration_option_2'); ?></option>
                                            <option value="12"><?php echo $lang->get('loan_request_employment_duration_option_3'); ?></option>
                                            <option value="24"><?php echo $lang->get('loan_request_employment_duration_option_4'); ?></option>
                                            <option value="60"><?php echo $lang->get('loan_request_employment_duration_option_5'); ?></option>
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
                                               placeholder="<?php echo $lang->get('loan_request_employer_name_placeholder'); ?>">
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
                                    <h3><?php echo $lang->get('loan_request_additional_info_title'); ?></h3>
                                    
                                    <div class="form-group">
                                        <label for="collateral" class="form-label">
                                            <?php echo $lang->get('loan_collateral'); ?>
                                        </label>
                                        <textarea id="collateral" 
                                                  name="collateral" 
                                                  class="form-textarea" 
                                                  rows="3" 
                                                  placeholder="<?php echo $lang->get('loan_request_collateral_placeholder'); ?>"></textarea>
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
                                                   placeholder="<?php echo $lang->get('loan_request_co_signer_name_placeholder'); ?>">
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
                                                  placeholder="<?php echo $lang->get('loan_request_notes_placeholder'); ?>"></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-step" data-step="4">
                                <div class="step-header">
                                    <h2 class="step-title"><?php echo $lang->get('loan_request_step4_title'); ?></h2>
                                    <p class="step-description"><?php echo $lang->get('loan_request_step4_desc'); ?></p>
                                </div>
                                
                                <div class="validation-summary" id="validationSummary">
                                    <div class="summary-section">
                                        <h3><?php echo $lang->get('loan_request_summary_title'); ?></h3>
                                        <div class="summary-grid">
                                            <div class="summary-item">
                                                <span class="summary-label"><?php echo $lang->get('loan_amount'); ?></span>
                                                <span class="summary-value" id="summaryAmount">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label"><?php echo $lang->get('loan_duration'); ?></span>
                                                <span class="summary-value" id="summaryDuration">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label"><?php echo $lang->get('loan_request_simulation_monthly'); ?></span>
                                                <span class="summary-value" id="summaryMonthlyPayment">-</span>
                                            </div>
                                            <div class="summary-item">
                                                <span class="summary-label"><?php echo $lang->get('loan_monthly_income'); ?></span>
                                                <span class="summary-value" id="summaryIncome">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="summary-section">
                                        <h3><?php echo $lang->get('loan_request_docs_status_title'); ?></h3>
                                        <div class="documents-status">
                                            <div class="doc-progress">
                                                <div class="doc-progress-bar">
                                                    <div class="doc-progress-fill" style="width: <?php echo $documentStatus['completion_percentage']; ?>%"></div>
                                                </div>
                                                <span class="doc-progress-text"><?php echo $lang->get('loan_request_docs_verified_text', ['verified' => $documentStatus['verified'], 'total' => $documentStatus['total_required']]); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($requiredDocs)): ?>
                                                <div class="missing-docs">
                                                    <h4><?php echo $lang->get('loan_request_docs_missing_title'); ?></h4>
                                                    <ul>
                                                        <?php foreach ($requiredDocs as $doc): ?>
                                                            <li><?php echo $lang->get('document_type_' . strtolower(str_replace(' ', '_', $doc['name']))); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                    <p class="doc-note">
                                                        <?php echo $lang->get('loan_request_docs_missing_note'); ?>
                                                    </p>
                                                </div>
                                            <?php else: ?>
                                                <div class="docs-complete">
                                                    <?php echo $lang->get('loan_request_docs_complete_text'); ?>
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
                                            <?php echo $lang->get('loan_request_consent_verification'); ?>
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="consent_processing" id="consentProcessing" required>
                                            <span class="checkbox-custom"></span>
                                            <?php echo $lang->get('loan_request_consent_processing'); ?>
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="consent_terms" id="consentTerms" required>
                                            <span class="checkbox-custom"></span>
                                            <?php echo $lang->get('loan_request_consent_terms', [
                                                'terms_link' => '<a href="' . $lang->pageUrl('terms') . '" target="_blank">' . $lang->get('terms') . '</a>',
                                                'privacy_link' => '<a href="' . $lang->pageUrl('privacy') . '" target="_blank">' . $lang->get('privacy') . '</a>'
                                            ]); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="prevStepBtn" class="btn btn-outline" onclick="previousStep()" style="display: none;">
                                    <?php echo $lang->get('previous'); ?>
                                </button>
                                
                                <button type="button" id="nextStepBtn" class="btn btn-primary" onclick="nextStep()">
                                    <?php echo $lang->get('next'); ?>
                                </button>
                                
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-large" style="display: none;">
                                    <span class="btn-text"><?php echo $lang->get('loan_request_submit_btn'); ?></span>
                                    <span class="btn-loader"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="loan-sidebar">
                        <div class="help-card">
                            <h3><?php echo $lang->get('loan_request_sidebar_help_title'); ?></h3>
                            <ul>
                                <li><?php echo $lang->get('loan_request_sidebar_help_item1'); ?></li>
                                <li><?php echo $lang->get('loan_request_sidebar_help_item2'); ?></li>
                                <li><?php echo $lang->get('loan_request_sidebar_help_item3'); ?></li>
                                <li><?php echo $lang->get('loan_request_sidebar_help_item4'); ?></li>
                            </ul>
                        </div>
                        
                        <div class="process-card">
                            <h3><?php echo $lang->get('loan_request_sidebar_process_title'); ?></h3>
                            <div class="sidebar-process">
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">1</span>
                                    <div class="sidebar-process__item-content">
                                        <strong><?php echo $lang->get('loan_request_sidebar_process1_title'); ?></strong>
                                        <small><?php echo $lang->get('loan_request_sidebar_process1_desc'); ?></small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">2</span>
                                    <div class="sidebar-process__item-content">
                                        <strong><?php echo $lang->get('loan_request_sidebar_process2_title'); ?></strong>
                                        <small><?php echo $lang->get('loan_request_sidebar_process2_desc'); ?></small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">3</span>
                                    <div class="sidebar-process__item-content">
                                        <strong><?php echo $lang->get('loan_request_sidebar_process3_title'); ?></strong>
                                        <small><?php echo $lang->get('loan_request_sidebar_process3_desc'); ?></small>
                                    </div>
                                </div>
                                <div class="sidebar-process__item">
                                    <span class="sidebar-process__item-number">4</span>
                                    <div class="sidebar-process__item-content">
                                        <strong><?php echo $lang->get('loan_request_sidebar_process4_title'); ?></strong>
                                        <small><?php echo $lang->get('loan_request_sidebar_process4_desc'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contact-card">
                            <h3><?php echo $lang->get('loan_request_sidebar_contact_title'); ?></h3>
                            <p><?php echo $lang->get('loan_request_sidebar_contact_desc'); ?></p>
                            <div class="contact-options">
                                <a href="tel:+33745505207" class="contact-option">
                                    <span class="contact-icon">📞</span>
                                    <span>+33 7 45 50 52 07</span>
                                a>
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
        const translations = <?php echo json_encode($js_translations); ?>;
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
                if ((input.type === 'checkbox' && !input.checked) || (input.type !== 'checkbox' && !input.value.trim())) {
                    showFieldError(input, translations.requiredField);
                    isValid = false;
                } else {
                    clearFieldError(input);
                }
            });
            
            if (currentStep === 1) {
                const amountInput = document.getElementById('loanAmount');
                const amount = parseFloat(amountInput.value);
                if (amount < 500 || amount > 50000) {
                    showFieldError(amountInput, translations.amountRangeError);
                    isValid = false;
                }
            }
            
            if (currentStep === 2) {
                const income = parseFloat(document.getElementById('monthlyIncome').value);
                const expenses = parseFloat(document.getElementById('monthlyExpenses').value);
                
                if (expenses >= income) {
                    showFieldError(document.getElementById('monthlyExpenses'), translations.expensesVsIncomeError);
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function showFieldError(field, message) {
            const formGroup = field.closest('.form-group');
            if (!formGroup) return;
            let errorElement = formGroup.querySelector('.form-error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'form-error';
                formGroup.appendChild(errorElement);
            }
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            field.classList.add('error');
        }

        function clearFieldError(field) {
            const formGroup = field.closest('.form-group');
            if (!formGroup) return;
            const errorElement = formGroup.querySelector('.form-error');
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
                const monthlyRate = 0.06 / 12; // Taux moyen pour la simulation
                const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, duration)) / 
                                    (Math.pow(1 + monthlyRate, duration) - 1);
                const totalCost = monthlyPayment * duration;
                
                document.getElementById('monthlyPayment').textContent = Math.round(monthlyPayment) + '€';
                document.getElementById('totalCost').textContent = Math.round(totalCost) + '€';
            } else {
                document.getElementById('monthlyPayment').textContent = '-';
                document.getElementById('totalCost').textContent = '-';
            }
        }
        
        function updateFinancialAnalysis() {
            const income = parseFloat(document.getElementById('monthlyIncome').value) || 0;
            const expenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
            const otherLoans = parseFloat(document.getElementById('otherLoans').value) || 0;
            
            const remainingEl = document.getElementById('remainingIncome');
            const debtRatioEl = document.getElementById('debtRatio');
            const indicator = document.querySelector('#analysisIndicator .indicator-fill');
            const indicatorText = document.querySelector('#analysisIndicator .indicator-text');

            if (income > 0) {
                const remaining = income - expenses - otherLoans;
                const debtRatio = ((expenses + otherLoans) / income * 100);
                
                remainingEl.textContent = remaining.toFixed(2) + '€';
                debtRatioEl.textContent = debtRatio.toFixed(1) + '%';
                
                if (debtRatio < 33) {
                    indicator.style.width = '80%';
                    indicator.style.background = '#4CAF50';
                    indicatorText.textContent = translations.capacityExcellent;
                } else if (debtRatio < 50) {
                    indicator.style.width = '60%';
                    indicator.style.background = '#FF9800';
                    indicatorText.textContent = translations.capacityGood;
                } else {
                    indicator.style.width = '30%';
                    indicator.style.background = '#E53935';
                    indicatorText.textContent = translations.capacityLimited;
                }
            } else {
                remainingEl.textContent = '-';
                debtRatioEl.textContent = '-';
                indicator.style.width = '0%';
                indicatorText.textContent = '...';
            }
        }
        
        function updateValidationSummary() {
            const amount = document.getElementById('loanAmount').value;
            const duration = document.getElementById('loanDuration').value;
            const income = document.getElementById('monthlyIncome').value;
            const monthlyPayment = document.getElementById('monthlyPayment').textContent;
            
            document.getElementById('summaryAmount').textContent = amount ? amount + '€' : '-';
            document.getElementById('summaryDuration').textContent = duration ? `${duration} ${translations.monthsSuffix}` : '-';
            document.getElementById('summaryMonthlyPayment').textContent = monthlyPayment || '-';
            document.getElementById('summaryIncome').textContent = income ? income + '€' : '-';
        }
        
        function handleLoanSubmit(event) {
            event.preventDefault();
            
            // Re-valider la dernière étape avant soumission
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
                    showToast(translations.submitSuccess, 'success');
                    setTimeout(() => {
                        window.location.href = '<?php echo $lang->pageUrl('dashboard'); ?>';
                    }, 2000);
                } else {
                    showToast(data.message || translations.submitError, 'error');
                }
            })
            .catch(error => {
                submitBtn.classList.remove('loading');
                showToast(translations.errorOccurred, 'error');
            });
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