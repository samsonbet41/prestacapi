<?php
$pageKey = 'documents';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);
?>
<?php
require_once 'includes/auth-check.php';
require_once 'classes/Language.php';
require_once 'classes/SEO.php';
require_once 'classes/Document.php';

$lang = Language::getInstance();
$seo = new SEO();
$document = new Document();



$userDocuments = $document->getUserDocuments($currentUser['id'], true);
$documentStatus = $document->getUserDocumentStatus($currentUser['id']);

$errors = [];
$success = false;

if (isset($_SESSION['document_uploaded'])) {
    $success = true;
    $successMessage = $_SESSION['document_uploaded'];
    unset($_SESSION['document_uploaded']);
}

$requiredDocuments = [
    'identity' => [
        'name' => $document->getDocumentTypeName('identity'),
        'description' => $document->getDocumentTypeDescription('identity'),
        'required' => true,
        'icon' => 'icon-id-card'
    ],
    'income_proof' => [
        'name' => $document->getDocumentTypeName('income_proof'),
        'description' => $document->getDocumentTypeDescription('income_proof'),
        'required' => true,
        'icon' => 'icon-document-text'
    ],
    'bank_statement' => [
        'name' => $document->getDocumentTypeName('bank_statement'),
        'description' => $document->getDocumentTypeDescription('bank_statement'),
        'required' => true,
        'icon' => 'icon-bank'
    ],
    'employment_certificate' => [
        'name' => $document->getDocumentTypeName('employment_certificate'),
        'description' => $document->getDocumentTypeDescription('employment_certificate'),
        'required' => false,
        'icon' => 'icon-briefcase'
    ],
    'birth_certificate' => [
        'name' => $document->getDocumentTypeName('birth_certificate'),
        'description' => $document->getDocumentTypeDescription('birth_certificate'),
        'required' => false,
        'icon' => 'icon-certificate'
    ]
];

function getDocumentStatusClass($documents, $docType) {
    if (!isset($documents[$docType]) || empty($documents[$docType])) {
        return 'missing';
    }
    
    $latestDoc = $documents[$docType][0];
    
    if ($latestDoc['is_verified']) {
        return 'verified';
    }
    
    return 'pending';
}

function getDocumentStatusText($documents, $docType) {
    if (!isset($documents[$docType]) || empty($documents[$docType])) {
        return 'Non fourni';
    }
    
    $latestDoc = $documents[$docType][0];
    
    if ($latestDoc['is_verified']) {
        return 'Vérifié';
    }
    
    return 'En cours de vérification';
}


