<?php
$pageKey = 'about';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<?php
require_once 'includes/header.php';



$stats = [];
try {
    $stats = $db->getUserStats();
} catch (Exception $e) {
    error_log("Erreur chargement statistiques: " . $e->getMessage());
}
?>

<main class="about-page">
    <section class="about-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang->get('about_title'); ?></h1>
                <p class="hero-lead"><?php echo $lang->get('about_hero_description'); ?></p>
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-number">10 000+</div>
                        <div class="stat-label"><?php echo $lang->get('clients_satisfied'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">17</div>
                        <div class="stat-label"><?php echo $lang->get('years_experience'); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">50M+</div>
                        <div class="stat-label"><?php echo $lang->get('euros_financed'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-story">
        <div class="container">
            <div class="story-grid">
                <div class="story-content">
                    <h2><?php echo $lang->get('our_story_title'); ?></h2>
                    <p><?php echo $lang->get('our_story_p1'); ?></p>
                    <p><?php echo $lang->get('our_story_p2'); ?></p>
                    <p><?php echo $lang->get('our_story_p3'); ?></p>
                    <div class="story-highlights">
                        <div class="highlight-item">
                            <i class="icon-award"></i>
                            <div>
                                <h4><?php echo $lang->get('highlight_expertise'); ?></h4>
                                <p><?php echo $lang->get('highlight_expertise_desc'); ?></p>
                            </div>
                        </div>
                        <div class="highlight-item">
                            <i class="icon-handshake"></i>
                            <div>
                                <h4><?php echo $lang->get('highlight_trust'); ?></h4>
                                <p><?php echo $lang->get('highlight_trust_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="story-visual">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-year">2008</div>
                            <div class="timeline-content">
                                <h4><?php echo $lang->get('timeline_2008_title'); ?></h4>
                                <p><?php echo $lang->get('timeline_2008_desc'); ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2012</div>
                            <div class="timeline-content">
                                <h4><?php echo $lang->get('timeline_2012_title'); ?></h4>
                                <p><?php echo $lang->get('timeline_2012_desc'); ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2018</div>
                            <div class="timeline-content">
                                <h4><?php echo $lang->get('timeline_2018_title'); ?></h4>
                                <p><?php echo $lang->get('timeline_2018_desc'); ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-year">2025</div>
                            <div class="timeline-content">
                                <h4><?php echo $lang->get('timeline_2025_title'); ?></h4>
                                <p><?php echo $lang->get('timeline_2025_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-values">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('our_values_title'); ?></h2>
                <p><?php echo $lang->get('our_values_subtitle'); ?></p>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fa-solid fa-scale-balanced"></i> </div>
                    <h3><?php echo $lang->get('value_transparency_title'); ?></h3>
                    <p><?php echo $lang->get('value_transparency_desc'); ?></p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fa-solid fa-lightbulb"></i> </div>
                    <h3><?php echo $lang->get('value_innovation_title'); ?></h3>
                    <p><?php echo $lang->get('value_innovation_desc'); ?></p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fa-solid fa-handshake-angle"></i> </div>
                    <h3><?php echo $lang->get('value_support_title'); ?></h3>
                    <p><?php echo $lang->get('value_support_desc'); ?></p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fa-solid fa-lock"></i> </div>
                    <h3><?php echo $lang->get('value_security_title'); ?></h3>
                    <p><?php echo $lang->get('value_security_desc'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-team">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('our_team_title'); ?></h2>
                <p><?php echo $lang->get('our_team_subtitle'); ?></p>
            </div>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/equipes/ceo.jpg" alt="Photo de <?php echo $lang->get('team_ceo_name'); ?>">
                    </div>
                    <div class="member-info">
                        <h3><?php echo $lang->get('team_ceo_name'); ?></h3>
                        <p class="member-role"><?php echo $lang->get('team_ceo_role'); ?></p>
                        <p class="member-bio"><?php echo $lang->get('team_ceo_bio'); ?></p>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/equipes/cfo.jpg" alt="Photo de <?php echo $lang->get('team_cfo_name'); ?>">
                    </div>
                    <div class="member-info">
                        <h3><?php echo $lang->get('team_cfo_name'); ?></h3>
                        <p class="member-role"><?php echo $lang->get('team_cfo_role'); ?></p>
                        <p class="member-bio"><?php echo $lang->get('team_cfo_bio'); ?></p>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-photo">
                        <img src="/images/equipes/cto.jpg" alt="Photo de <?php echo $lang->get('team_cto_name'); ?>">
                    </div>
                    <div class="member-info">
                        <h3><?php echo $lang->get('team_cto_name'); ?></h3>
                        <p class="member-role"><?php echo $lang->get('team_cto_role'); ?></p>
                        <p class="member-bio"><?php echo $lang->get('team_cto_bio'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-mission">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text">
                    <h2><?php echo $lang->get('our_mission_title'); ?></h2>
                    <p class="mission-statement"><?php echo $lang->get('our_mission_statement'); ?></p>
                    <div class="mission-points">
                        <div class="mission-point">
                            <i class="icon-target"></i>
                            <div>
                                <h4><?php echo $lang->get('mission_accessibility_title'); ?></h4>
                                <p><?php echo $lang->get('mission_accessibility_desc'); ?></p>
                            </div>
                        </div>
                        <div class="mission-point">
                            <i class="icon-speed"></i>
                            <div>
                                <h4><?php echo $lang->get('mission_speed_title'); ?></h4>
                                <p><?php echo $lang->get('mission_speed_desc'); ?></p>
                            </div>
                        </div>
                        <div class="mission-point">
                            <i class="icon-partnership"></i>
                            <div>
                                <h4><?php echo $lang->get('mission_partnership_title'); ?></h4>
                                <p><?php echo $lang->get('mission_partnership_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mission-visual">
                    <div class="mission-chart">
                        <div class="chart-container">
                            <canvas id="successChart" width="300" height="300"></canvas>
                        </div>
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background: #4CAF50;"></div>
                                <span><?php echo $lang->get('approved_loans'); ?> (85%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #FF9800;"></div>
                                <span><?php echo $lang->get('under_review'); ?> (10%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background: #E53935;"></div>
                                <span><?php echo $lang->get('declined'); ?> (5%)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="about-certifications">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('certifications_title'); ?></h2>
                <p><?php echo $lang->get('certifications_subtitle'); ?></p>
            </div>
            <div class="certifications-grid">
                <div class="certification-item">
                    <div class="cert-icon">
                        <i class="icon-shield-check"></i>
                    </div>
                    <h4><?php echo $lang->get('cert_data_protection'); ?></h4>
                    <p><?php echo $lang->get('cert_data_protection_desc'); ?></p>
                </div>
                <div class="certification-item">
                    <div class="cert-icon">
                        <i class="icon-bank"></i>
                    </div>
                    <h4><?php echo $lang->get('cert_financial_authority'); ?></h4>
                    <p><?php echo $lang->get('cert_financial_authority_desc'); ?></p>
                </div>
                <div class="certification-item">
                    <div class="cert-icon">
                        <i class="icon-certificate"></i>
                    </div>
                    <h4><?php echo $lang->get('cert_iso_quality'); ?></h4>
                    <p><?php echo $lang->get('cert_iso_quality_desc'); ?></p>
                </div>
                <div class="certification-item">
                    <div class="cert-icon">
                        <i class="icon-lock"></i>
                    </div>
                    <h4><?php echo $lang->get('cert_ssl_security'); ?></h4>
                    <p><?php echo $lang->get('cert_ssl_security_desc'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="about-contact">
        <div class="container">
            <div class="contact-wrapper">
                <div class="contact-info">
                    <h2><?php echo $lang->get('contact_us_title'); ?></h2>
                    <p><?php echo $lang->get('contact_us_subtitle'); ?></p>
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="icon-phone"></i>
                            <div>
                                <h4><?php echo $lang->get('phone_support'); ?></h4>
                                <p>+33 7 45 50 52 07</p>
                                <span><?php echo $lang->get('phone_hours'); ?></span>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="icon-mail"></i>
                            <div>
                                <h4><?php echo $lang->get('email_support'); ?></h4>
                                <p>support@prestacapi.com</p>
                                <span><?php echo $lang->get('email_response_time'); ?></span>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="icon-whatsapp"></i>
                            <div>
                                <h4><?php echo $lang->get('whatsapp_support'); ?></h4>
                                <p>+33 7 45 50 52 07</p>
                                <span><?php echo $lang->get('whatsapp_hours'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="contact-cta">
                    <h3><?php echo $lang->get('ready_to_start'); ?></h3>
                    <p><?php echo $lang->get('ready_to_start_desc'); ?></p>
                    <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-lg">
                        <?php echo $lang->get('start_application'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('contact'); ?>" class="btn btn-outline btn-lg">
                        <?php echo $lang->get('contact_advisor'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('successChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 100;

        const data = [
            { label: 'Approved', value: 85, color: '#4CAF50' },
            { label: 'Under Review', value: 10, color: '#FF9800' },
            { label: 'Declined', value: 5, color: '#E53935' }
        ];

        let currentAngle = -Math.PI / 2;

        data.forEach(segment => {
            const sliceAngle = (segment.value / 100) * 2 * Math.PI;

            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
            ctx.closePath();
            ctx.fillStyle = segment.color;
            ctx.fill();

            currentAngle += sliceAngle;
        });

        ctx.beginPath();
        ctx.arc(centerX, centerY, radius * 0.6, 0, 2 * Math.PI);
        ctx.fillStyle = '#fff';
        ctx.fill();

        ctx.fillStyle = '#1F3B73';
        ctx.font = 'bold 24px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('85%', centerX, centerY - 5);
        ctx.font = '14px Arial';
        ctx.fillText('Success Rate', centerX, centerY + 15);
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.timeline-item, .value-card, .team-member').forEach(el => {
        observer.observe(el);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>