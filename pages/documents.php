<?php
$pageKey = 'documents';
$pageTitle = $lang->get('page_title_' . $pageKey);
$pageDescription = $lang->get('page_description_' . $pageKey);

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

// Préparation des traductions pour JavaScript
$js_translations = [
    'upload_success' => $lang->get('documents_upload_success'),
    'upload_error' => $lang->get('documents_upload_error'),
    'network_error' => $lang->get('error_network'),
    'loading_document' => $lang->get('documents_loading_document'),
    'delete_confirm' => $lang->get('documents_delete_confirm'),
    'delete_success' => $lang->get('documents_delete_success'),
    'delete_error' => $lang->get('documents_delete_error'),
    'file_too_large' => $lang->get('documents_file_too_large'),
    'file_type_invalid' => $lang->get('documents_file_type_invalid'),
];


$requiredDocuments = [
    'identity' => [
        'name' => $lang->get('document_type_identity'),
        'description' => $lang->get('document_type_identity_desc'),
        'required' => true,
        'icon' => 'icon-id-card'
    ],
    'income_proof' => [
        'name' => $lang->get('document_type_income_proof'),
        'description' => $lang->get('document_type_income_proof_desc'),
        'required' => true,
        'icon' => 'icon-document-text'
    ],
    'bank_statement' => [
        'name' => $lang->get('document_type_bank_statement'),
        'description' => $lang->get('document_type_bank_statement_desc'),
        'required' => true,
        'icon' => 'icon-bank'
    ],
    'employment_certificate' => [
        'name' => $lang->get('document_type_employment_certificate'),
        'description' => $lang->get('document_type_employment_certificate_desc'),
        'required' => false,
        'icon' => 'icon-briefcase'
    ],
    'birth_certificate' => [
        'name' => $lang->get('document_type_birth_certificate'),
        'description' => $lang->get('document_type_birth_certificate_desc'),
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

function getDocumentStatusText($documents, $docType, $lang) {
    if (!isset($documents[$docType]) || empty($documents[$docType])) {
        return $lang->get('documents_status_not_provided');
    }
    $latestDoc = $documents[$docType][0];
    if ($latestDoc['is_verified']) {
        return $lang->get('documents_status_verified');
    }
    return $lang->get('documents_status_pending');
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
                        <p class="page-subtitle"><?php echo $lang->get('documents_subtitle'); ?></p>
                    </div>
                    
                    <div class="documents-progress">
                        <div class="progress-circle">
                            <svg class="progress-ring" width="80" height="80">
                                <circle class="progress-ring-circle" stroke="var(--primary-color)" stroke-width="4" fill="transparent" r="36" cx="40" cy="40"/>
                            </svg>
                            <div class="progress-text">
                                <span class="progress-percentage"><?php echo round($documentStatus['completion_percentage']); ?>%</span>
                                <span class="progress-label"><?php echo $lang->get('documents_progress_completed'); ?></span>
                            </div>
                        </div>
                        
                        <div class="progress-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $documentStatus['verified']; ?>/<?php echo $documentStatus['total_required']; ?></span>
                                <span class="stat-label"><?php echo $lang->get('documents_progress_verified'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo count($documentStatus['pending_verification']); ?></span>
                                <span class="stat-label"><?php echo $lang->get('documents_progress_pending'); ?></span>
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
                            <h3><?php echo $lang->get('documents_missing_alert_title'); ?></h3>
                            <p><?php echo $lang->get('documents_missing_alert_desc'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="documents-content">
                    <div class="documents-grid">
                        <?php foreach ($requiredDocuments as $docType => $docInfo):
                            $statusClass = getDocumentStatusClass($userDocuments, $docType);
                            $statusText = getDocumentStatusText($userDocuments, $docType, $lang);
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
                                                <span class="required-badge"><?php echo $lang->get('documents_required_badge'); ?></span>
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
                                            $icon = (strpos($doc['mime_type'], 'pdf') !== false) ? 'icon-file-pdf' : 'icon-file-image';
                                            ?>
                                            <div class="file-item">
                                                <div class="file-info">
                                                    <div class="file-icon"><i class="<?php echo $icon; ?>"></i></div>
                                                    <div class="file-details">
                                                        <span class="file-name"><?php echo htmlspecialchars($doc['file_name']); ?></span>
                                                        <span class="file-meta">
                                                            <?php echo formatFileSize($doc['file_size']); ?> • 
                                                            <?php echo $lang->formatDate($doc['uploaded_at']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="file-actions">
                                                    <button class="btn-icon" onclick="viewDocument('<?php echo $doc['file_path']; ?>')" title="<?php echo $lang->get('btn_view'); ?>">
                                                        <i class="icon-eye"></i>
                                                    </button>
                                                    <button class="btn-icon" onclick="downloadDocument('<?php echo $doc['file_path']; ?>')" title="<?php echo $lang->get('btn_download'); ?>">
                                                        <i class="icon-download"></i>
                                                    </button>
                                                    <?php if (!$doc['is_verified']): ?>
                                                        <button class="btn-icon btn-danger" onclick="deleteDocument(<?php echo $doc['id']; ?>)" title="<?php echo $lang->get('btn_delete'); ?>">
                                                            <i class="icon-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="document-actions">
                                    <?php if (!$hasDocument || !$latestDoc['is_verified']):
                                        $buttonText = $hasDocument ? $lang->get('documents_replace_file_btn') : $lang->get('documents_upload_file_btn');
                                        ?>
                                        <button class="btn btn-primary btn-upload" onclick="openUploadModal('<?php echo $docType; ?>')">
                                            <i class="icon-upload"></i>
                                            <?php echo $buttonText; ?>
                                        </button>
                                    <?php else: ?>
                                        <div class="verified-badge">
                                            <i class="icon-check-circle"></i>
                                            <span><?php echo $lang->get('documents_verified_badge'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="documents-help">
                        <div class="help-card">
                            <div class="help-icon"><i class="icon-help-circle"></i></div>
                            <div class="help-content">
                                <h3><?php echo $lang->get('need_help'); ?></h3>
                                <p><?php echo $lang->get('documents_help_intro'); ?></p>
                                <ul class="help-list">
                                    <li><?php echo $lang->get('documents_help_item1'); ?></li>
                                    <li><?php echo $lang->get('documents_help_item2'); ?></li>
                                    <li><?php echo $lang->get('documents_help_item3'); ?></li>
                                    <li><?php echo $lang->get('documents_help_item4'); ?></li>
                                </ul>
                                <a href="<?php echo $lang->pageUrl('help'); ?>/documents" class="help-link">
                                    <?php echo $lang->get('documents_help_link'); ?> <i class="icon-arrow-right"></i>
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
                <h3 class="modal-title"><?php echo $lang->get('documents_upload_modal_title'); ?></h3>
                <button class="modal-close" onclick="closeUploadModal()"><i class="icon-x"></i></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                    <input type="hidden" id="documentType" name="document_type">
                    <div class="upload-area" id="uploadArea">
                        <div class="upload-icon"><i class="icon-upload-cloud"></i></div>
                        <div class="upload-text">
                            <h4><?php echo $lang->get('documents_upload_area_title'); ?></h4>
                            <p><?php echo $lang->get('documents_upload_area_desc'); ?></p>
                        </div>
                        <input type="file" id="fileInput" name="document" accept=".jpg,.jpeg,.png,.pdf" hidden>
                    </div>
                    <div class="file-preview" id="filePreview" style="display: none;">
                        <div class="preview-item">
                            <div class="preview-icon"><i class="icon-file"></i></div>
                            <div class="preview-info">
                                <span class="preview-name"></span>
                                <span class="preview-size"></span>
                            </div>
                            <button type="button" class="preview-remove" onclick="removeSelectedFile()"><i class="icon-x"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="documentNotes"><?php echo $lang->get('documents_notes_label_optional'); ?></label>
                        <textarea id="documentNotes" name="notes" rows="3" placeholder="<?php echo $lang->get('documents_notes_placeholder'); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeUploadModal()"><?php echo $lang->get('btn_cancel'); ?></button>
                <button class="btn btn-primary" id="uploadBtn" onclick="uploadDocument()" disabled>
                    <span class="btn-text"><?php echo $lang->get('btn_upload'); ?></span>
                    <div class="btn-loader"></div>
                </button>
            </div>
        </div>
    </div>
    
    <div class="modal" id="viewModal">
        <div class="modal-overlay" onclick="closeViewModal()"></div>
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo $lang->get('documents_view_modal_title'); ?></h3>
                <button class="modal-close" onclick="closeViewModal()"><i class="icon-x"></i></button>
            </div>
            <div class="modal-body">
                <div class="document-viewer" id="documentViewer">
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <span><?php echo $lang->get('documents_loading_document'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        const translations = <?php echo json_encode($js_translations); ?>;
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

        function showNotification(message, type = 'success') {
            // Implémentation supposée dans un fichier JS global (ex: main.js)
            // Pour l'exemple, on utilise une simple alerte. Remplacez par votre système de notification.
            console.log(`[${type.toUpperCase()}] ${message}`);
            // Exemple avec un système de toast simple:
             const toast = document.createElement('div');
             toast.className = `toast show ${type}`;
             toast.textContent = message;
             document.body.appendChild(toast);
             setTimeout(() => toast.remove(), 4000);
        }

        function uploadDocument() {
            if (!selectedFile) return;
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.classList.add('loading');
            const formData = new FormData();
            formData.append('document', selectedFile);
            formData.append('document_type', currentDocumentType);
            formData.append('notes', document.getElementById('documentNotes').value);

            fetch('/ajax/upload-document.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(translations.upload_success, 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification(data.message || translations.upload_error, 'error');
                    }
                })
                .catch(() => showNotification(translations.network_error, 'error'))
                .finally(() => uploadBtn.classList.remove('loading'));
        }

        function viewDocument(filePath) {
            const modal = document.getElementById('viewModal');
            const viewer = document.getElementById('documentViewer');
            modal.classList.add('active');
            document.body.classList.add('modal-open');
            viewer.innerHTML = `<div class="loading-placeholder"><div class="spinner"></div><span>${translations.loading_document}</span></div>`;
            if (filePath.toLowerCase().endsWith('.pdf')) {
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
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function deleteDocument(documentId) {
            if (!confirm(translations.delete_confirm)) return;
            fetch('/ajax/delete-document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ document_id: documentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(translations.delete_success, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.message || translations.delete_error, 'error');
                }
            })
            .catch(() => showNotification(translations.network_error, 'error'));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const uploadBtn = document.getElementById('uploadBtn');

            if (uploadArea) {
                uploadArea.addEventListener('click', () => fileInput.click());
                uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
                uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
                uploadArea.addEventListener('drop', e => {
                    e.preventDefault();
                    uploadArea.classList.remove('drag-over');
                    if (e.dataTransfer.files.length) handleFileSelection(e.dataTransfer.files[0]);
                });
            }
            if (fileInput) {
                fileInput.addEventListener('change', e => {
                    if (e.target.files.length) handleFileSelection(e.target.files[0]);
                });
            }

            function handleFileSelection(file) {
                if (file.size > 5 * 1024 * 1024) {
                    showNotification(translations.file_too_large, 'error');
                    return;
                }
                const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification(translations.file_type_invalid, 'error');
                    return;
                }
                selectedFile = file;
                uploadArea.style.display = 'none';
                document.getElementById('filePreview').style.display = 'flex';
                uploadBtn.disabled = false;
                document.querySelector('.preview-name').textContent = file.name;
                document.querySelector('.preview-size').textContent = formatFileSize(file.size);
            }

            const circle = document.querySelector('.progress-ring-circle');
            if(circle) {
                const percentage = <?php echo $documentStatus['completion_percentage']; ?>;
                const radius = circle.r.baseVal.value;
                const circumference = 2 * Math.PI * radius;
                circle.style.strokeDasharray = `${circumference} ${circumference}`;
                circle.style.strokeDashoffset = circumference - (percentage / 100) * circumference;
            }
        });
        
        function formatFileSize(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>