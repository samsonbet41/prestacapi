<?php
require_once 'includes/header.php';
require_once 'classes/Testimonial.php';

$seo->generateTitle($lang->get('testimonials_page_title'));
$seo->generateDescription($lang->get('testimonials_page_description'));

$testimonialClass = new Testimonial();
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$testimonialsPerPage = 12;
$offset = ($currentPage - 1) * $testimonialsPerPage;

$filterRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'recent';

$testimonials = [];
$totalTestimonials = 0;
$stats = [];

try {
    if ($filterRating > 0) {
        $testimonials = $testimonialClass->getTestimonialsByRating($filterRating, $testimonialsPerPage);
        $totalTestimonials = count($testimonials);
    } else {
        $testimonials = $testimonialClass->getApprovedTestimonials($testimonialsPerPage + $offset, true);
        $testimonials = array_slice($testimonials, $offset, $testimonialsPerPage);
        $totalTestimonials = $db->count("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1");
    }
    
    $featuredTestimonials = $testimonialClass->getFeaturedTestimonials(3);
    $stats = $testimonialClass->getTestimonialStats();
    
} catch (Exception $e) {
    error_log("Erreur chargement témoignages: " . $e->getMessage());
    $testimonials = [];
    $featuredTestimonials = [];
}

$totalPages = ceil($totalTestimonials / $testimonialsPerPage);
?>

