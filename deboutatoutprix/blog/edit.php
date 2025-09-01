<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_blog');

require_once __DIR__ . '/../../classes/Blog.php';
require_once __DIR__ . '/../../classes/Language.php';
$blog_obj = new Blog();
$lang = Language::getInstance();

$postId = intval($_GET['id'] ?? 0);
if ($postId <= 0) {
    header('Location: list.php');
    exit;
}

$authorName = 'Administrateur'; // Valeur par défaut
if (isset($currentAdmin['first_name']) && isset($currentAdmin['last_name'])) {
    $authorName = trim($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']);
} elseif (isset($currentAdmin['username'])) {
    $authorName = $currentAdmin['username'];
}

$post = $blog_obj->getPostById($postId);
if (!$post) {
    header('Location: list.php');
    exit;
}

$pageTitle = 'Modifier l\'article';
$errors = [];
$success = $_GET['created'] ?? false ? 'Article créé avec succès !' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'author' => $_POST['author'] ?? $post['author'],
        'published' => isset($_POST['published']),
        'excerpt' => $_POST['excerpt'] ?? '',
        'meta_title' => $_POST['meta_title'] ?? '',
        'meta_description' => $_POST['meta_description'] ?? '',
        'meta_keywords' => $_POST['meta_keywords'] ?? ''
    ];

    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $blog_obj->uploadFeaturedImage($_FILES['featured_image'], $postId);
        if ($uploadResult['success']) {
            $data['featured_image'] = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }

    if (empty($errors)) {
        $result = $blog_obj->updatePost($postId, $data);
        if ($result['success']) {
            $success = 'Article mis à jour avec succès !';
            $post = $blog_obj->getPostById($postId); // Re-fetch data
        } else {
            $errors[] = $result['message'];
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <form method="POST" enctype="multipart/form-data">
        <div class="page-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="list.php" class="btn btn-secondary">← Retour</a>
                <div>
                    <h1 class="page-title">Modifier l'article</h1>
                </div>
            </div>
            <div class="page-actions">
                 <a href="/pages/blog-post.php?slug=<?php echo $post['slug']; ?>" target="_blank" class="btn btn-secondary">👁️ Prévisualiser</a>
                <button type="submit" class="btn btn-primary">
                    💾 Mettre à jour
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><strong>Erreur :</strong> <?php echo implode('<br>', $errors); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Contenu de l'article</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title" class="form-label">Titre *</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="content" class="form-label">Contenu *</label>
                        <textarea id="content" name="content" class="form-control" rows="15"><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="excerpt" class="form-label">Extrait</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                    </div>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header"><h3 class="card-title">Organisation</h3></div>
                    <div class="card-body">
                         <div class="form-group">
                            <label for="status" class="form-label">Statut</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="published" id="published" value="1" <?php echo $post['published'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="published">Publié</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Langue</label>
                            <input type="text" class="form-control" value="<?php echo strtoupper($post['language']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="author" class="form-label">Auteur</label>
                            <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($authorName); ?>">
                        </div>
                        <div class="form-group">
                            <label for="featured_image" class="form-label">Image à la une</label>
                            <?php if (!empty($post['featured_image'])): ?>
                                <img src="<?php echo $post['featured_image']; ?>" alt="Image actuelle" style="max-width: 100%; border-radius: 8px; margin-bottom: 1rem;">
                            <?php endif; ?>
                            <input type="file" id="featured_image" name="featured_image" class="form-control">
                            <small>Laissez vide pour conserver l'image actuelle.</small>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><h3 class="card-title">SEO</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="meta_title" class="form-label">Titre SEO</label>
                            <input type="text" id="meta_title" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($post['meta_title']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="meta_description" class="form-label">Description SEO</label>
                            <textarea id="meta_description" name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="meta_keywords" class="form-label">Mots-clés</label>
                            <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?php echo htmlspecialchars($post['meta_keywords']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<?php include '../includes/footer.php'; ?>