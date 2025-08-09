<?php
require_once 'includes/header.php';

$seo->generateTitle($lang->get('partners_page_title'));
$seo->generateDescription($lang->get('partners_page_description'));

$partnerClass = new Partner();
$filterType = isset($_GET['type']) ? $_GET['type'] : '';

$partners = [];
$partnerStats = [];

try {
    if (!empty($filterType)) {
        $partners = $partnerClass->getPartnersByType($filterType);
    } else {
        $partners = $partnerClass->getAllPartners(true);
    }
    
    $partnerStats = $partnerClass->getPartnerStats();
    
} catch (Exception $e) {
    error_log("Erreur chargement partenaires: " . $e->getMessage());
    $partners = [];
    $partnerStats = [];
}

$partnerTypes = [
    'bank' => $lang->get('banks'),
    'microfinance' => $lang->get('microfinance'),
    'lender' => $lang->get('private_lenders'),
    'other' => $lang->get('other_partners')
];
?>

<main class="partners-page">
    <section class="partners-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang->get('our_partners'); ?></h1>
                <p class="hero-subtitle"><?php echo $lang->get('partners_description'); ?></p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo isset($partnerStats['active']) ? $partnerStats['active'] : '25+'; ?></div>
                        <div class="stat-label"><?php echo $lang->get('active_partners'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">17</div>
                        <div class="stat-label"><?php echo $lang->get('years_collaboration'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">95%</div>
                        <div class="stat-label"><?php echo $lang->get('success_rate'); ?></div>
                    </div>
                </div>
                
                <div class="hero-badges">
                    <div class="badge-item">
                        <i class="icon-shield-check"></i>
                        <span><?php echo $lang->get('trusted_institutions'); ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="icon-handshake"></i>
                        <span><?php echo $lang->get('long_term_partnerships'); ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="icon-globe"></i>
                        <span><?php echo $lang->get('international_network'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-intro">
        <div class="container">
            <div class="intro-content">
                <div class="intro-text">
                    <h2><?php echo $lang->get('why_partners'); ?></h2>
                    <p><?php echo $lang->get('partners_intro_p1'); ?></p>
                    <p><?php echo $lang->get('partners_intro_p2'); ?></p>
                    
                    <div class="intro-highlights">
                        <div class="highlight-item">
                            <i class="icon-check-circle"></i>
                            <span><?php echo $lang->get('highlight_selection'); ?></span>
                        </div>
                        <div class="highlight-item">
                            <i class="icon-check-circle"></i>
                            <span><?php echo $lang->get('highlight_best_rates'); ?></span>
                        </div>
                        <div class="highlight-item">
                            <i class="icon-check-circle"></i>
                            <span><?php echo $lang->get('highlight_security'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="intro-visual">
                    <div class="partnership-diagram">
                        <div class="center-node">
                            <div class="node-content">
                                <img src="/images/logo-mini.png" alt="PrestaCapi" class="prestacapi-logo">
                                <span>PrestaCapi</span>
                            </div>
                        </div>
                        <div class="partner-nodes">
                            <div class="partner-node bank">
                                <i class="icon-bank"></i>
                                <span><?php echo $lang->get('banks'); ?></span>
                            </div>
                            <div class="partner-node microfinance">
                                <i class="icon-building"></i>
                                <span><?php echo $lang->get('microfinance'); ?></span>
                            </div>
                            <div class="partner-node lender">
                                <i class="icon-users"></i>
                                <span><?php echo $lang->get('lenders'); ?></span>
                            </div>
                            <div class="partner-node other">
                                <i class="icon-star"></i>
                                <span><?php echo $lang->get('others'); ?></span>
                            </div>
                        </div>
                        <div class="connection-lines">
                            <div class="line line-1"></div>
                            <div class="line line-2"></div>
                            <div class="line line-3"></div>
                            <div class="line line-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-main">
        <div class="container">
            <div class="partners-header">
                <div class="header-left">
                    <h2><?php echo $lang->get('our_partner_network'); ?></h2>
                    <p><?php echo count($partners); ?> <?php echo $lang->get('trusted_partners'); ?></p>
                </div>
                <div class="header-right">
                    <div class="partners-filter">
                        <label><?php echo $lang->get('filter_by_type'); ?>:</label>
                        <select id="typeFilter" onchange="filterPartners()">
                            <option value="" <?php echo empty($filterType) ? 'selected' : ''; ?>><?php echo $lang->get('all_types'); ?></option>
                            <?php foreach ($partnerTypes as $type => $label): ?>
                                <option value="<?php echo $type; ?>" <?php echo $filterType === $type ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if (isset($partnerStats['by_type'])): ?>
            <div class="partners-overview">
                <div class="overview-grid">
                    <?php foreach ($partnerTypes as $type => $label): ?>
                        <?php $count = $partnerStats['by_type'][$type] ?? 0; ?>
                        <div class="overview-item">
                            <div class="overview-icon">
                                <?php
                                switch ($type) {
                                    case 'bank':
                                        echo '<i class="icon-bank"></i>';
                                        break;
                                    case 'microfinance':
                                        echo '<i class="icon-building"></i>';
                                        break;
                                    case 'lender':
                                        echo '<i class="icon-users"></i>';
                                        break;
                                    default:
                                        echo '<i class="icon-star"></i>';
                                }
                                ?>
                            </div>
                            <div class="overview-content">
                                <div class="overview-number"><?php echo $count; ?></div>
                                <div class="overview-label"><?php echo $label; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($partners)): ?>
            <div class="partners-grid">
                <?php foreach ($partners as $partner): ?>
                <div class="partner-card" data-type="<?php echo $partner['type']; ?>">
                    <div class="partner-header">
                        <?php if (!empty($partner['logo'])): ?>
                            <div class="partner-logo">
                                <img src="<?php echo htmlspecialchars($partner['logo']); ?>" 
                                     alt="<?php echo htmlspecialchars($partner['name']); ?>" 
                                     loading="lazy">
                            </div>
                        <?php else: ?>
                            <div class="partner-logo-placeholder">
                                <i class="icon-building"></i>
                            </div>
                        <?php endif; ?>
                        <div class="partner-type-badge">
                            <span class="type-<?php echo $partner['type']; ?>">
                                <?php echo $partnerTypes[$partner['type']] ?? $partner['type']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="partner-content">
                        <h3 class="partner-name"><?php echo htmlspecialchars($partner['name']); ?></h3>
                        
                        <?php if (!empty($partner['description'])): ?>
                            <p class="partner-description"><?php echo htmlspecialchars($partner['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="partner-features">
                            <?php
                            switch ($partner['type']) {
                                case 'bank':
                                    echo '<div class="feature-item"><i class="icon-shield"></i><span>' . $lang->get('guaranteed_security') . '</span></div>';
                                    echo '<div class="feature-item"><i class="icon-percent"></i><span>' . $lang->get('competitive_rates') . '</span></div>';
                                    break;
                                case 'microfinance':
                                    echo '<div class="feature-item"><i class="icon-users"></i><span>' . $lang->get('personalized_support') . '</span></div>';
                                    echo '<div class="feature-item"><i class="icon-clock"></i><span>' . $lang->get('quick_decisions') . '</span></div>';
                                    break;
                                case 'lender':
                                    echo '<div class="feature-item"><i class="icon-flash"></i><span>' . $lang->get('fast_approval') . '</span></div>';
                                    echo '<div class="feature-item"><i class="icon-heart"></i><span>' . $lang->get('flexible_terms') . '</span></div>';
                                    break;
                                default:
                                    echo '<div class="feature-item"><i class="icon-star"></i><span>' . $lang->get('specialized_solutions') . '</span></div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="partner-footer">
                        <?php if (!empty($partner['website'])): ?>
                            <a href="<?php echo htmlspecialchars($partner['website']); ?>" 
                               target="_blank" rel="noopener" class="partner-website">
                                <i class="icon-external-link"></i>
                                <?php echo $lang->get('visit_website'); ?>
                            </a>
                        <?php endif; ?>
                        
                        <div class="partner-contact">
                            <?php if (!empty($partner['contact_email'])): ?>
                                <a href="mailto:<?php echo htmlspecialchars($partner['contact_email']); ?>" 
                                   class="contact-link">
                                    <i class="icon-mail"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($partner['contact_phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($partner['contact_phone']); ?>" 
                                   class="contact-link">
                                    <i class="icon-phone"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php else: ?>
            <div class="no-partners">
                <div class="no-partners-icon">
                    <i class="icon-handshake"></i>
                </div>
                <h3><?php echo $lang->get('no_partners_found'); ?></h3>
                <p>
                    <?php if (!empty($filterType)): ?>
                        <?php echo $lang->get('no_partners_type'); ?>
                    <?php else: ?>
                        <?php echo $lang->get('no_partners_available'); ?>
                    <?php endif; ?>
                </p>
                <?php if (!empty($filterType)): ?>
                <a href="<?php echo generateLocalizedUrl('partners'); ?>" class="btn btn-primary">
                    <?php echo $lang->get('view_all_partners'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="partnership-benefits">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('partnership_benefits'); ?></h2>
                <p><?php echo $lang->get('partnership_benefits_desc'); ?></p>
            </div>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-search"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_selection'); ?></h3>
                    <p><?php echo $lang->get('benefit_selection_desc'); ?></p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-percent"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_rates'); ?></h3>
                    <p><?php echo $lang->get('benefit_rates_desc'); ?></p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-clock"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_speed'); ?></h3>
                    <p><?php echo $lang->get('benefit_speed_desc'); ?></p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-users"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_support'); ?></h3>
                    <p><?php echo $lang->get('benefit_support_desc'); ?></p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-shield"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_security'); ?></h3>
                    <p><?php echo $lang->get('benefit_security_desc'); ?></p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="icon-chart-line"></i>
                    </div>
                    <h3><?php echo $lang->get('benefit_growth'); ?></h3>
                    <p><?php echo $lang->get('benefit_growth_desc'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('how_partnership_works'); ?></h2>
                <p><?php echo $lang->get('how_partnership_works_desc'); ?></p>
            </div>
            
            <div class="process-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="icon-wrapper">
                            <i class="icon-form"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h3><?php echo $lang->get('step_application'); ?></h3>
                        <p><?php echo $lang->get('step_application_desc'); ?></p>
                    </div>
                    <div class="timeline-number">1</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="icon-wrapper">
                            <i class="icon-analysis"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h3><?php echo $lang->get('step_analysis'); ?></h3>
                        <p><?php echo $lang->get('step_analysis_desc'); ?></p>
                    </div>
                    <div class="timeline-number">2</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="icon-wrapper">
                            <i class="icon-network"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h3><?php echo $lang->get('step_matching'); ?></h3>
                        <p><?php echo $lang->get('step_matching_desc'); ?></p>
                    </div>
                    <div class="timeline-number">3</div>
                </div>
                
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="icon-wrapper">
                            <i class="icon-handshake"></i>
                        </div>
                    </div>
                    <div class="timeline-content">
                        <h3><?php echo $lang->get('step_approval'); ?></h3>
                        <p><?php echo $lang->get('step_approval_desc'); ?></p>
                    </div>
                    <div class="timeline-number">4</div>
                </div>
            </div>
        </div>
    </section>

    <section class="become-partner">
        <div class="container">
            <div class="cta-content">
                <div class="cta-text">
                    <h2><?php echo $lang->get('become_partner'); ?></h2>
                    <p><?php echo $lang->get('become_partner_desc'); ?></p>
                    
                    <div class="partner-requirements">
                        <div class="requirement-item">
                            <i class="icon-check"></i>
                            <span><?php echo $lang->get('requirement_licensed'); ?></span>
                        </div>
                        <div class="requirement-item">
                            <i class="icon-check"></i>
                            <span><?php echo $lang->get('requirement_experience'); ?></span>
                        </div>
                        <div class="requirement-item">
                            <i class="icon-check"></i>
                            <span><?php echo $lang->get('requirement_capacity'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="cta-actions">
                    <a href="<?php echo generateLocalizedUrl('contact'); ?>?subject=partnership" class="btn btn-primary btn-lg">
                        <i class="icon-handshake"></i>
                        <?php echo $lang->get('contact_partnership'); ?>
                    </a>
                    <a href="mailto:partnerships@prestacapi.com" class="btn btn-outline btn-lg">
                        <i class="icon-mail"></i>
                        partnerships@prestacapi.com
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="partners-cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo $lang->get('ready_to_apply'); ?></h2>
                <p><?php echo $lang->get('partners_cta_desc'); ?></p>
                <div class="cta-actions">
                    <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-lg">
                        <?php echo $lang->get('apply_now'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('calculator'); ?>" class="btn btn-outline btn-lg">
                        <?php echo $lang->get('calculate_loan'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
function filterPartners() {
    const type = document.getElementById('typeFilter').value;
    
    let url = new URL(window.location);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const partnerCards = document.querySelectorAll('.partner-card');
    
    partnerCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.partner-card, .benefit-card, .timeline-item').forEach(el => {
        observer.observe(el);
    });

    const partnerNodes = document.querySelectorAll('.partner-node');
    partnerNodes.forEach((node, index) => {
        setTimeout(() => {
            node.classList.add('animate');
        }, index * 200);
    });

    const connectionLines = document.querySelectorAll('.connection-lines .line');
    connectionLines.forEach((line, index) => {
        setTimeout(() => {
            line.classList.add('animate');
        }, 800 + (index * 150));
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>