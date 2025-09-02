<?php
require_once '../includes/auth-admin.php';
require_once '../../classes/Document.php';
require_once '../../classes/User.php';
require_once '../../classes/Language.php';
requirePermission('manage_documents');

$pageTitle = 'Détail du document';

$documentId = intval($_GET['id'] ?? 0);
if ($documentId <= 0) {
    header('Location: list.php');
    exit;
}

$doc_obj = new Document();
$document = $doc_obj->getDocumentById($documentId);

if (!$document) {
    // Gérer l'erreur, document non trouvé
    header('Location: list.php');
    exit;
}

$user_obj = new User();
$user = $user_obj->getUserById($document['user_id']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
         <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="list.php" class="btn btn-secondary">← Retour</a>
            <div>
                <h1 class="page-title">Document #<?php echo $document['id']; ?></h1>
                <p class="page-subtitle">Type: <?php echo htmlspecialchars($doc_obj->getDocumentTypeName($document['document_type'])); ?></p>
            </div>
        </div>
         <div class="page-actions">
            <?php if (!$document['is_verified']): ?>
                 <button class="btn btn-success" data-action="verify-document" data-id="<?php echo $document['id']; ?>" data-verified="1">✅ Approuver</button>
                 <button class="btn btn-error" data-action="reject-document" data-id="<?php echo $document['id']; ?>" data-verified="0">❌ Rejeter</button>
            <?php endif; ?>
         </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Visualisation du document</h3>
            </div>
            <div class="card-body">
                <?php
                $filePath = '../../' . $document['file_path']; // Chemin relatif depuis ce fichier
                if (file_exists($filePath)):
                    $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                    if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])):
                ?>
                    <img src="/<?php echo $document['file_path']; ?>" alt="Aperçu du document" style="max-width: 100%; border-radius: 8px;">
                <?php elseif ($fileExtension === 'pdf'): ?>
                    <iframe src="/<?php echo $document['file_path']; ?>" style="width: 100%; height: 800px; border: none;"></iframe>
                <?php else: ?>
                    <p>L'aperçu n'est pas disponible pour ce type de fichier.</p>
                <?php endif; ?>
                 <a href="/<?php echo $document['file_path']; ?>" target="_blank" class="btn btn-primary" style="margin-top: 1rem;">Télécharger le document</a>
                <?php else: ?>
                    <p class="text-error">Fichier non trouvé sur le serveur.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informations</h3>
            </div>
            <div class="card-body">
                <h4>Utilisateur</h4>
                <p>
                    <strong>Nom:</strong> <a href="../users/view.php?id=<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></a><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </p>
                <hr>
                <h4>Détails du fichier</h4>
                <p>
                    <strong>Nom du fichier:</strong> <?php echo htmlspecialchars($document['file_name']); ?><br>
                    <strong>Taille:</strong> <?php echo round($document['file_size'] / 1024, 2); ?> KB<br>
                    <strong>Type MIME:</strong> <?php echo htmlspecialchars($document['mime_type']); ?><br>
                    <strong>Date d'upload:</strong> <?php echo formatDateTime($document['uploaded_at']); ?>
                </p>
                <hr>
                <h4>Statut</h4>
                <?php if ($document['is_verified']): ?>
                    <div class="alert alert-success">
                        Document vérifié par l'admin #<?php echo $document['verified_by']; ?> le <?php echo formatDateTime($document['verified_at']); ?>.
                    </div>
                <?php else: ?>
                     <div class="alert alert-warning">
                        Ce document est en attente de vérification.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
// Ajout d'une gestion simple pour les actions via admin.js
document.addEventListener('click', function(e) {
    const target = e.target.closest('[data-action]');
    if (!target) return;

    const action = target.dataset.action;
    const docId = target.dataset.id;
    const isVerified = target.dataset.verified;

    if (action === 'verify-document' || action === 'reject-document') {
        const notes = action === 'reject-document' ? prompt("Veuillez entrer le motif du rejet :") : '';
        if (action === 'reject-document' && notes === null) return; // Annulé

        handleDocumentAction(docId, isVerified, notes);
    }
});

async function handleDocumentAction(docId, isVerified, notes = '') {
    showLoading();
    try {
        const response = await fetch('../ajax/document-actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                document_id: docId,
                is_verified: isVerified,
                notes: notes
            })
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