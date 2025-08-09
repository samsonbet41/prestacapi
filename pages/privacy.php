<?php
require_once 'includes/header.php';

$seo->generateTitle($lang->get('privacy_policy_title'));
$seo->generateDescription($lang->get('privacy_policy_description'));

$lastUpdated = '2025-01-01';
?>

<main class="privacy-page">
    <div class="container">
        <div class="page-header">
            <h1><?php echo $lang->get('privacy_policy'); ?></h1>
            <p class="last-updated"><?php echo $lang->get('last_updated'); ?>: <?php echo $lang->formatDate($lastUpdated); ?></p>
        </div>

        <div class="privacy-content">
            <div class="privacy-nav">
                <h3><?php echo $lang->get('table_of_contents'); ?></h3>
                <ul class="nav-list">
                    <li><a href="#introduction"><?php echo $lang->get('introduction'); ?></a></li>
                    <li><a href="#data-collection"><?php echo $lang->get('data_collection'); ?></a></li>
                    <li><a href="#data-usage"><?php echo $lang->get('data_usage'); ?></a></li>
                    <li><a href="#data-sharing"><?php echo $lang->get('data_sharing'); ?></a></li>
                    <li><a href="#data-security"><?php echo $lang->get('data_security'); ?></a></li>
                    <li><a href="#your-rights"><?php echo $lang->get('your_rights'); ?></a></li>
                    <li><a href="#cookies"><?php echo $lang->get('cookies'); ?></a></li>
                    <li><a href="#children"><?php echo $lang->get('children_privacy'); ?></a></li>
                    <li><a href="#updates"><?php echo $lang->get('policy_updates'); ?></a></li>
                    <li><a href="#contact"><?php echo $lang->get('contact_info'); ?></a></li>
                </ul>
            </div>

            <div class="privacy-main">
                <section id="introduction" class="privacy-section">
                    <h2><?php echo $lang->get('introduction'); ?></h2>
                    <p><?php echo $lang->get('privacy_intro_p1'); ?></p>
                    <p><?php echo $lang->get('privacy_intro_p2'); ?></p>
                    <p><?php echo $lang->get('privacy_intro_p3'); ?></p>
                    
                    <div class="info-box">
                        <div class="info-icon">
                            <i class="icon-info"></i>
                        </div>
                        <div class="info-content">
                            <h4><?php echo $lang->get('important_note'); ?></h4>
                            <p><?php echo $lang->get('privacy_important_note'); ?></p>
                        </div>
                    </div>
                </section>

                <section id="data-collection" class="privacy-section">
                    <h2><?php echo $lang->get('data_collection'); ?></h2>
                    <p><?php echo $lang->get('data_collection_intro'); ?></p>
                    
                    <h3><?php echo $lang->get('personal_data_collected'); ?></h3>
                    <ul class="data-list">
                        <li><strong><?php echo $lang->get('identity_data'); ?>:</strong> <?php echo $lang->get('identity_data_desc'); ?></li>
                        <li><strong><?php echo $lang->get('contact_data'); ?>:</strong> <?php echo $lang->get('contact_data_desc'); ?></li>
                        <li><strong><?php echo $lang->get('financial_data'); ?>:</strong> <?php echo $lang->get('financial_data_desc'); ?></li>
                        <li><strong><?php echo $lang->get('document_data'); ?>:</strong> <?php echo $lang->get('document_data_desc'); ?></li>
                        <li><strong><?php echo $lang->get('technical_data'); ?>:</strong> <?php echo $lang->get('technical_data_desc'); ?></li>
                        <li><strong><?php echo $lang->get('usage_data'); ?>:</strong> <?php echo $lang->get('usage_data_desc'); ?></li>
                    </ul>

                    <h3><?php echo $lang->get('collection_methods'); ?></h3>
                    <div class="collection-methods">
                        <div class="method-item">
                            <i class="icon-form"></i>
                            <div>
                                <h4><?php echo $lang->get('direct_collection'); ?></h4>
                                <p><?php echo $lang->get('direct_collection_desc'); ?></p>
                            </div>
                        </div>
                        <div class="method-item">
                            <i class="icon-globe"></i>
                            <div>
                                <h4><?php echo $lang->get('automatic_collection'); ?></h4>
                                <p><?php echo $lang->get('automatic_collection_desc'); ?></p>
                            </div>
                        </div>
                        <div class="method-item">
                            <i class="icon-users"></i>
                            <div>
                                <h4><?php echo $lang->get('third_party_collection'); ?></h4>
                                <p><?php echo $lang->get('third_party_collection_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="data-usage" class="privacy-section">
                    <h2><?php echo $lang->get('data_usage'); ?></h2>
                    <p><?php echo $lang->get('data_usage_intro'); ?></p>
                    
                    <div class="usage-purposes">
                        <div class="purpose-item">
                            <div class="purpose-icon">
                                <i class="icon-document"></i>
                            </div>
                            <div class="purpose-content">
                                <h4><?php echo $lang->get('loan_processing'); ?></h4>
                                <p><?php echo $lang->get('loan_processing_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="purpose-item">
                            <div class="purpose-icon">
                                <i class="icon-shield"></i>
                            </div>
                            <div class="purpose-content">
                                <h4><?php echo $lang->get('fraud_prevention'); ?></h4>
                                <p><?php echo $lang->get('fraud_prevention_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="purpose-item">
                            <div class="purpose-icon">
                                <i class="icon-chart"></i>
                            </div>
                            <div class="purpose-content">
                                <h4><?php echo $lang->get('service_improvement'); ?></h4>
                                <p><?php echo $lang->get('service_improvement_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="purpose-item">
                            <div class="purpose-icon">
                                <i class="icon-mail"></i>
                            </div>
                            <div class="purpose-content">
                                <h4><?php echo $lang->get('communication'); ?></h4>
                                <p><?php echo $lang->get('communication_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="purpose-item">
                            <div class="purpose-icon">
                                <i class="icon-scale"></i>
                            </div>
                            <div class="purpose-content">
                                <h4><?php echo $lang->get('legal_compliance'); ?></h4>
                                <p><?php echo $lang->get('legal_compliance_desc'); ?></p>
                            </div>
                        </div>
                    </div>

                    <h3><?php echo $lang->get('legal_basis'); ?></h3>
                    <table class="legal-basis-table">
                        <thead>
                            <tr>
                                <th><?php echo $lang->get('purpose'); ?></th>
                                <th><?php echo $lang->get('legal_basis'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $lang->get('loan_processing'); ?></td>
                                <td><?php echo $lang->get('contract_performance'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $lang->get('fraud_prevention'); ?></td>
                                <td><?php echo $lang->get('legitimate_interest'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $lang->get('marketing'); ?></td>
                                <td><?php echo $lang->get('consent'); ?></td>
                            </tr>
                            <tr>
                                <td><?php echo $lang->get('legal_obligations'); ?></td>
                                <td><?php echo $lang->get('legal_requirement'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section id="data-sharing" class="privacy-section">
                    <h2><?php echo $lang->get('data_sharing'); ?></h2>
                    <p><?php echo $lang->get('data_sharing_intro'); ?></p>
                    
                    <h3><?php echo $lang->get('who_we_share_with'); ?></h3>
                    <div class="sharing-categories">
                        <div class="category-item">
                            <h4><i class="icon-bank"></i> <?php echo $lang->get('financial_partners'); ?></h4>
                            <p><?php echo $lang->get('financial_partners_desc'); ?></p>
                        </div>
                        
                        <div class="category-item">
                            <h4><i class="icon-cog"></i> <?php echo $lang->get('service_providers'); ?></h4>
                            <p><?php echo $lang->get('service_providers_desc'); ?></p>
                        </div>
                        
                        <div class="category-item">
                            <h4><i class="icon-scale"></i> <?php echo $lang->get('legal_authorities'); ?></h4>
                            <p><?php echo $lang->get('legal_authorities_desc'); ?></p>
                        </div>
                        
                        <div class="category-item">
                            <h4><i class="icon-shield"></i> <?php echo $lang->get('security_companies'); ?></h4>
                            <p><?php echo $lang->get('security_companies_desc'); ?></p>
                        </div>
                    </div>

                    <div class="warning-box">
                        <div class="warning-icon">
                            <i class="icon-warning"></i>
                        </div>
                        <div class="warning-content">
                            <h4><?php echo $lang->get('no_sale_policy'); ?></h4>
                            <p><?php echo $lang->get('no_sale_policy_desc'); ?></p>
                        </div>
                    </div>
                </section>

                <section id="data-security" class="privacy-section">
                    <h2><?php echo $lang->get('data_security'); ?></h2>
                    <p><?php echo $lang->get('data_security_intro'); ?></p>
                    
                    <div class="security-measures">
                        <div class="measure-item">
                            <div class="measure-icon">
                                <i class="icon-lock"></i>
                            </div>
                            <div class="measure-content">
                                <h4><?php echo $lang->get('encryption'); ?></h4>
                                <p><?php echo $lang->get('encryption_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="measure-item">
                            <div class="measure-icon">
                                <i class="icon-shield-check"></i>
                            </div>
                            <div class="measure-content">
                                <h4><?php echo $lang->get('access_control'); ?></h4>
                                <p><?php echo $lang->get('access_control_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="measure-item">
                            <div class="measure-icon">
                                <i class="icon-eye"></i>
                            </div>
                            <div class="measure-content">
                                <h4><?php echo $lang->get('monitoring'); ?></h4>
                                <p><?php echo $lang->get('monitoring_desc'); ?></p>
                            </div>
                        </div>
                        
                        <div class="measure-item">
                            <div class="measure-icon">
                                <i class="icon-backup"></i>
                            </div>
                            <div class="measure-content">
                                <h4><?php echo $lang->get('backup'); ?></h4>
                                <p><?php echo $lang->get('backup_desc'); ?></p>
                            </div>
                        </div>
                    </div>

                    <h3><?php echo $lang->get('data_retention'); ?></h3>
                    <p><?php echo $lang->get('data_retention_intro'); ?></p>
                    <ul>
                        <li><?php echo $lang->get('retention_active_accounts'); ?></li>
                        <li><?php echo $lang->get('retention_closed_accounts'); ?></li>
                        <li><?php echo $lang->get('retention_legal_requirements'); ?></li>
                        <li><?php echo $lang->get('retention_marketing'); ?></li>
                    </ul>
                </section>

                <section id="your-rights" class="privacy-section">
                    <h2><?php echo $lang->get('your_rights'); ?></h2>
                    <p><?php echo $lang->get('your_rights_intro'); ?></p>
                    
                    <div class="rights-grid">
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-info"></i>
                            </div>
                            <h4><?php echo $lang->get('right_access'); ?></h4>
                            <p><?php echo $lang->get('right_access_desc'); ?></p>
                        </div>
                        
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-edit"></i>
                            </div>
                            <h4><?php echo $lang->get('right_rectification'); ?></h4>
                            <p><?php echo $lang->get('right_rectification_desc'); ?></p>
                        </div>
                        
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-trash"></i>
                            </div>
                            <h4><?php echo $lang->get('right_erasure'); ?></h4>
                            <p><?php echo $lang->get('right_erasure_desc'); ?></p>
                        </div>
                        
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-pause"></i>
                            </div>
                            <h4><?php echo $lang->get('right_restriction'); ?></h4>
                            <p><?php echo $lang->get('right_restriction_desc'); ?></p>
                        </div>
                        
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-download"></i>
                            </div>
                            <h4><?php echo $lang->get('right_portability'); ?></h4>
                            <p><?php echo $lang->get('right_portability_desc'); ?></p>
                        </div>
                        
                        <div class="right-item">
                            <div class="right-icon">
                                <i class="icon-x"></i>
                            </div>
                            <h4><?php echo $lang->get('right_objection'); ?></h4>
                            <p><?php echo $lang->get('right_objection_desc'); ?></p>
                        </div>
                    </div>

                    <div class="exercise-rights">
                        <h3><?php echo $lang->get('exercise_rights'); ?></h3>
                        <p><?php echo $lang->get('exercise_rights_desc'); ?></p>
                        <div class="contact-methods">
                            <a href="mailto:privacy@prestacapi.com" class="contact-btn">
                                <i class="icon-mail"></i>
                                privacy@prestacapi.com
                            </a>
                            <a href="<?php echo generateLocalizedUrl('contact'); ?>?subject=privacy" class="contact-btn">
                                <i class="icon-form"></i>
                                <?php echo $lang->get('contact_form'); ?>
                            </a>
                        </div>
                    </div>
                </section>

                <section id="cookies" class="privacy-section">
                    <h2><?php echo $lang->get('cookies'); ?></h2>
                    <p><?php echo $lang->get('cookies_intro'); ?></p>
                    
                    <h3><?php echo $lang->get('types_of_cookies'); ?></h3>
                    <div class="cookies-types">
                        <div class="cookie-type">
                            <h4><?php echo $lang->get('essential_cookies'); ?></h4>
                            <p><?php echo $lang->get('essential_cookies_desc'); ?></p>
                            <span class="cookie-status required"><?php echo $lang->get('required'); ?></span>
                        </div>
                        
                        <div class="cookie-type">
                            <h4><?php echo $lang->get('performance_cookies'); ?></h4>
                            <p><?php echo $lang->get('performance_cookies_desc'); ?></p>
                            <span class="cookie-status optional"><?php echo $lang->get('optional'); ?></span>
                        </div>
                        
                        <div class="cookie-type">
                            <h4><?php echo $lang->get('functional_cookies'); ?></h4>
                            <p><?php echo $lang->get('functional_cookies_desc'); ?></p>
                            <span class="cookie-status optional"><?php echo $lang->get('optional'); ?></span>
                        </div>
                        
                        <div class="cookie-type">
                            <h4><?php echo $lang->get('marketing_cookies'); ?></h4>
                            <p><?php echo $lang->get('marketing_cookies_desc'); ?></p>
                            <span class="cookie-status optional"><?php echo $lang->get('optional'); ?></span>
                        </div>
                    </div>

                    <h3><?php echo $lang->get('manage_cookies'); ?></h3>
                    <p><?php echo $lang->get('manage_cookies_desc'); ?></p>
                    <button class="btn btn-primary" onclick="openCookieSettings()">
                        <i class="icon-settings"></i>
                        <?php echo $lang->get('cookie_settings'); ?>
                    </button>
                </section>

                <section id="children" class="privacy-section">
                    <h2><?php echo $lang->get('children_privacy'); ?></h2>
                    <p><?php echo $lang->get('children_privacy_intro'); ?></p>
                    <p><?php echo $lang->get('children_privacy_policy'); ?></p>
                    
                    <div class="age-verification">
                        <div class="verification-icon">
                            <i class="icon-calendar-check"></i>
                        </div>
                        <div class="verification-content">
                            <h4><?php echo $lang->get('age_verification'); ?></h4>
                            <p><?php echo $lang->get('age_verification_desc'); ?></p>
                        </div>
                    </div>
                </section>

                <section id="updates" class="privacy-section">
                    <h2><?php echo $lang->get('policy_updates'); ?></h2>
                    <p><?php echo $lang->get('policy_updates_intro'); ?></p>
                    <p><?php echo $lang->get('policy_updates_notification'); ?></p>
                    
                    <div class="version-history">
                        <h3><?php echo $lang->get('version_history'); ?></h3>
                        <div class="version-item">
                            <div class="version-date">01/01/2025</div>
                            <div class="version-content">
                                <h4><?php echo $lang->get('version_current'); ?></h4>
                                <p><?php echo $lang->get('version_current_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="contact" class="privacy-section">
                    <h2><?php echo $lang->get('contact_info'); ?></h2>
                    <p><?php echo $lang->get('contact_privacy_intro'); ?></p>
                    
                    <div class="contact-info-grid">
                        <div class="contact-info-item">
                            <h4><?php echo $lang->get('data_controller'); ?></h4>
                            <p>
                                PrestaCapi SAS<br>
                                <?php echo $lang->get('company_address_full'); ?>
                            </p>
                        </div>
                        
                        <div class="contact-info-item">
                            <h4><?php echo $lang->get('privacy_officer'); ?></h4>
                            <p>
                                Email: <a href="mailto:privacy@prestacapi.com">privacy@prestacapi.com</a><br>
                                Téléphone: <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                            </p>
                        </div>
                        
                        <div class="contact-info-item">
                            <h4><?php echo $lang->get('supervisory_authority'); ?></h4>
                            <p>
                                CNIL (Commission Nationale de l'Informatique et des Libertés)<br>
                                <a href="https://www.cnil.fr" target="_blank" rel="noopener">www.cnil.fr</a>
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.privacy-nav a');
    const sections = document.querySelectorAll('.privacy-section');
    
    function highlightNavigation() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 100;
            const sectionHeight = section.offsetHeight;
            if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    window.addEventListener('scroll', highlightNavigation);
    highlightNavigation();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.purpose-item, .right-item, .measure-item, .cookie-type').forEach(el => {
        observer.observe(el);
    });
});

function openCookieSettings() {
    if (typeof window.cookieConsent !== 'undefined') {
        window.cookieConsent.openSettings();
    } else {
        alert('<?php echo $lang->get("cookie_settings_unavailable"); ?>');
    }
}
</script>

<style>
.privacy-page {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #E5E7EB;
}

.page-header h1 {
    font-size: 2.5rem;
    color: #1F3B73;
    margin-bottom: 1rem;
}

.last-updated {
    color: #6B7280;
    font-style: italic;
}

.privacy-content {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 3rem;
    align-items: start;
}

.privacy-nav {
    position: sticky;
    top: 100px;
    background: #F5F7FA;
    padding: 1.5rem;
    border-radius: 8px;
}

.privacy-nav h3 {
    margin-bottom: 1rem;
    color: #1F3B73;
}

.nav-list {
    list-style: none;
    padding: 0;
}

.nav-list li {
    margin-bottom: 0.5rem;
}

.nav-list a {
    display: block;
    padding: 0.5rem;
    color: #4B5563;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.nav-list a:hover,
.nav-list a.active {
    background: #00B8D9;
    color: white;
}

.privacy-section {
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #E5E7EB;
}

.privacy-section:last-child {
    border-bottom: none;
}

.privacy-section h2 {
    color: #1F3B73;
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.privacy-section h3 {
    color: #1F3B73;
    margin: 2rem 0 1rem 0;
}

.info-box,
.warning-box {
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1.5rem 0;
    display: flex;
    gap: 1rem;
}

.info-box {
    background: #EBF8FF;
    border: 1px solid #00B8D9;
}

.warning-box {
    background: #FEF3C7;
    border: 1px solid #F59E0B;
}

.info-icon,
.warning-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    color: #00B8D9;
}

.warning-icon {
    color: #F59E0B;
}

.data-list {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.data-list li {
    margin-bottom: 0.75rem;
}

.collection-methods,
.usage-purposes,
.security-measures,
.rights-grid {
    display: grid;
    gap: 1.5rem;
    margin: 2rem 0;
}

.usage-purposes,
.security-measures,
.rights-grid {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.method-item,
.purpose-item,
.measure-item,
.right-item {
    padding: 1.5rem;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    display: flex;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.method-item:hover,
.purpose-item:hover,
.measure-item:hover,
.right-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.purpose-icon,
.measure-icon,
.right-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    background: #00B8D9;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.legal-basis-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
}

.legal-basis-table th,
.legal-basis-table td {
    padding: 1rem;
    text-align: left;
    border: 1px solid #E5E7EB;
}

.legal-basis-table th {
    background: #F5F7FA;
    font-weight: bold;
    color: #1F3B73;
}

.sharing-categories .category-item {
    margin-bottom: 1.5rem;
    padding: 1rem;
    border-left: 4px solid #00B8D9;
    background: #F8FAFC;
}

.cookies-types .cookie-type {
    padding: 1.5rem;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    margin-bottom: 1rem;
    position: relative;
}

.cookie-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}

.cookie-status.required {
    background: #FEE2E2;
    color: #DC2626;
}

.cookie-status.optional {
    background: #DBEAFE;
    color: #2563EB;
}

.contact-methods,
.contact-info-grid {
    display: grid;
    gap: 1rem;
    margin: 1.5rem 0;
}

.contact-methods {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.contact-info-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.contact-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #00B8D9;
    border-radius: 8px;
    text-decoration: none;
    color: #00B8D9;
    transition: all 0.3s ease;
}

.contact-btn:hover {
    background: #00B8D9;
    color: white;
}

.contact-info-item {
    padding: 1.5rem;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
}

.contact-info-item h4 {
    color: #1F3B73;
    margin-bottom: 1rem;
}

.version-history .version-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-left: 4px solid #00B8D9;
    background: #F8FAFC;
    margin-bottom: 1rem;
}

.version-date {
    flex-shrink: 0;
    font-weight: bold;
    color: #1F3B73;
}

.age-verification {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: #FEF3C7;
    border: 1px solid #F59E0B;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.verification-icon {
    flex-shrink: 0;
    color: #F59E0B;
}

@media (max-width: 768px) {
    .privacy-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .privacy-nav {
        position: static;
        order: -1;
    }
    
    .usage-purposes,
    .security-measures,
    .rights-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-methods,
    .contact-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>