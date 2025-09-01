<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_blog'); // Assurez-vous d'avoir cette permission ou adaptez-la

require_once __DIR__ . '/../../classes/Language.php';

require_once __DIR__ . '/../../classes/Blog.php';
$blog_obj = new Blog();
$lang = Language::getInstance();
$db = Database::getInstance();

$pageTitle = 'Gestion du blog';

// Pagination et filtres (simplifié pour commencer)
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Le 'false' dans getAllPosts est pour récupérer les articles publiés ET les brouillons
$posts = $db->fetchAll("
    SELECT * FROM blog_posts 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
", [$limit, $offset]);

// On s'assure que le total correspond bien à tous les articles
$totalPosts = $db->count("SELECT COUNT(*) FROM blog_posts");
$totalPages = ceil($totalPosts / $limit);

$blogStats = $blog_obj->getBlogStats();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <h1 class="page-title">Gestion du blog</h1>
        <p class="page-subtitle">Rédigez, modifiez et gérez les articles du blog</p>
        <div class="page-actions">
            <a href="create.php" class="btn btn-primary">
                ➕ Créer un article
            </a>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-header"><div class="stat-title">Total articles</div><div class="stat-icon">📰</div></div>
            <div class="stat-value"><?php echo number_format($blogStats['total_posts']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-title">Publiés</div><div class="stat-icon" style="background: var(--success-color);">✅</div></div>
            <div class="stat-value"><?php echo number_format($blogStats['published_posts']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-title">Brouillons</div><div class="stat-icon" style="background: var(--warning-color);">📝</div></div>
            <div class="stat-value"><?php echo number_format($blogStats['draft_posts']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header"><div class="stat-title">Vues totales</div><div class="stat-icon" style="background: var(--info-color);">👁️</div></div>
            <div class="stat-value"><?php echo number_format($blogStats['total_views']); ?></div>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Liste des articles <span class="badge badge-info"><?php echo number_format($totalPosts); ?></span></h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h3 class="empty-state-title">Aucun article pour le moment</h3>
                    <p><a href="create.php">Commencez par rédiger votre premier article !</a></p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Langue</th>
                            <th>Statut</th>
                            <th>Date de création</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: var(--accent-2);">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #6B7280;">
                                        Slug: <?php echo $post['slug']; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($post['author']); ?></td>
                                <td><span class="badge badge-primary"><?php echo strtoupper($post['language']); ?></span></td>
                                <td>
                                    <?php if ($post['published']): ?>
                                        <span class="badge badge-success">Publié</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDateTime($post['created_at']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/pages/blog-post.php?slug=<?php echo $post['slug']; ?>" target="_blank" class="action-btn view" title="Voir l'article">👁️</a>
                                        <a href="edit.php?id=<?php echo $post['id']; ?>" class="action-btn edit" title="Modifier">✏️</a>
                                        <button class="action-btn delete" 
                                                data-action="delete-post" 
                                                data-id="<?php echo $post['id']; ?>" 
                                                data-confirm="Êtes-vous sûr de vouloir supprimer cet article ?">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        </div>
</main>

<script>
// Ce script utilisera admin.js ou un script de page dédié pour gérer les clics
document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action="delete-post"]');
    if (!target) return;

    const postId = target.dataset.id;
    const confirmMessage = target.dataset.confirm;

    if (confirm(confirmMessage)) {
        handlePostAction('delete', postId);
    }
});

async function handlePostAction(action, postId) {
    showLoading();
    try {
        const response = await fetch('../ajax/blog-actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: action, post_id: postId })
        });
        const data = await response.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Une erreur est survenue.', 'error');
    } finally {
        hideLoading();
    }
}
</script>

<?php include '../includes/footer.php'; ?>