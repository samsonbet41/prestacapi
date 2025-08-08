<?php
if (!isset($lang)) {
    require_once __DIR__ . '/../classes/Language.php';
    $lang = Language::getInstance();
}

if (!isset($user)) {
    require_once __DIR__ . '/../classes/User.php';
    $user = new User();
}
?>

<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section brand-section">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <img src="/images/logo.png" alt="PrestaCapi" class="footer-logo-image">
                            <span class="footer-logo-text">PrestaCapi</span>
                        </div>
                        <p class="footer-tagline"><?php echo $lang->get('tagline'); ?></p>
                    </div>
                    
                    <div class="footer-description">
                        <p><?php echo $lang->get('about_description'); ?></p>
                    </div>
                    
                    <div class="footer-trust">
                        <div class="trust-badges">
                            <div class="trust-badge">
                                <span class="badge-icon">🔒</span>
                                <div class="badge-content">
                                    <strong>SSL 256-bit</strong>
                                    <small>Sécurité maximale</small>
                                </div>
                            </div>
                            
                            <div class="trust-badge">
                                <span class="badge-icon">🛡️</span>
                                <div class="badge-content">
                                    <strong>RGPD</strong>
                                    <small>Conforme</small>
                                </div>
                            </div>
                            
                            <div class="trust-badge">
                                <span class="badge-icon">🏦</span>
                                <div class="badge-content">
                                    <strong>Agréé</strong>
                                    <small>ACPR</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-stats">
                        <div class="stat-item">
                            <div class="stat-number">10,000+</div>
                            <div class="stat-label">Clients satisfaits</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">€25M</div>
                            <div class="stat-label">Prêts accordés</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">16</div>
                            <div class="stat-label">Années d'expérience</div>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section links-section">
                    <h3 class="footer-section-title"><?php echo $lang->get('footer_services'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo $lang->pageUrl('loan_request'); ?>">Demande de prêt</a></li>
                        <li><a href="<?php echo $lang->pageUrl('dashboard'); ?>">Tableau de bord</a></li>
                        <li><a href="<?php echo $lang->pageUrl('withdrawal'); ?>">Retrait de fonds</a></li>
                        <li><a href="<?php echo $lang->pageUrl('profile'); ?>">Mon profil</a></li>
                        <li><a href="<?php echo $lang->pageUrl('partners'); ?>">Nos partenaires</a></li>
                        <li><a href="/simulateur">Simulateur de prêt</a></li>
                    </ul>
                </div>
                
                <div class="footer-section links-section">
                    <h3 class="footer-section-title"><?php echo $lang->get('footer_about_us'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo $lang->pageUrl('about'); ?>">À propos de nous</a></li>
                        <li><a href="<?php echo $lang->pageUrl('blog'); ?>">Blog & Actualités</a></li>
                        <li><a href="<?php echo $lang->pageUrl('testimonials'); ?>">Témoignages</a></li>
                        <li><a href="/equipe">Notre équipe</a></li>
                        <li><a href="/presse">Espace presse</a></li>
                        <li><a href="/carriere">Nous rejoindre</a></li>
                    </ul>
                </div>
                
                <div class="footer-section links-section">
                    <h3 class="footer-section-title"><?php echo $lang->get('footer_support'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo $lang->pageUrl('contact'); ?>">Nous contacter</a></li>
                        <li><a href="/faq">Questions fréquentes</a></li>
                        <li><a href="/aide">Centre d'aide</a></li>
                        <li><a href="/guide">Guide d'utilisation</a></li>
                        <li><a href="/statut">Statut du service</a></li>
                        <li><a href="/feedback">Vos suggestions</a></li>
                    </ul>
                </div>
                
                <div class="footer-section newsletter-section">
                    <h3 class="footer-section-title"><?php echo $lang->get('footer_newsletter'); ?></h3>
                    <p class="newsletter-description"><?php echo $lang->get('footer_newsletter_text'); ?></p>
                    
                    <form class="newsletter-form" onsubmit="handleNewsletterSubmit(event)">
                        <div class="newsletter-input-group">
                            <input type="email" 
                                   name="email" 
                                   class="newsletter-input" 
                                   placeholder="<?php echo $lang->get('footer_newsletter_placeholder'); ?>"
                                   required>
                            <button type="submit" class="newsletter-btn">
                                <span class="btn-text"><?php echo $lang->get('footer_newsletter_btn'); ?></span>
                                <span class="btn-loader"></span>
                            </button>
                        </div>
                        <div class="newsletter-message" id="newsletterMessage"></div>
                    </form>
                    
                    <div class="footer-social">
                        <h4 class="social-title"><?php echo $lang->get('footer_follow_us'); ?></h4>
                        <div class="social-links">
                            <a href="https://facebook.com/prestacapi" class="social-link facebook" target="_blank" rel="noopener" aria-label="Facebook">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            
                            <a href="https://twitter.com/prestacapi" class="social-link twitter" target="_blank" rel="noopener" aria-label="Twitter">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            
                            <a href="https://linkedin.com/company/prestacapi" class="social-link linkedin" target="_blank" rel="noopener" aria-label="LinkedIn">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            
                            <a href="https://instagram.com/prestacapi" class="social-link instagram" target="_blank" rel="noopener" aria-label="Instagram">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            
                            <a href="https://youtube.com/prestacapi" class="social-link youtube" target="_blank" rel="noopener" aria-label="YouTube">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <div class="footer-contact-info">
                        <h4 class="contact-title">Contact rapide</h4>
                        <div class="contact-details">
                            <div class="contact-item">
                                <span class="contact-icon">📞</span>
                                <a href="tel:+33123456789">+33 1 23 45 67 89</a>
                            </div>
                            <div class="contact-item">
                                <span class="contact-icon">📧</span>
                                <a href="mailto:contact@prestacapi.com">contact@prestacapi.com</a>
                            </div>
                            <div class="contact-item">
                                <span class="contact-icon">💬</span>
                                <a href="https://wa.me/33123456789">WhatsApp</a>
                            </div>
                            <div class="contact-item">
                                <span class="contact-icon">🕒</span>
                                <span>Lun-Ven 9h-18h, Sam 9h-12h</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-legal">
        <div class="footer-container">
            <div class="legal-content">
                <div class="legal-links">
                    <h4 class="legal-title"><?php echo $lang->get('footer_legal'); ?></h4>
                    <div class="legal-links-grid">
                        <a href="<?php echo $lang->pageUrl('terms'); ?>">Conditions générales</a>
                        <a href="<?php echo $lang->pageUrl('privacy'); ?>">Politique de confidentialité</a>
                        <a href="/mentions-legales">Mentions légales</a>
                        <a href="/cgv">Conditions de vente</a>
                        <a href="/cookies">Politique des cookies</a>
                        <a href="/rgpd">Protection des données</a>
                    </div>
                </div>
                
                <div class="regulatory-info">
                    <h4 class="regulatory-title">Informations réglementaires</h4>
                    <div class="regulatory-text">
                        <p>PrestaCapi SAS - Capital social : 100 000€</p>
                        <p>SIRET : 12345678901234 - RCS Paris 123 456 789</p>
                        <p>Intermédiaire en opérations de banque et services de paiement</p>
                        <p>N° ORIAS : 12345678 - www.orias.fr</p>
                        <p>Contrôlé par l'ACPR - 4 Place de Budapest, 75009 Paris</p>
                    </div>
                </div>
                
                <div class="disclaimers">
                    <div class="disclaimer-item">
                        <h5>Avertissement sur les risques</h5>
                        <p>L'emprunt d'argent coûte de l'argent et doit être remboursé. Vérifiez vos capacités de remboursement avant de vous engager.</p>
                    </div>
                    
                    <div class="disclaimer-item">
                        <h5>Médiation</h5>
                        <p>En cas de litige, vous pouvez saisir le médiateur de l'AMF : mediateur@amf-france.org</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="footer-container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> PrestaCapi. <?php echo $lang->get('footer_copyright'); ?></p>
                    <p class="founding-year">Votre partenaire financier depuis 2008</p>
                </div>
                
                <div class="footer-language">
                    <?php echo $lang->generateLanguageSelector(); ?>
                </div>
                
                <div class="back-to-top">
                    <button onclick="scrollToTop()" class="back-to-top-btn" aria-label="Retour en haut">
                        <span>↑</span>
                        Haut de page
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background: var(--primary-color);
    color: white;
    margin-top: auto;
}

.footer-main {
    padding: 4rem 0 2rem;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

.footer-section-title {
    color: var(--accent-1);
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
}

.footer-section-title::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 30px;
    height: 2px;
    background: var(--accent-1);
    border-radius: 1px;
}

.brand-section .footer-brand {
    margin-bottom: 1.5rem;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.footer-logo-image {
    height: 40px;
    width: auto;
}

.footer-logo-text {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.footer-tagline {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

.footer-description {
    margin-bottom: 2rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.6;
}

.footer-trust {
    margin-bottom: 2rem;
}

.trust-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.trust-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.75rem;
    border-radius: var(--border-radius);
    backdrop-filter: blur(10px);
    min-width: 120px;
}

.badge-icon {
    font-size: 1.25rem;
}

.badge-content strong {
    display: block;
    font-size: 0.875rem;
    color: var(--accent-1);
}

.badge-content small {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
}

.footer-stats {
    display: flex;
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent-1);
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 0.25rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--accent-1);
}

.newsletter-section {
    background: rgba(255, 255, 255, 0.05);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    backdrop-filter: blur(10px);
}

.newsletter-description {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.newsletter-form {
    margin-bottom: 2rem;
}

.newsletter-input-group {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.newsletter-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--border-radius);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    font-size: 0.875rem;
}

.newsletter-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--accent-1);
    background: rgba(255, 255, 255, 0.15);
}

