<?php
require_once 'includes/header.php';

$seo->generateTitle($lang->get('contact_page_title'));
$seo->generateDescription($lang->get('contact_page_description'));
?>

<main class="contact-page">
    <section class="contact-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang->get('contact_title'); ?></h1>
                <p class="hero-subtitle"><?php echo $lang->get('contact_subtitle'); ?></p>
                <div class="contact-highlights">
                    <div class="highlight-item">
                        <i class="icon-clock"></i>
                        <span><?php echo $lang->get('response_24h'); ?></span>
                    </div>
                    <div class="highlight-item">
                        <i class="icon-users"></i>
                        <span><?php echo $lang->get('expert_team'); ?></span>
                    </div>
                    <div class="highlight-item">
                        <i class="icon-phone"></i>
                        <span><?php echo $lang->get('multilingual_support'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-main">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-form-section">
                    <div class="form-header">
                        <h2><?php echo $lang->get('send_message'); ?></h2>
                        <p><?php echo $lang->get('send_message_desc'); ?></p>
                    </div>
                    
                    <form id="contactForm" class="contact-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName"><?php echo $lang->get('first_name'); ?> *</label>
                                <input type="text" id="firstName" name="first_name" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="lastName"><?php echo $lang->get('last_name'); ?> *</label>
                                <input type="text" id="lastName" name="last_name" required>
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email"><?php echo $lang->get('email'); ?> *</label>
                                <input type="email" id="email" name="email" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="phone"><?php echo $lang->get('phone'); ?></label>
                                <input type="tel" id="phone" name="phone">
                                <div class="error-message"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject"><?php echo $lang->get('subject'); ?> *</label>
                            <select id="subject" name="subject" required>
                                <option value=""><?php echo $lang->get('select_subject'); ?></option>
                                <option value="loan_info"><?php echo $lang->get('subject_loan_info'); ?></option>
                                <option value="loan_status"><?php echo $lang->get('subject_loan_status'); ?></option>
                                <option value="technical_issue"><?php echo $lang->get('subject_technical'); ?></option>
                                <option value="partnership"><?php echo $lang->get('subject_partnership'); ?></option>
                                <option value="complaint"><?php echo $lang->get('subject_complaint'); ?></option>
                                <option value="other"><?php echo $lang->get('subject_other'); ?></option>
                            </select>
                            <div class="error-message"></div>
                        </div>

                        <div class="form-group">
                            <label for="message"><?php echo $lang->get('message'); ?> *</label>
                            <textarea id="message" name="message" rows="6" required placeholder="<?php echo $lang->get('message_placeholder'); ?>"></textarea>
                            <div class="error-message"></div>
                            <div class="char-counter">
                                <span id="charCount">0</span>/1000
                            </div>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="newsletter" name="newsletter" value="1">
                                <span class="checkmark"></span>
                                <?php echo $lang->get('newsletter_consent'); ?>
                            </label>
                        </div>

                        <div class="form-group checkbox-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="privacy" name="privacy" required>
                                <span class="checkmark"></span>
                                <?php echo $lang->get('privacy_consent'); ?> 
                                <a href="<?php echo generateLocalizedUrl('privacy'); ?>" target="_blank"><?php echo $lang->get('privacy_policy'); ?></a> *
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full" id="submitBtn">
                            <span class="btn-text"><?php echo $lang->get('send_message'); ?></span>
                            <span class="btn-loader">
                                <i class="icon-spinner"></i>
                            </span>
                        </button>

                        <div class="form-result" id="formResult"></div>
                    </form>
                </div>

                <div class="contact-info-section">
                    <div class="info-card">
                        <h3><?php echo $lang->get('contact_info'); ?></h3>
                        
                        <div class="contact-methods">
                            <div class="contact-method">
                                <div class="method-icon">
                                    <i class="icon-phone"></i>
                                </div>
                                <div class="method-content">
                                    <h4><?php echo $lang->get('phone_number'); ?></h4>
                                    <p><a href="tel:+33123456789">+33 1 23 45 67 89</a></p>
                                    <span><?php echo $lang->get('phone_availability'); ?></span>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <i class="icon-mail"></i>
                                </div>
                                <div class="method-content">
                                    <h4><?php echo $lang->get('email_address'); ?></h4>
                                    <p><a href="mailto:contact@prestacapi.com">contact@prestacapi.com</a></p>
                                    <span><?php echo $lang->get('email_response'); ?></span>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <i class="icon-whatsapp"></i>
                                </div>
                                <div class="method-content">
                                    <h4><?php echo $lang->get('whatsapp'); ?></h4>
                                    <p><a href="https://wa.me/33612345678">+33 6 12 34 56 78</a></p>
                                    <span><?php echo $lang->get('whatsapp_availability'); ?></span>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <i class="icon-map"></i>
                                </div>
                                <div class="method-content">
                                    <h4><?php echo $lang->get('address'); ?></h4>
                                    <p><?php echo $lang->get('company_address'); ?></p>
                                    <span><?php echo $lang->get('office_hours'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card support-card">
                        <h3><?php echo $lang->get('support_center'); ?></h3>
                        <p><?php echo $lang->get('support_center_desc'); ?></p>
                        
                        <div class="support-links">
                            <a href="<?php echo generateLocalizedUrl('faq'); ?>" class="support-link">
                                <i class="icon-help"></i>
                                <div>
                                    <h4><?php echo $lang->get('faq'); ?></h4>
                                    <p><?php echo $lang->get('faq_desc'); ?></p>
                                </div>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('help'); ?>" class="support-link">
                                <i class="icon-guide"></i>
                                <div>
                                    <h4><?php echo $lang->get('user_guide'); ?></h4>
                                    <p><?php echo $lang->get('user_guide_desc'); ?></p>
                                </div>
                            </a>
                            
                            <a href="<?php echo generateLocalizedUrl('calculator'); ?>" class="support-link">
                                <i class="icon-calculator"></i>
                                <div>
                                    <h4><?php echo $lang->get('loan_calculator'); ?></h4>
                                    <p><?php echo $lang->get('calculator_desc'); ?></p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <div class="info-card emergency-card">
                        <h3><?php echo $lang->get('emergency_contact'); ?></h3>
                        <p><?php echo $lang->get('emergency_desc'); ?></p>
                        <div class="emergency-number">
                            <i class="icon-phone-urgent"></i>
                            <a href="tel:+33123456700">+33 1 23 45 67 00</a>
                        </div>
                        <span><?php echo $lang->get('emergency_hours'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-faq">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('frequent_questions'); ?></h2>
                <p><?php echo $lang->get('frequent_questions_desc'); ?></p>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q1'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a1'); ?></p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q2'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a2'); ?></p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q3'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a3'); ?></p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q4'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a4'); ?></p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q5'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a5'); ?></p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4><?php echo $lang->get('faq_q6'); ?></h4>
                        <i class="icon-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p><?php echo $lang->get('faq_a6'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="faq-cta">
                <p><?php echo $lang->get('more_questions'); ?></p>
                <a href="<?php echo generateLocalizedUrl('faq'); ?>" class="btn btn-outline">
                    <?php echo $lang->get('view_all_faq'); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="contact-cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo $lang->get('ready_to_apply'); ?></h2>
                <p><?php echo $lang->get('ready_to_apply_desc'); ?></p>
                <div class="cta-actions">
                    <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-lg">
                        <?php echo $lang->get('start_application'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('calculator'); ?>" class="btn btn-outline btn-lg">
                        <?php echo $lang->get('estimate_loan'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const formResult = document.getElementById('formResult');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');

    messageTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        if (count > 1000) {
            charCount.parentElement.classList.add('over-limit');
        } else {
            charCount.parentElement.classList.remove('over-limit');
        }
    });

    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        clearErrors();
        setLoadingState(true);
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('ajax/contact-form.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showResult(result.message, 'success');
                contactForm.reset();
                charCount.textContent = '0';
            } else {
                if (result.errors) {
                    showFieldErrors(result.errors);
                }
                showResult(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showResult('<?php echo $lang->get("error_occurred"); ?>', 'error');
        } finally {
            setLoadingState(false);
        }
    });

    function setLoadingState(loading) {
        if (loading) {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
        } else {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    }

    function showResult(message, type) {
        formResult.textContent = message;
        formResult.className = `form-result ${type}`;
        formResult.style.display = 'block';
        
        setTimeout(() => {
            formResult.style.display = 'none';
        }, 5000);
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(el => {
            el.textContent = '';
            el.parentElement.classList.remove('error');
        });
    }

    function showFieldErrors(errors) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.querySelector(`#${field} + .error-message, [name="${field}"] + .error-message`);
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.parentElement.classList.add('error');
            }
        });
    }

    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        question.addEventListener('click', () => {
            const isOpen = item.classList.contains('open');
            
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('open');
            });
            
            if (!isOpen) {
                item.classList.add('open');
            }
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.contact-method, .info-card, .faq-item').forEach(el => {
        observer.observe(el);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>