<?php
$pageKey = 'blog';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<?php
require_once 'includes/header.php';
require_once 'classes/Blog.php';



$blog = new Blog();
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$postsPerPage = 9;
$offset = ($currentPage - 1) * $postsPerPage;

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$selectedAuthor = isset($_GET['author']) ? trim($_GET['author']) : '';

$posts = [];
$totalPosts = 0;

try {
    if (!empty($searchQuery)) {
        $posts = $blog->searchPosts($searchQuery, $currentLang, $postsPerPage);
        $totalPosts = count($posts);
    } elseif (!empty($selectedAuthor)) {
        $posts = $blog->getPostsByAuthor($selectedAuthor, $currentLang, $postsPerPage);
        $totalPosts = count($posts);
    } else {
        $posts = $blog->getAllPosts($currentLang, true, $postsPerPage, $offset);
        $totalPosts = $db->count("SELECT COUNT(*) FROM blog_posts WHERE language = ? AND published = 1", [$currentLang]);
    }
    
    $featuredPosts = $blog->getFeaturedPosts($currentLang, 3);
    $popularTags = $blog->getPopularTags($currentLang, 15);
    $monthlyArchive = $blog->getMonthlyArchive($currentLang);
    
} catch (Exception $e) {
    error_log("Erreur chargement blog: " . $e->getMessage());
    $posts = [];
    $featuredPosts = [];
    $popularTags = [];
    $monthlyArchive = [];
}

$totalPages = ceil($totalPosts / $postsPerPage);
?>

