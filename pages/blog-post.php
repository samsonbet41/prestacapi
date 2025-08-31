<?php
$pageKey = 'blog';
$pageTitle = $blogPost['meta_title'] ?: $blogPost['title'];
$pageDescription = $blogPost['meta_description'] ?: $blogPost['excerpt'];
?>
<?php
require_once 'includes/header.php';

if (!isset($blogPost) || empty($blogPost)) {
    header('HTTP/1.1 404 Not Found');
    include '404.php';
    exit;
}



$blog = new Blog();
$relatedPosts = [];
try {
    $relatedPosts = $blog->getRelatedPosts($blogPost['id'], $currentLang, 3);
} catch (Exception $e) {
    error_log("Erreur chargement articles relatés: " . $e->getMessage());
}

$currentUrl = getCurrentUrl();
$shareUrl = urlencode($currentUrl);
$shareTitle = urlencode($blogPost['title']);
$shareText = urlencode($blogPost['excerpt']);
?>

<main class="blog-post-page">
    <article class="blog-post">
        <div class="post-header">
            <div class="container">
                <nav class="breadcrumb">
                    <a href="<?php echo generateLocalizedUrl(''); ?>"><?php echo $lang->get('home'); ?></a>
                    <span class="breadcrumb-separator">›</span>
                    <a href="<?php echo generateLocalizedUrl('blog'); ?>"><?php echo $lang->get('blog'); ?></a>
                    <span class="breadcrumb-separator">›</span>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($blogPost['title']); ?></span>
                </nav>
                
                <div class="post-meta">
                    <div class="meta-left">
                        <span class="post-author">
                            <i class="icon-user"></i>
                            <?php echo htmlspecialchars($blogPost['author']); ?>
                        </span>
                        <span class="post-date">
                            <i class="icon-calendar"></i>
                            <?php echo $lang->formatDate($blogPost['created_at']); ?>
                        </span>
                        <span class="post-views">
                            <i class="icon-eye"></i>
                            <?php echo number_format($blogPost['views']); ?> <?php echo $lang->get('views'); ?>
                        </span>
                        <span class="reading-time">
                            <i class="icon-clock"></i>
                            <?php echo ceil(str_word_count(strip_tags($blogPost['content'])) / 200); ?> min
                        </span>
                    </div>
                    <div class="meta-right">
                        <div class="social-share">
                            <span class="share-label"><?php echo $lang->get('share'); ?>:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" 
                               target="_blank" rel="noopener" class="share-btn facebook">
                                <i class="icon-facebook"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" 
                               target="_blank" rel="noopener" class="share-btn twitter">
                                <i class="icon-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" 
                               target="_blank" rel="noopener" class="share-btn linkedin">
                                <i class="icon-linkedin"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo $shareTitle; ?>%20<?php echo $shareUrl; ?>" 
                               target="_blank" rel="noopener" class="share-btn whatsapp">
                                <i class="icon-whatsapp"></i>
                            </a>
                            <button class="share-btn copy-link" data-url="<?php echo htmlspecialchars($currentUrl); ?>">
                                <i class="icon-link"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <h1 class="post-title"><?php echo htmlspecialchars($blogPost['title']); ?></h1>
                
                <?php if (!empty($blogPost['excerpt'])): ?>
                <div class="post-excerpt">
                    <p><?php echo htmlspecialchars($blogPost['excerpt']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($blogPost['featured_image'])): ?>
        <div class="post-featured-image">
            <div class="container">
                <img src="<?php echo htmlspecialchars($blogPost['featured_image']); ?>" 
                     alt="<?php echo htmlspecialchars($blogPost['title']); ?>" 
                     loading="lazy">
            </div>
        </div>
        <?php endif; ?>

        <div class="post-content-wrapper">
            <div class="container">
                <div class="post-layout">
                    <div class="post-main">
                        <div class="post-content">
                            <?php echo $blogPost['content']; ?>
                        </div>

                        <?php if (!empty($blogPost['meta_keywords'])): ?>
                        <div class="post-tags">
                            <h3><?php echo $lang->get('tags'); ?></h3>
                            <div class="tags-list">
                                <?php 
                                $tags = explode(',', $blogPost['meta_keywords']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <a href="<?php echo generateLocalizedUrl('blog'); ?>?q=<?php echo urlencode($tag); ?>" class="tag">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="post-share-bottom">
                            <div class="share-section">
                                <h3><?php echo $lang->get('share_article'); ?></h3>
                                <div class="social-share-large">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-large facebook">
                                        <i class="icon-facebook"></i>
                                        <span>Facebook</span>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-large twitter">
                                        <i class="icon-twitter"></i>
                                        <span>Twitter</span>
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-large linkedin">
                                        <i class="icon-linkedin"></i>
                                        <span>LinkedIn</span>
                                    </a>
                                    <a href="https://wa.me/?text=<?php echo $shareTitle; ?>%20<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-large whatsapp">
                                        <i class="icon-whatsapp"></i>
                                        <span>WhatsApp</span>
                                    </a>
                                    <button class="share-btn-large copy-link" data-url="<?php echo htmlspecialchars($currentUrl); ?>">
                                        <i class="icon-link"></i>
                                        <span><?php echo $lang->get('copy_link'); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="post-author-bio">
                            <div class="author-info">
                                <div class="author-avatar">
                                    <i class="icon-user"></i>
                                </div>
                                <div class="author-details">
                                    <h3><?php echo htmlspecialchars($blogPost['author']); ?></h3>
                                    <p><?php echo $lang->get('author_bio_prestacapi'); ?></p>
                                    <div class="author-links">
                                        <a href="<?php echo generateLocalizedUrl('blog'); ?>?author=<?php echo urlencode($blogPost['author']); ?>">
                                            <?php echo $lang->get('view_all_articles'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="post-navigation">
                            <div class="nav-links">
                                <?php
                                $prevPost = $db->fetchOne("
                                    SELECT id, title, slug FROM blog_posts 
                                    WHERE language = ? AND published = 1 AND created_at < ? 
                                    ORDER BY created_at DESC LIMIT 1
                                ", [$currentLang, $blogPost['created_at']]);
                                
                                $nextPost = $db->fetchOne("
                                    SELECT id, title, slug FROM blog_posts 
                                    WHERE language = ? AND published = 1 AND created_at > ? 
                                    ORDER BY created_at ASC LIMIT 1
                                ", [$currentLang, $blogPost['created_at']]);
                                ?>
                                
                                <?php if ($prevPost): ?>
                                <div class="nav-link prev">
                                    <a href="<?php echo generateLocalizedUrl('blog', $prevPost['slug']); ?>">
                                        <div class="nav-direction">
                                            <i class="icon-arrow-left"></i>
                                            <?php echo $lang->get('previous_article'); ?>
                                        </div>
                                        <div class="nav-title"><?php echo htmlspecialchars($prevPost['title']); ?></div>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($nextPost): ?>
                                <div class="nav-link next">
                                    <a href="<?php echo generateLocalizedUrl('blog', $nextPost['slug']); ?>">
                                        <div class="nav-direction">
                                            <?php echo $lang->get('next_article'); ?>
                                            <i class="icon-arrow-right"></i>
                                        </div>
                                        <div class="nav-title"><?php echo htmlspecialchars($nextPost['title']); ?></div>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="post-sidebar">
                        <div class="sidebar-sticky">
                            <div class="table-of-contents">
                                <h3><?php echo $lang->get('table_of_contents'); ?></h3>
                                <ul id="tocList">
                                </ul>
                            </div>

                            <div class="share-sticky">
                                <h3><?php echo $lang->get('share'); ?></h3>
                                <div class="share-vertical">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-vertical facebook">
                                        <i class="icon-facebook"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrl; ?>&text=<?php echo $shareTitle; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-vertical twitter">
                                        <i class="icon-twitter"></i>
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-vertical linkedin">
                                        <i class="icon-linkedin"></i>
                                    </a>
                                    <a href="https://wa.me/?text=<?php echo $shareTitle; ?>%20<?php echo $shareUrl; ?>" 
                                       target="_blank" rel="noopener" class="share-btn-vertical whatsapp">
                                        <i class="icon-whatsapp"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="sidebar-widget cta-widget">
                                <h3><?php echo $lang->get('need_financing'); ?></h3>
                                <p><?php echo $lang->get('financing_cta_desc'); ?></p>
                                <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-full">
                                    <?php echo $lang->get('apply_now'); ?>
                                </a>
                            </div>

                            <div class="sidebar-widget newsletter-widget">
                                <h3><?php echo $lang->get('newsletter_signup'); ?></h3>
                                <p><?php echo $lang->get('newsletter_blog_desc'); ?></p>
                                <form class="newsletter-form" method="POST" action="ajax/newsletter-signup.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                    <div class="form-group">
                                        <input type="email" name="email" placeholder="<?php echo $lang->get('your_email'); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-outline btn-full">
                                        <?php echo $lang->get('subscribe'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <?php if (!empty($relatedPosts)): ?>
    <section class="related-posts">
        <div class="container">
            <div class="section-header">
                <h2><?php echo $lang->get('related_articles'); ?></h2>
                <p><?php echo $lang->get('related_articles_desc'); ?></p>
            </div>
            <div class="related-posts-grid">
                <?php foreach ($relatedPosts as $post): ?>
                <article class="related-post-card">
                    <?php if (!empty($post['featured_image'])): ?>
                    <div class="post-image">
                        <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                             loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="post-content">
                        <div class="post-meta">
                            <span class="post-date"><?php echo $lang->formatDate($post['created_at']); ?></span>
                            <span class="post-views"><?php echo number_format($post['views']); ?> <?php echo $lang->get('views'); ?></span>
                        </div>
                        <h3>
                            <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <a href="<?php echo generateLocalizedUrl('blog', $post['slug']); ?>" class="read-more">
                            <?php echo $lang->get('read_more'); ?> <i class="icon-arrow-right"></i>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="blog-cta">
        <div class="container">
            <div class="cta-content">
                <h2><?php echo $lang->get('explore_more'); ?></h2>
                <p><?php echo $lang->get('explore_more_desc'); ?></p>
                <div class="cta-actions">
                    <a href="<?php echo generateLocalizedUrl('blog'); ?>" class="btn btn-outline btn-lg">
                        <?php echo $lang->get('all_articles'); ?>
                    </a>
                    <a href="<?php echo generateLocalizedUrl('loan_request'); ?>" class="btn btn-primary btn-lg">
                        <?php echo $lang->get('apply_for_loan'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": "<?php echo htmlspecialchars($blogPost['title'], ENT_QUOTES); ?>",
    "description": "<?php echo htmlspecialchars($blogPost['excerpt'], ENT_QUOTES); ?>",
    "image": "<?php echo !empty($blogPost['featured_image']) ? htmlspecialchars($blogPost['featured_image'], ENT_QUOTES) : ''; ?>",
    "author": {
        "@type": "Person",
        "name": "<?php echo htmlspecialchars($blogPost['author'], ENT_QUOTES); ?>"
    },
    "publisher": {
        "@type": "Organization",
        "name": "PrestaCapi",
        "logo": {
            "@type": "ImageObject",
            "url": "<?php echo $lang->getBaseUrl(); ?>/images/logo.png"
        }
    },
    "datePublished": "<?php echo date('c', strtotime($blogPost['created_at'])); ?>",
    "dateModified": "<?php echo date('c', strtotime($blogPost['updated_at'] ?: $blogPost['created_at'])); ?>",
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?php echo getCurrentUrl(); ?>"
    }
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function generateTableOfContents() {
        const headings = document.querySelectorAll('.post-content h2, .post-content h3, .post-content h4');
        const tocList = document.getElementById('tocList');
        
        if (headings.length === 0) {
            document.querySelector('.table-of-contents').style.display = 'none';
            return;
        }

        headings.forEach((heading, index) => {
            heading.id = `heading-${index}`;
            
            const li = document.createElement('li');
            li.className = `toc-${heading.tagName.toLowerCase()}`;
            
            const a = document.createElement('a');
            a.href = `#heading-${index}`;
            a.textContent = heading.textContent;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            
            li.appendChild(a);
            tocList.appendChild(li);
        });
    }

    function setupCopyLink() {
        const copyButtons = document.querySelectorAll('.copy-link');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(() => {
                        showCopySuccess(this);
                    });
                } else {
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showCopySuccess(this);
                }
            });
        });
    }

    function showCopySuccess(button) {
        const originalIcon = button.querySelector('i').className;
        const originalText = button.querySelector('span')?.textContent;
        
        button.querySelector('i').className = 'icon-check';
        if (button.querySelector('span')) {
            button.querySelector('span').textContent = '<?php echo $lang->get("copied"); ?>!';
        }
        
        setTimeout(() => {
            button.querySelector('i').className = originalIcon;
            if (button.querySelector('span') && originalText) {
                button.querySelector('span').textContent = originalText;
            }
        }, 2000);
    }

    function setupStickyElements() {
        const sidebar = document.querySelector('.sidebar-sticky');
        const postContent = document.querySelector('.post-content');
        
        if (!sidebar || !postContent) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    sidebar.classList.add('sticky-active');
                } else {
                    sidebar.classList.remove('sticky-active');
                }
            });
        }, { threshold: 0.1 });

        observer.observe(postContent);
    }

    function setupReadingProgress() {
        const progressBar = document.createElement('div');
        progressBar.className = 'reading-progress';
        progressBar.innerHTML = '<div class="progress-fill"></div>';
        document.body.appendChild(progressBar);

        const progressFill = progressBar.querySelector('.progress-fill');
        const postContent = document.querySelector('.post-content');
        
        if (!postContent) return;

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset;
            const contentTop = postContent.offsetTop;
            const contentHeight = postContent.offsetHeight;
            const windowHeight = window.innerHeight;
            
            const progress = Math.max(0, Math.min(100, 
                ((scrollTop - contentTop + windowHeight) / contentHeight) * 100
            ));
            
            progressFill.style.width = `${progress}%`;
        });
    }

    generateTableOfContents();
    setupCopyLink();
    setupStickyElements();
    setupReadingProgress();

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.related-post-card, .post-author-bio, .post-share-bottom').forEach(el => {
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
});
</script>

<?php require_once 'includes/footer.php'; ?>