?>
<!DOCTYPE html>
<html lang="<?php echo $lang->getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $seo->generateTitle($pageTitle); ?></title>
    <meta name="description" content="<?php echo $seo->generateDescription($pageDescription); ?>">
    <link rel="canonical" href="<?php echo $seo->generateCanonicalUrl($lang->pageUrl($pageKey)); ?>">
    
    <?php echo $seo->generateAlternateLinks(); ?>
    
    <?php echo $seo->generateOpenGraphTags(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php echo $seo->generateTwitterCard(['title' => $pageTitle, 'description' => $pageDescription]); ?>
    
    <?php echo $seo->generateMetaTags(); ?>

    <?php echo $seo->generateStructuredData('webpage', ['title' => $pageTitle, 'description' => $pageDescription]); ?>
    <?php // Optionnel: Ajouter un Breadcrumb si pertinent
    /*
    echo $seo->generateStructuredData('breadcrumb', ['items' => [
        ['name' => $lang->get('home'), 'url' => $lang->url('home')],
        ['name' => $pageTitle]
    ]]);
    */
    ?>
    <meta name="robots" content="noindex, nofollow">
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/forms.css">
    <link rel="stylesheet" href="/css/responsive.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">
    <?php include 'includes/header.php'; ?>
    
    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-content">
                <div class="page-header">
                    <div class="page-title-section">
                        <h1 class="page-title"><?php echo $lang->get('documents_title'); ?></h1>
                        <p class="page-subtitle">Téléchargez vos documents pour valider votre demande de prêt</p>
                    </div>
                    
                    <div class="documents-progress">
                        <div class="progress-circle">
                            <svg class="progress-ring" width="80" height="80">
                                <circle
                                    class="progress-ring-circle"
                                    stroke="var(--primary-color)"
                                    stroke-width="4"
                                    fill="transparent"
                                    r="36"
                                    cx="40"
                                    cy="40"
                                    style="stroke-dasharray: <?php echo 2 * 3.14159 * 36; ?>; stroke-dashoffset: <?php echo 2 * 3.14159 * 36 * (1 - $documentStatus['completion_percentage'] / 100); ?>"/>
                            </svg>
                            <div class="progress-text">
                                <span class="progress-percentage"><?php echo round($documentStatus['completion_percentage']); ?>%</span>
                                <span class="progress-label">Complété</span>
                            </div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $documentStatus['verified']; ?>/<?php echo $documentStatus['total_required']; ?></span>
                                <span class="stat-label">Documents vérifiés</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($documentStatus['pending_verification']); ?></span>
                                <span class="stat-label">En attente</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($success && isset($successMessage)): ?>
                    <div class="alert alert-success">
                        <i class="icon-check-circle"></i>
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="icon-alert-circle"></i>
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($documentStatus['completion_percentage'] < 100): ?>
                    <div class="alert alert-info">
                        <i class="icon-info-circle"></i>
                        <div class="alert-content">
                            <h3>Documents requis manquants</h3>
                            <p>Pour finaliser votre demande de prêt, veuillez fournir tous les documents obligatoires.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="documents-content">
                    <div class="documents-grid">
                        <?php foreach ($requiredDocuments as $docType => $docInfo):
                            $statusClass = getDocumentStatusClass($userDocuments, $docType);
                            $statusText = getDocumentStatusText($userDocuments, $docType);
                            $hasDocument = isset($userDocuments[$docType]) && !empty($userDocuments[$docType]);
                            $latestDoc = $hasDocument ? $userDocuments[$docType][0] : null;
                            ?>
                            
                            <div class="document-card <?php echo $statusClass; ?>">
                                <div class="document-header">
                                    <div class="document-icon">
                                        <i class="<?php echo $docInfo['icon']; ?>"></i>
                                    </div>
                                    <div class="document-info">
                                        <h3 class="document-title">
                                            <?php echo htmlspecialchars($docInfo['name']); ?>
                                            <?php if ($docInfo['required']): ?>
                                                <span class="required-badge">Obligatoire</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="document-description"><?php echo htmlspecialchars($docInfo['description']); ?></p>
                                    </div>
                                    <div class="document-status">
                                        <span class="status-badge status-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($hasDocument): ?>
                                    <div class="document-files">
                                        <?php foreach ($userDocuments[$docType] as $doc):
                                            if (strpos($doc['mime_type'], 'pdf') !== false):
                                                $icon = 'icon-file-pdf';
                                            else:
                                                $icon = 'icon-file-image';
                                            endif;
                                            ?>
                                            <div class="file-item">
                                                <div class="file-info">
                                                    <div class="file-icon">
                                                        <i class="<?php echo $icon; ?>"></i>
                                                    </div>
                                                    <div class="file-details">
                                                        <span class="file-name"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                                                        <span class="file-meta">
                                                            <?php echo formatFileSize($doc['file_size']); ?> • 
                                                            <?php echo $lang->formatDate($doc['uploaded_at']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="file-actions">
                                                    <button class="btn-icon" onclick="viewDocument('<?php echo $doc['file_path']; ?>')" title="Voir">
                                                        <i class="icon-eye"></i>
                                                    </button>
                                                    <button class="btn-icon" onclick="downloadDocument('<?php echo $doc['file_path']; ?>')" title="Télécharger">
                                                        <i class="icon-download"></i>
                                                    </button>
                                                    <?php if (!$doc['is_verified']): ?>
                                                        <button class="btn-icon btn-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="Supprimer">
                                                            <i class="icon-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($doc['notes'])): ?>
                                                <div class="file-notes">
                                                    <i class="icon-message-circle"></i>
                                                    <span><?php echo htmlspecialchars($doc['notes']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="document-actions">
                                    <?php if (!$hasDocument || !$latestDoc['is_verified']):
                                        $buttonText = $hasDocument ? 'Remplacer le fichier' : 'Télécharger un fichier';
                                        ?>
                                        <button class="btn btn-primary btn-upload" onclick="openUploadModal('<?php echo $docType; ?>')">
                                            <i class="icon-upload"></i>
                                            <?php echo $buttonText; ?>
                                        </button>
                                    <?php else:
                                        ?>
                                        <div class="verified-badge">
                                            <i class="icon-check-circle"></i>
                                            <span>Document vérifié</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="documents-help">
                        <div class="help-card">
                            <div class="help-icon">
                                <i class="icon-help-circle"></i>
                            </div>
                            <div class="help-content">
                                <h3>Besoin d\'aide ?</h3>
                                <p>Nos conseils pour bien préparer vos documents :</p>
                                <ul class="help-list">
                                    <li>Scannez vos documents en haute qualité (minimum 300 DPI)</li>
                                    <li>Assurez-vous que tous les textes sont lisibles</li>
                                    <li>Les documents doivent être récents (moins de 3 mois)</li>
                                    <li>Formats acceptés : JPG, PNG, PDF (max 5MB par fichier)</li>
                                </ul>
                                <a href="/help/documents" class="help-link">
                                    Guide complet <i class="icon-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div class="modal" id="uploadModal">
        <div class="modal-overlay" onclick="closeUploadModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Télécharger un document</h3>
                <button class="modal-close" onclick="closeUploadModal()">
                    <i class="icon-x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                    <input type="hidden" id="documentType" name="document_type">
                    
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon">
                            <i class="icon-upload-cloud"></i>
                        </div>
                        <div class="upload-text">
                            <h4>Glissez votre fichier ici ou cliquez pour sélectionner</h4>
                            <p>Formats acceptés : JPG, PNG, PDF • Taille maximum : 5MB</p>
                        </div>
                        <input type="file" id="fileInput" name="document" accept=".jpg,.jpeg,.png,.pdf" hidden>
                    </div>
                    
                    <div class="file-preview" id="filePreview" style="display: none;">
                        <div class="preview-item">
                            <div class="preview-icon">
                                <i class="icon-file"></i>
                            </div>
                            <div class="preview-info">
                                <span class="preview-name"></span>
                                <span class="preview-size"></span>
                            </div>
                            <button type="button" class="preview-remove" onclick="removeSelectedFile()">
                                <i class="icon-x"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="documentNotes">Notes (optionnel)</label>
                        <textarea id="documentNotes" name="notes" rows="3" placeholder="Ajoutez des informations complémentaires..."></textarea>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeUploadModal()">
                    Annuler
                </button>
                <button class="btn btn-primary" id="uploadBtn" onclick="uploadDocument()" disabled>
                    <span class="btn-text">Télécharger</span>
                    <div class="btn-loader"></div>
                </button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="viewModal">
        <div class="modal-overlay" onclick="closeViewModal()"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 class="modal-title">Aperçu du document</h3>
                <button class="modal-close" onclick="closeViewModal()">
                    <i class="icon-x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="document-viewer" id="documentViewer">
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <span>Chargement du document...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="/js/modules/file-upload.js"></script>
    <script src="/js/modules/dashboard.js"></script>
    <script>
        let currentDocumentType = '';
        let selectedFile = null;
        
        function openUploadModal(docType) {
            currentDocumentType = docType;
            document.getElementById('documentType').value = docType;
            document.getElementById('uploadModal').classList.add('active');
            document.body.classList.add('modal-open');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.body.classList.remove('modal-open');
            resetUploadForm();
        }
        
        function resetUploadForm() {
            document.getElementById('uploadForm').reset();
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('uploadArea').style.display = 'block';
            document.getElementById('uploadBtn').disabled = true;
            selectedFile = null;
        }
        
        function removeSelectedFile() {
            resetUploadForm();
        }

        // AJOUTEZ CETTE FONCTION ICI
        function showNotification(message, type = 'success') {
            const toast = document.getElementById('toast');
            if (!toast) return; // Sécurité si l\'élément n\'existe pas

            const iconEl = document.getElementById('toastIcon');
            const messageEl = document.getElementById('toastMessage');
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            if(iconEl) iconEl.textContent = icons[type] || icons.info;
            if(messageEl) messageEl.textContent = message;
            
            toast.className = `toast show ${type}`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }
        
        function uploadDocument() {
            if (!selectedFile) return;
            
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.classList.add('loading');
            
            const formData = new FormData();
            formData.append('document', selectedFile);
            formData.append('document_type', currentDocumentType);
            formData.append('notes', document.getElementById('documentNotes').value);
            
            fetch('/ajax/upload-document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Document téléchargé avec succès', 'success');
                    closeUploadModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Erreur lors du téléchargement', 'error');
                }
            })
            .catch(error => {
                showNotification('Erreur réseau', 'error');
            })
            .finally(() => {
                uploadBtn.classList.remove('loading');
            });
        }
        
        function viewDocument(filePath) {
            const modal = document.getElementById('viewModal');
            const viewer = document.getElementById('documentViewer');
            
            modal.classList.add('active');
            document.body.classList.add('modal-open');
            
            viewer.innerHTML = '<div class="loading-placeholder"><div class="spinner"></div><span>Chargement du document...</span></div>';
            
            if (filePath.toLowerCase().includes('.pdf')) {
                viewer.innerHTML = `<iframe src="${filePath}" width="100%" height="600px"></iframe>`;
            } else {
                viewer.innerHTML = `<img src="${filePath}" alt="Document" style="max-width: 100%; height: auto;">`;
            }
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
            document.body.classList.remove('modal-open');
        }
        
        function downloadDocument(filePath) {
            const link = document.createElement('a');
            link.href = filePath;
            link.download = filePath.split('/').pop();
            link.click();
        }
        
        function deleteDocument(documentId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce document ?')) {
                return;
            }
            
            fetch('/ajax/delete-document.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ document_id: documentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Document supprimé', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message || 'Erreur lors de la suppression', 'error');
                }
            })
            .catch(error => {
                showNotification('Erreur réseau', 'error');
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initFileUpload();
            initProgressAnimation();
        });
        
        function initFileUpload() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const filePreview = document.getElementById('filePreview');
            const uploadBtn = document.getElementById('uploadBtn');
            
            uploadArea.addEventListener('click', () => fileInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('drag-over');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('drag-over');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelection(files[0]);
                }
            });
            
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    handleFileSelection(e.target.files[0]);
                }
            });
            
            function handleFileSelection(file) {
                if (file.size > 5 * 1024 * 1024) {
                    showNotification('Le fichier est trop volumineux (max 5MB)', 'error');
                    return;
                }
                
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Type de fichier non autorisé', 'error');
                    return;
                }
                
                selectedFile = file;
                
                uploadArea.style.display = 'none';
                filePreview.style.display = 'block';
                uploadBtn.disabled = false;
                
                document.querySelector('.preview-name').textContent = file.name;
                document.querySelector('.preview-size').textContent = formatFileSize(file.size);
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }
        
        function initProgressAnimation() {
            const circle = document.querySelector('.progress-ring-circle');
            const percentage = <?php echo $documentStatus['completion_percentage']; ?>;
            const circumference = 2 * Math.PI * 36;
            
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference * (1 - percentage / 100);
        }
    </script>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>