<main class="blog-page">
    <section class="blog-hero">
        <div class="container">
            <div class="hero-content">
                <h1><?php echo $lang->get('blog_title'); ?></h1>
                <p><?php echo $lang->get('blog_description'); ?></p>
                <div class="blog-search">
                    <form method="GET" class="search-form">
                        <div class="search-input-group">
                            <input type="text" name="q" placeholder="<?php echo $lang->get('search_articles'); ?>" 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button type="submit" class="search-btn">
                                <i class="icon-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <?php if (!empty($searchQuery)): ?>
                <div class="search-results-info">
                    <p><?php echo $lang->get('search_results_for'); ?> "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" 
                    (<?php echo count($posts); ?> <?php echo $lang->get('results'); ?>)</p>
                    <a href="<?php echo generateLocalizedUrl('blog'); ?>" class="clear-search">
                        <i class="icon-close"></i> <?php echo $lang->get('clear_search'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($featuredPosts) && empty($searchQuery) && empty($selectedAuthor) && $currentPage === 1): ?>
    <section class="featured-posts">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('featured_articles'); ?></h2>
            </div>
            <div class="featured-grid">
                <?php foreach ($featuredPosts as $index => $post): ?>
                <article class="featured-post <?php echo $index === 0 ? 'featured-main' : 'featured-secondary'; ?>">
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="post-image">
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                             loading="lazy">
                        <div class="post-overlay">
                            <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>" class="read-more-btn">
                                <?php echo $lang->get('read_article'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="post-content">
                        <div class="post-meta">
                            <span class="post-author">
                                <i class="icon-user"></i>
                                <?php echo htmlspecialchars($post['author']); ?>
                            </span>
                            <span class="post-date">
                                <i class="icon-calendar"></i>
                                <?php echo $lang->formatDate($post['created_at']); ?>
                            </span>
                            <span class="post-views">
                                <i class="icon-eye"></i>
                                <?php echo number_format($post['views']); ?>
                            </span>
                        </div>
                        <h3>
                            <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <?php if (!empty($post['meta_keywords'])): ?>
                        <div class="post-tags">
                            <?php 
                            $tags = array_slice(explode(',', $post['meta_keywords']), 0, 3);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="blog-content">
        <div class="container">
            <div class="blog-layout">
                <div class="blog-main">
                    <?php if (!empty($posts)): ?>
                    <div class="posts-header">
                        <h2>
                            <?php if (!empty($searchQuery)): ?>
                                <?php echo $lang->get('search_results'); ?>
                            <?php elseif (!empty($selectedAuthor)): ?>
                                <?php echo $lang->get('articles_by'); ?> <?php echo htmlspecialchars($selectedAuthor); ?>
                            <?php else: ?>
                                <?php echo $lang->get('latest_articles'); ?>
                            <?php endif; ?>
                        </h2>
                        <div class="posts-count">
                            <?php echo $totalPosts; ?> <?php echo $lang->get('articles_found'); ?>
                        </div>
                    </div>

                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <?php if (!empty($post['featured_image'])): ?>
                            <div class="post-image">
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     loading="lazy">
                            </div>
                            <?php endif; ?>
                            <div class="post-content">
                                <div class="post-meta">
                                    <span class="post-author">
                                        <a href="?author=<?php echo urlencode($post['author']); ?>">
                                            <i class="icon-user"></i>
                                            <?php echo htmlspecialchars($post['author']); ?>
                                        </a>
                                    </span>
                                    <span class="post-date">
                                        <i class="icon-calendar"></i>
                                        <?php echo $lang->formatDate($post['created_at']); ?>
                                    </span>
                                    <span class="post-views">
                                        <i class="icon-eye"></i>
                                        <?php echo number_format($post['views']); ?>
                                    </span>
                                </div>
                                <h3>
                                    <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </a>
                                </h3>
                                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                <?php if (!empty($post['meta_keywords'])): ?>
                                <div class="post-tags">
                                    <?php 
                                    $tags = array_slice(explode(',', $post['meta_keywords']), 0, 3);
                                    foreach ($tags as $tag): 
                                        $tag = trim($tag);
                                        if (!empty($tag)):
                                    ?>
                                        <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                                <?php endif; ?>
                                <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>" class="read-more">
                                    <?php echo $lang->get('read_more'); ?> <i class="icon-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo !empty($selectedAuthor) ? '&author=' . urlencode($selectedAuthor) : ''; ?>" 
                               class="pagination-btn prev">
                                <i class="icon-chevron-left"></i> <?php echo $lang->get('previous'); ?>
                            </a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?page=1<?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo !empty($selectedAuthor) ? '&author=' . urlencode($selectedAuthor) : ''; ?>" class="pagination-number">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i === $currentPage): ?>
                                    <span class="pagination-number active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo !empty($selectedAuthor) ? '&author=' . urlencode($selectedAuthor) : ''; ?>" 
                                       class="pagination-number"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo !empty($selectedAuthor) ? '&author=' . urlencode($selectedAuthor) : ''; ?>" class="pagination-number"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?><?php echo !empty($selectedAuthor) ? '&author=' . urlencode($selectedAuthor) : ''; ?>" 
                               class="pagination-btn next">
                                <?php echo $lang->get('next'); ?> <i class="icon-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="no-posts">
                        <div class="no-posts-icon">
                            <i class="icon-document"></i>
                        </div>
                        <h3><?php echo $lang->get('no_articles_found'); ?></h3>
                        <p>
                            <?php if (!empty($searchQuery)): ?>
                                <?php echo $lang->get('no_search_results'); ?>
                            <?php else: ?>
                                <?php echo $lang->get('no_articles_available'); ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($searchQuery)): ?>
                        <a href="<?php echo generateLocalizedUrl('blog'); ?>" class="btn btn-primary">
                            <?php echo $lang->get('view_all_articles'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="blog-sidebar">
                    <div class="sidebar-widget search-widget">
                        <h3><?php echo $lang->get('search_articles'); ?></h3>
                        <form method="GET" class="sidebar-search">
                            <div class="search-input-group">
                                <input type="text" name="q" placeholder="<?php echo $lang->get('search_placeholder'); ?>" 
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                                <button type="submit">
                                    <i class="icon-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php if (!empty($popularTags)): ?>
                    <div class="sidebar-widget tags-widget">
                        <h3><?php echo $lang->get('popular_tags'); ?></h3>
                        <div class="tags-cloud">
                            <?php foreach ($popularTags as $tag => $count): ?>
                            <a href="?q=<?php echo urlencode($tag); ?>" class="tag-link" 
                               style="font-size: <?php echo min(18, 12 + ($count * 2)); ?>px;">
                                <?php echo htmlspecialchars($tag); ?>
                                <span class="tag-count">(<?php echo $count; ?>)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($monthlyArchive)): ?>
                    <div class="sidebar-widget archive-widget">
                        <h3><?php echo $lang->get('archive'); ?></h3>
                        <ul class="archive-list">
                            <?php foreach (array_slice($monthlyArchive, 0, 12) as $archive): ?>
                            <li>
                                <a href="?year=<?php echo $archive['year']; ?>&month=<?php echo $archive['month']; ?>">
                                    <?php echo $archive['month_name']; ?> <?php echo $archive['year']; ?>
                                    <span class="post-count">(<?php echo $archive['post_count']; ?>)</span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="sidebar-widget newsletter-widget">
                        <h3><?php echo $lang->get('newsletter_signup'); ?></h3>
                        <p><?php echo $lang->get('newsletter_description'); ?></p>
                        <form class="newsletter-form" method="POST" action="ajax/newsletter-signup.php">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            <div class="form-group">
                                <input type="email" name="email" placeholder="<?php echo $lang->get('your_email'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-full">
                                <?php echo $lang->get('subscribe'); ?>
                            </button>
                        </form>
                    </div>

                    <div class="sidebar-widget cta-widget">
                        <div class="cta-content">
                            <h3><?php echo $lang->get('need_loan'); ?></h3>
                            <p><?php echo $lang->get('loan_cta_description'); ?></p>
                            <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-full">
                                <?php echo $lang->get('apply_now'); ?>
                            </a>
                        </div>
                    </div>

                    <div class="sidebar-widget contact-widget">
                        <h3><?php echo $lang->get('contact_us'); ?></h3>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="icon-phone"></i>
                                <span>+33 1 23 45 67 89</span>
                            </div>
                            <div class="contact-item">
                                <i class="icon-mail"></i>
                                <span>contact@prestacapi.com</span>
                            </div>
                            <div class="contact-item">
                                <i class="icon-whatsapp"></i>
                                <span>+33 6 12 34 56 78</span>
                            </div>
                        </div>
                        <a href="<?php echo generateLocalizedUrl('contact'); ?>" class="btn btn-outline btn-sm">
                            <?php echo $lang->get('contact_form'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.post-card, .featured-post, .sidebar-widget').forEach(el => {
        observer.observe(el);
    });

    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = '<?php echo $lang->get("subscribing"); ?>...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    submitBtn.textContent = '<?php echo $lang->get("subscribed"); ?>!';
                    this.reset();
                    setTimeout(() => {
                        submitBtn.textContent = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('Newsletter subscription error:', error);
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    const searchForms = document.querySelectorAll('.search-form, .sidebar-search');
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const input = this.querySelector('input[name="q"]');
            if (!input.value.trim()) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>