.newsletter-btn {
    padding: 0.75rem 1.5rem;
    background: var(--accent-1);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.newsletter-btn:hover {
    background: #0099B8;
    transform: translateY(-2px);
}

.newsletter-btn.loading .btn-text {
    opacity: 0;
}

.newsletter-btn .btn-loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    display: none;
}

.newsletter-btn.loading .btn-loader {
    display: block;
}

.newsletter-message {
    font-size: 0.875rem;
    min-height: 20px;
    margin-top: 0.5rem;
}

.newsletter-message.success {
    color: var(--success-color);
}

.newsletter-message.error {
    color: var(--error-color);
}

.footer-social {
    margin-bottom: 2rem;
}

.social-title {
    color: var(--accent-1);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.social-links {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.social-link {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255, 255, 255, 0.8);
    transition: all 0.3s ease;
    text-decoration: none;
}

.social-link:hover {
    background: var(--accent-1);
    color: white;
    transform: translateY(-2px);
}

.social-link.facebook:hover { background: #1877F2; }
.social-link.twitter:hover { background: #1DA1F2; }
.social-link.linkedin:hover { background: #0A66C2; }
.social-link.instagram:hover { background: #E4405F; }
.social-link.youtube:hover { background: #FF0000; }

.footer-contact-info {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 2rem;
}

.contact-title {
    color: var(--accent-1);
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.contact-icon {
    font-size: 1rem;
}

.contact-item a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: color 0.3s ease;
}

.contact-item a:hover {
    color: var(--accent-1);
}

.footer-legal {
    background: rgba(0, 0, 0, 0.3);
    padding: 2rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.legal-content {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 2rem;
}

.legal-title,
.regulatory-title {
    color: var(--accent-1);
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.legal-links-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

.legal-links-grid a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    font-size: 0.75rem;
    transition: color 0.3s ease;
}

.legal-links-grid a:hover {
    color: var(--accent-1);
}

.regulatory-text {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.4;
}

.regulatory-text p {
    margin-bottom: 0.5rem;
}

.disclaimers {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.disclaimer-item h5 {
    color: var(--accent-1);
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.disclaimer-item p {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.3;
    margin: 0;
}

.footer-bottom {
    background: rgba(0, 0, 0, 0.5);
    padding: 1.5rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.copyright {
    flex: 1;
}

.copyright p {
    margin: 0;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
}

.founding-year {
    font-size: 0.75rem !important;
    color: rgba(255, 255, 255, 0.6) !important;
    margin-top: 0.25rem !important;
}

.footer-language .language-selector {
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--border-radius);
    padding: 0.5rem;
}

.back-to-top-btn {
    background: var(--accent-1);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    padding: 0.75rem 1rem;
    cursor: pointer;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.back-to-top-btn:hover {
    background: #0099B8;
    transform: translateY(-2px);
}

@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr 1fr;
        gap: 2rem;
    }
    
    .brand-section {
        grid-column: 1 / -1;
    }
    
    .newsletter-section {
        grid-column: 1 / -1;
        margin-top: 2rem;
    }
    
    .footer-stats {
        justify-content: space-around;
    }
    
    .trust-badges {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .footer-main {
        padding: 3rem 0 1.5rem;
    }
    
    .newsletter-input-group {
        flex-direction: column;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .legal-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .legal-links-grid {
        grid-template-columns: 1fr;
    }
    
    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .footer-stats {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .footer-container {
        padding: 0 0.75rem;
    }
    
    .trust-badges {
        flex-direction: column;
    }
    
    .trust-badge {
        min-width: auto;
    }
}
</style>

<script>
function handleNewsletterSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const btn = form.querySelector('.newsletter-btn');
    const message = document.getElementById('newsletterMessage');
    
    btn.classList.add('loading');
    message.textContent = '';
    
    const formData = new FormData(form);
    
    fetch('/ajax/newsletter-subscribe.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.classList.remove('loading');
        message.textContent = data.message;
        message.className = `newsletter-message ${data.success ? 'success' : 'error'}`;
        
        if (data.success) {
            form.reset();
        }
    })
    .catch(error => {
        btn.classList.remove('loading');
        message.textContent = 'Une erreur est survenue. Veuillez réessayer.';
        message.className = 'newsletter-message error';
    });
}

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

window.addEventListener('scroll', function() {
    const backToTopBtn = document.querySelector('.back-to-top-btn');
    if (window.scrollY > 500) {
        backToTopBtn.style.opacity = '1';
    } else {
        backToTopBtn.style.opacity = '0.7';
    }
});
</script>