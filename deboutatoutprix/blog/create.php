<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_blog');

require_once __DIR__ . '/../../classes/Blog.php';
require_once __DIR__ . '/../../classes/Language.php';
$blog_obj = new Blog();
$lang = Language::getInstance();

$pageTitle = 'Créer un article';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'language' => $_POST['language'] ?? $lang->getCurrentLanguage(),
        'author' => $_POST['author'] ?? $currentAdmin['first_name'] . ' ' . $currentAdmin['last_name'],
        'published' => isset($_POST['published']),
        'excerpt' => $_POST['excerpt'] ?? '',
        'meta_title' => $_POST['meta_title'] ?? '',
        'meta_description' => $_POST['meta_description'] ?? '',
        'meta_keywords' => $_POST['meta_keywords'] ?? ''
    ];

    // Gérer l'upload de l'image
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $blog_obj->uploadFeaturedImage($_FILES['featured_image']);
        if ($uploadResult['success']) {
            $data['featured_image'] = $uploadResult['file_path'];
        } else {
            $errors[] = $uploadResult['message'];
        }
    }

    if (empty($errors)) {
        $result = $blog_obj->createPost($data);
        if ($result['success']) {
            header('Location: edit.php?id=' . $result['post_id'] . '&created=true');
            exit;
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
                    <h1 class="page-title">Créer un nouvel article</h1>
                </div>
            </div>
            <div class="page-actions">
                <button type="submit" name="published" value="1" class="btn btn-primary">
                    💾 Publier
                </button>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>Erreur :</strong> <?php echo implode('<br>', $errors); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Contenu de l'article</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title" class="form-label">Titre *</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="content" class="form-label">Contenu *</label>
                        <textarea id="content" name="content" class="form-control" rows="15"></textarea>
                        <small>Astuce : Utilisez un éditeur de texte riche (WYSIWYG) comme TinyMCE pour une meilleure expérience.</small>
                    </div>
                    <div class="form-group">
                        <label for="excerpt" class="form-label">Extrait (optionnel)</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"></textarea>
                        <small>Un court résumé. S'il est vide, il sera généré automatiquement.</small>
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
                                <input class="form-check-input" type="checkbox" name="published" id="published" value="1" checked>
                                <label class="form-check-label" for="published">Publié</label>
                            </div>
                            <small>Décochez pour sauvegarder en tant que brouillon.</small>
                        </div>
                        <div class="form-group">
                            <label for="language" class="form-label">Langue</label>
                            <select id="language" name="language" class="form-control">
                                <?php foreach ($lang->getSupportedLanguages() as $code): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $lang->getCurrentLanguage() === $code ? 'selected' : ''; ?>>
                                        <?php echo strtoupper($code); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="author" class="form-label">Auteur</label>
                            <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($currentAdmin['first_name'] . ' ' . $currentAdmin['last_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="featured_image" class="form-label">Image à la une</label>
                            <input type="file" id="featured_image" name="featured_image" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header"><h3 class="card-title">SEO</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="meta_title" class="form-label">Titre SEO</label>
                            <input type="text" id="meta_title" name="meta_title" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="meta_description" class="form-label">Description SEO</label>
                            <textarea id="meta_description" name="meta_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="meta_keywords" class="form-label">Mots-clés</label>
                            <input type="text" id="meta_keywords" name="meta_keywords" class="form-control">
                            <small>Séparés par des virgules.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>

<?php include '../includes/footer.php'; ?>