<main class="testimonials-page">
    <section class="testimonials-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang->get('testimonials_title'); ?></h1>
                <p class="hero-subtitle"><?php echo $lang->get('testimonials_description'); ?></p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo isset($stats['approved']) ? number_format($stats['approved']) : '500+'; ?></div>
                        <div class="stat-label"><?php echo $lang->get('satisfied_clients'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo isset($stats['average_rating']) ? $stats['average_rating'] : '4.8'; ?></div>
                        <div class="stat-label"><?php echo $lang->get('average_rating'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">17</div>
                        <div class="stat-label"><?php echo $lang->get('years_trust'); ?></div>
                    </div>
                </div>
                
                <?php if (isset($stats['average_rating'])): ?>
                <div class="overall-rating">
                    <div class="rating-display">
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($stats['average_rating'])): ?>
                                    <span class="star filled">★</span>
                                <?php elseif ($i - 0.5 <= $stats['average_rating']): ?>
                                    <span class="star half">★</span>
                                <?php else: ?>
                                    <span class="star">★</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text">
                            <?php echo $stats['average_rating']; ?>/5 (<?php echo $stats['approved']; ?> <?php echo $lang->get('reviews'); ?>)
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($featuredTestimonials)): ?>
    <section class="featured-testimonials">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('featured_testimonials'); ?></h2>
                <p><?php echo $lang->get('featured_testimonials_desc'); ?></p>
            </div>
            <div class="featured-testimonials-grid">
                <?php foreach ($featuredTestimonials as $testimonial): ?>
                <div class="featured-testimonial-card">
                    <div class="testimonial-content">
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($testimonial['title'])): ?>
                            <h3><?php echo htmlspecialchars($testimonial['title']); ?></h3>
                        <?php endif; ?>
                        <p class="testimonial-text"><?php echo htmlspecialchars($testimonial['content']); ?></p>
                    </div>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <?php echo strtoupper(substr($testimonial['name'], 0, 1)); ?>
                        </div>
                        <div class="author-info">
                            <div class="author-name"><?php echo htmlspecialchars($testimonial['name']); ?></div>
                            <div class="author-label"><?php echo $lang->get('verified_client'); ?></div>
                            <div class="testimonial-date"><?php echo $lang->formatDate($testimonial['created_at']); ?></div>
                        </div>
                    </div>
                    <div class="featured-badge">
                        <i class="icon-star"></i>
                        <?php echo $lang->get('featured'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="testimonials-main">
        <div class="container">
            <div class="testimonials-header">
                <div class="header-left">
                    <h2><?php echo $lang->get('all_testimonials'); ?></h2>
                    <p><?php echo $totalTestimonials; ?> <?php echo $lang->get('client_reviews'); ?></p>
                </div>
                <div class="header-right">
                    <div class="testimonials-filters">
                        <div class="filter-group">
                            <label><?php echo $lang->get('filter_by_rating'); ?>:</label>
                            <select id="ratingFilter" onchange="filterTestimonials()">
                                <option value="0" <?php echo $filterRating === 0 ? 'selected' : ''; ?>><?php echo $lang->get('all_ratings'); ?></option>
                                <option value="5" <?php echo $filterRating === 5 ? 'selected' : ''; ?>>5 ★</option>
                                <option value="4" <?php echo $filterRating === 4 ? 'selected' : ''; ?>>4 ★+</option>
                                <option value="3" <?php echo $filterRating === 3 ? 'selected' : ''; ?>>3 ★+</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><?php echo $lang->get('sort_by'); ?>:</label>
                            <select id="sortFilter" onchange="sortTestimonials()">
                                <option value="recent" <?php echo $sortBy === 'recent' ? 'selected' : ''; ?>><?php echo $lang->get('most_recent'); ?></option>
                                <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>><?php echo $lang->get('highest_rating'); ?></option>
                                <option value="featured" <?php echo $sortBy === 'featured' ? 'selected' : ''; ?>><?php echo $lang->get('featured_first'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($stats['rating_distribution'])): ?>
            <div class="rating-breakdown">
                <h3><?php echo $lang->get('rating_breakdown'); ?></h3>
                <div class="rating-bars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <div class="rating-bar">
                        <div class="rating-label">
                            <?php echo $i; ?> ★
                        </div>
                        <div class="rating-progress">
                            <?php 
                            $count = $stats['rating_distribution'][$i] ?? 0;
                            $percentage = $stats['approved'] > 0 ? ($count / $stats['approved']) * 100 : 0;
                            ?>
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="rating-count">
                            <?php echo $count; ?> (<?php echo round($percentage, 1); ?>%)
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($testimonials)): ?>
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="testimonial-date">
                            <?php echo $lang->formatDate($testimonial['created_at']); ?>
                        </div>
                        <?php if ($testimonial['is_featured']): ?>
                        <div class="featured-icon">
                            <i class="icon-star"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="testimonial-content">
                        <?php if (!empty($testimonial['title'])): ?>
                            <h4 class="testimonial-title"><?php echo htmlspecialchars($testimonial['title']); ?></h4>
                        <?php endif; ?>
                        <p class="testimonial-text"><?php echo htmlspecialchars($testimonial['content']); ?></p>
                    </div>
                    
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <?php echo strtoupper(substr($testimonial['name'], 0, 1)); ?>
                        </div>
                        <div class="author-info">
                            <div class="author-name"><?php echo htmlspecialchars($testimonial['name']); ?></div>
                            <div class="author-label"><?php echo $lang->get('verified_client'); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?><?php echo $filterRating > 0 ? '&rating=' . $filterRating : ''; ?><?php echo $sortBy !== 'recent' ? '&sort=' . $sortBy : ''; ?>" 
                       class="pagination-btn prev">
                        <i class="icon-chevron-left"></i> <?php echo $lang->get('previous'); ?>
                    </a>
                <?php endif; ?>

                <div class="pagination-numbers">
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="?page=1<?php echo $filterRating > 0 ? '&rating=' . $filterRating : ''; ?><?php echo $sortBy !== 'recent' ? '&sort=' . $sortBy : ''; ?>" class="pagination-number">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="pagination-number active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $filterRating > 0 ? '&rating=' . $filterRating : ''; ?><?php echo $sortBy !== 'recent' ? '&sort=' . $sortBy : ''; ?>" 
                               class="pagination-number"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="pagination-dots">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?><?php echo $filterRating > 0 ? '&rating=' . $filterRating : ''; ?><?php echo $sortBy !== 'recent' ? '&sort=' . $sortBy : ''; ?>" class="pagination-number"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                </div>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?><?php echo $filterRating > 0 ? '&rating=' . $filterRating : ''; ?><?php echo $sortBy !== 'recent' ? '&sort=' . $sortBy : ''; ?>" 
                       class="pagination-btn next">
                        <?php echo $lang->get('next'); ?> <i class="icon-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="no-testimonials">
                <div class="no-testimonials-icon">
                    <i class="icon-star-outline"></i>
                </div>
                <h3><?php echo $lang->get('no_testimonials_found'); ?></h3>
                <p>
                    <?php if ($filterRating > 0): ?>
                        <?php echo $lang->get('no_testimonials_rating'); ?>
                    <?php else: ?>
                        <?php echo $lang->get('no_testimonials_available'); ?>
                    <?php endif; ?>
                </p>
                <?php if ($filterRating > 0): ?>
                <a href="<?php echo generateLocalizedUrl('testimonials'); ?>" class="btn btn-primary">
                    <?php echo $lang->get('view_all_testimonials'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($user->isLoggedIn()): ?>
    <section class="leave-testimonial">
        <div class="container">
            <div class="testimonial-cta">
                <div class="cta-content">
                    <h2><?php echo $lang->get('share_experience'); ?></h2>
                    <p><?php echo $lang->get('share_experience_desc'); ?></p>
                    
                    <?php
                    $canSubmit = $testimonialClass->canUserSubmitTestimonial($user->getCurrentUser()['id']);
                    ?>
                    
                    <?php if ($canSubmit['can_submit']): ?>
                    <button class="btn btn-primary btn-lg" onclick="openTestimonialModal()">
                        <i class="icon-star"></i>
                        <?php echo $lang->get('write_testimonial'); ?>
                    </button>
                    <?php else: ?>
                    <div class="cannot-submit">
                        <p class="info-text"><?php echo $canSubmit['reason']; ?></p>
                        <?php if ($canSubmit['reason'] === 'Aucun prêt approuvé trouvé'): ?>
                        <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary">
                            <?php echo $lang->get('apply_for_loan'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <section class="guest-testimonial-cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo $lang->get('become_client'); ?></h2>
                <p><?php echo $lang->get('become_client_desc'); ?></p>
                <div class="cta-actions">
                    <a href="<?php echo generateLocalizedUrl('register'); ?>" class="btn btn-primary btn-lg">
                        <?php echo $lang->get('create_account'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-outline btn-lg">
                        <?php echo $lang->get('apply_now'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="testimonials-trust">
        <div class="container">
            <div class="trust-content">
                <h2><?php echo $lang->get('why_trust_us'); ?></h2>
                <div class="trust-grid">
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="icon-shield-check"></i>
                        </div>
                        <h3><?php echo $lang->get('verified_reviews'); ?></h3>
                        <p><?php echo $lang->get('verified_reviews_desc'); ?></p>
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="icon-users-check"></i>
                        </div>
                        <h3><?php echo $lang->get('real_clients'); ?></h3>
                        <p><?php echo $lang->get('real_clients_desc'); ?></p>
                    </div>
                    <div class="trust-item">
                        <div class="trust-icon">
                            <i class="icon-award"></i>
                        </div>
                        <h3><?php echo $lang->get('proven_experience'); ?></h3>
                        <p><?php echo $lang->get('proven_experience_desc'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php if ($user->isLoggedIn() && $canSubmit['can_submit']): ?>
<div id="testimonialModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo $lang->get('write_testimonial'); ?></h3>
            <button class="modal-close" onclick="closeTestimonialModal()">
                <i class="icon-close"></i>
            </button>
        </div>
        <form id="testimonialForm" class="modal-form" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            
            <div class="form-group">
                <label><?php echo $lang->get('your_rating'); ?> *</label>
                <div class="rating-input">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star-input" data-rating="<?php echo $i; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
                <div class="error-message"></div>
            </div>
            
            <div class="form-group">
                <label for="testimonialTitle"><?php echo $lang->get('title_optional'); ?></label>
                <input type="text" id="testimonialTitle" name="title" maxlength="100" 
                       placeholder="<?php echo $lang->get('testimonial_title_placeholder'); ?>">
                <div class="error-message"></div>
            </div>
            
            <div class="form-group">
                <label for="testimonialContent"><?php echo $lang->get('your_experience'); ?> *</label>
                <textarea id="testimonialContent" name="content" rows="5" maxlength="1000" required
                          placeholder="<?php echo $lang->get('testimonial_content_placeholder'); ?>"></textarea>
                <div class="char-counter">
                    <span id="charCount">0</span>/1000
                </div>
                <div class="error-message"></div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="closeTestimonialModal()">
                    <?php echo $lang->get('cancel'); ?>
                </button>
                <button type="submit" class="btn btn-primary" id="submitTestimonial">
                    <span class="btn-text"><?php echo $lang->get('submit_testimonial'); ?></span>
                    <span class="btn-loader">
                        <i class="icon-spinner"></i>
                    </span>
                </button>
            </div>
            
            <div class="form-result" id="testimonialResult"></div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function filterTestimonials() {
    const rating = document.getElementById('ratingFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    let url = new URL(window.location);
    if (rating > 0) {
        url.searchParams.set('rating', rating);
    } else {
        url.searchParams.delete('rating');
    }
    if (sort !== 'recent') {
        url.searchParams.set('sort', sort);
    } else {
        url.searchParams.delete('sort');
    }
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
}

function sortTestimonials() {
    filterTestimonials();
}

function openTestimonialModal() {
    document.getElementById('testimonialModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeTestimonialModal() {
    document.getElementById('testimonialModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('.star-input');
    const ratingInput = document.getElementById('ratingInput');
    const testimonialContent = document.getElementById('testimonialContent');
    const charCount = document.getElementById('charCount');
    const testimonialForm = document.getElementById('testimonialForm');

    if (ratingInputs.length > 0) {
        ratingInputs.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                ratingInput.value = rating;
                
                ratingInputs.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('filled');
                    } else {
                        s.classList.remove('filled');
                    }
                });
            });
            
            star.addEventListener('mouseover', function() {
                const rating = index + 1;
                ratingInputs.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });
        });
        
        document.querySelector('.rating-input').addEventListener('mouseleave', function() {
            ratingInputs.forEach(s => s.classList.remove('hover'));
        });
    }

    if (testimonialContent && charCount) {
        testimonialContent.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 1000) {
                charCount.parentElement.classList.add('over-limit');
            } else {
                charCount.parentElement.classList.remove('over-limit');
            }
        });
    }

    if (testimonialForm) {
        testimonialForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitTestimonial');
            const formResult = document.getElementById('testimonialResult');
            
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('ajax/submit-testimonial.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    formResult.textContent = result.message;
                    formResult.className = 'form-result success';
                    this.reset();
                    charCount.textContent = '0';
                    ratingInputs.forEach(s => s.classList.remove('filled'));
                    ratingInput.value = '';
                    
                    setTimeout(() => {
                        closeTestimonialModal();
                    }, 2000);
                } else {
                    formResult.textContent = result.message;
                    formResult.className = 'form-result error';
                }
                
                formResult.style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                formResult.textContent = '<?php echo $lang->get("error_occurred"); ?>';
                formResult.className = 'form-result error';
                formResult.style.display = 'block';
            } finally {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.testimonial-card, .featured-testimonial-card, .trust-item').forEach(el => {
        observer.observe(el);
    });

    window.onclick = function(event) {
        const modal = document.getElementById('testimonialModal');
        if (event.target === modal) {
            closeTestimonialModal();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>