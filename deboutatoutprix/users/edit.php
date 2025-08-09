<?php
require_once '../includes/auth-admin.php';
requirePermission('manage_users');

$pageTitle = 'Modifier utilisateur';

$userId = intval($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: list.php');
    exit;
}

$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

if (!$user) {
    header('Location: list.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'date_of_birth' => $_POST['date_of_birth'] ?? null,
        'address' => trim($_POST['address'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'country' => trim($_POST['country'] ?? ''),
        'status' => $_POST['status'] ?? 'active'
    ];

    $errors = [];

    if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
        $errors[] = 'Le prénom est requis (minimum 2 caractères)';
    }

    if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
        $errors[] = 'Le nom est requis (minimum 2 caractères)';
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide';
    }

    $existingEmail = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $userId]);
    if ($existingEmail) {
        $errors[] = 'Cette adresse email est déjà utilisée';
    }

    if (!empty($data['phone']) && !preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $data['phone'])) {
        $errors[] = 'Numéro de téléphone invalide';
    }

    if (!empty($data['date_of_birth'])) {
        $birthDate = new DateTime($data['date_of_birth']);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 18) {
            $errors[] = 'L\'utilisateur doit être majeur';
        }
        
        if ($age > 100) {
            $errors[] = 'Date de naissance invalide';
        }
    }

    if (!in_array($data['status'], ['active', 'inactive', 'suspended'])) {
        $errors[] = 'Statut invalide';
    }

    if (isset($_POST['balance'])) {
        $newBalance = floatval($_POST['balance']);
        if ($newBalance < 0) {
            $errors[] = 'Le solde ne peut pas être négatif';
        }
        $data['balance'] = $newBalance;
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $oldData = $user;
            $db->update('users', $data, 'id = ?', [$userId]);

            $changes = [];
            foreach ($data as $key => $value) {
                if ($oldData[$key] != $value) {
                    $changes[] = "$key: '{$oldData[$key]}' → '$value'";
                }
            }

            if (!empty($changes)) {
                $db->logActivity($userId, $currentAdmin['id'], 'user_updated', 
                    'Profil utilisateur modifié: ' . implode(', ', $changes));
            }

            if (!empty($_POST['new_password'])) {
                $newPassword = $_POST['new_password'];
                if (strlen($newPassword) >= 8) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
                    $db->logActivity($userId, $currentAdmin['id'], 'password_reset', 'Mot de passe réinitialisé par admin');
                    $changes[] = 'Mot de passe réinitialisé';
                }
            }

            $db->commit();
            $success = 'Utilisateur mis à jour avec succès';
            
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

            if ($data['status'] !== $oldData['status']) {
                $statusText = $data['status'] === 'active' ? 'activé' : ($data['status'] === 'suspended' ? 'suspendu' : 'désactivé');
                
                $db->insert('notifications', [
                    'user_id' => $userId,
                    'type' => 'general',
                    'title' => 'Statut de compte modifié',
                    'message' => "Votre compte a été $statusText par notre équipe.",
                    'related_id' => null
                ]);
            }

        } catch (Exception $e) {
            $db->rollback();
            $error = 'Erreur lors de la mise à jour: ' . $e->getMessage();
        }
    } else {
        $error = implode(', ', $errors);
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <a href="list.php" class="btn btn-secondary">← Retour</a>
            <div>
                <h1 class="page-title">Modifier l'utilisateur</h1>
                <p class="page-subtitle">
                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> • 
                    ID: <?php echo $user['id']; ?>
                </p>
            </div>
        </div>
        <div class="page-actions">
            <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">
                👁️ Voir détails
            </a>
            <button type="submit" form="editUserForm" class="btn btn-primary">
                💾 Enregistrer
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <form method="POST" id="editUserForm" class="card">
            <div class="card-header">
                <h3 class="card-title">Informations personnelles</h3>
            </div>
            <div class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Prénom *</label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="last_name" class="form-label">Nom *</label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Téléphone</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input type="tel" 
                               id="whatsapp" 
                               name="whatsapp" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['whatsapp']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth" class="form-label">Date de naissance</label>
                        <input type="date" 
                               id="date_of_birth" 
                               name="date_of_birth" 
                               class="form-control" 
                               value="<?php echo $user['date_of_birth']; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="address" class="form-label">Adresse</label>
                        <textarea id="address" 
                                  name="address" 
                                  class="form-control" 
                                  rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="city" class="form-label">Ville</label>
                        <input type="text" 
                               id="city" 
                               name="city" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['city']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="postal_code" class="form-label">Code postal</label>
                        <input type="text" 
                               id="postal_code" 
                               name="postal_code" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['postal_code']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="country" class="form-label">Pays</label>
                        <input type="text" 
                               id="country" 
                               name="country" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['country']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Statut *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspendu</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Gestion du compte</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="balance" class="form-label">Solde (€)</label>
                        <input type="number" 
                               id="balance" 
                               name="balance" 
                               step="0.01" 
                               min="0" 
                               class="form-control" 
                               value="<?php echo number_format($user['balance'], 2, '.', ''); ?>"
                               form="editUserForm">
                        <small class="form-text">Solde actuel: <?php echo formatCurrency($user['balance']); ?></small>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" 
                               id="new_password" 
                               name="new_password" 
                               class="form-control" 
                               placeholder="Laisser vide pour ne pas changer"
                               form="editUserForm">
                        <small class="form-text">Minimum 8 caractères</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email vérifié</label>
                        <div style="padding: 0.75rem; background: <?php echo $user['email_verified'] ? '#d4edda' : '#fff3cd'; ?>; border-radius: 0.375rem;">
                            <?php if ($user['email_verified']): ?>
                                ✅ Email vérifié
                            <?php else: ?>
                                ⚠️ Email non vérifié
                                <button type="button" class="btn btn-sm btn-primary" data-action="verify-email" data-id="<?php echo $user['id']; ?>" style="margin-left: 0.5rem;">
                                    Marquer comme vérifié
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Statistiques</h3>
                </div>
                <div class="card-body">
                    <div class="stats-list">
                        <div class="stats-item">
                            <span class="stats-label">Inscription</span>
                            <span class="stats-value"><?php echo formatDateTime($user['created_at']); ?></span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Dernière modification</span>
                            <span class="stats-value"><?php echo formatDateTime($user['updated_at']); ?></span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Dernière connexion</span>
                            <span class="stats-value">
                                <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Jamais'; ?>
                            </span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Demandes de prêt</span>
                            <span class="stats-value">
                                <?php 
                                $loanCount = $db->count("SELECT COUNT(*) FROM loan_requests WHERE user_id = ?", [$userId]);
                                echo $loanCount;
                                ?>
                            </span>
                        </div>
                        <div class="stats-item">
                            <span class="stats-label">Documents uploadés</span>
                            <span class="stats-value">
                                <?php 
                                $docCount = $db->count("SELECT COUNT(*) FROM documents WHERE user_id = ?", [$userId]);
                                echo $docCount;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3 class="card-title">Actions rapides</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <button class="btn btn-info btn-block" data-action="send-welcome-email" data-id="<?php echo $user['id']; ?>">
                            📧 Renvoyer email de bienvenue
                        </button>
                        
                        <button class="btn btn-warning btn-block" data-action="reset-password" data-id="<?php echo $user['id']; ?>">
                            🔑 Envoyer lien de réinitialisation
                        </button>
                        
                        <button class="btn btn-success btn-block" data-action="login-as-user" data-id="<?php echo $user['id']; ?>">
                            👤 Se connecter en tant qu'utilisateur
                        </button>
                        
                        <div style="border-top: 1px solid #E5E7EB; margin: 0.5rem 0;"></div>
                        
                        <button class="btn btn-error btn-block" data-action="delete-user" data-id="<?php echo $user['id']; ?>" data-confirm="Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.">
                            🗑️ Supprimer l'utilisateur
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionButtons = document.querySelectorAll('[data-action]');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.dataset.action;
            const userId = this.dataset.id;
            const confirmMessage = this.dataset.confirm;
            
            if (confirmMessage && !confirm(confirmMessage)) {
                return;
            }
            
            handleUserAction(action, userId);
        });
    });
    
    const statusSelect = document.getElementById('status');
    statusSelect.addEventListener('change', function() {
        const value = this.value;
        const warning = document.getElementById('status-warning');
        
        if (warning) {
            warning.remove();
        }
        
        if (value === 'suspended') {
            const warningDiv = document.createElement('div');
            warningDiv.id = 'status-warning';
            warningDiv.className = 'alert alert-warning';
            warningDiv.style.marginTop = '0.5rem';
            warningDiv.innerHTML = '<span class="alert-icon">⚠️</span> L\'utilisateur ne pourra plus se connecter ni faire de nouvelles demandes.';
            this.parentNode.appendChild(warningDiv);
        }
    });
});

async function handleUserAction(action, userId) {
    try {
        showLoading();
        
        const response = await fetch('../ajax/user-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                user_id: userId
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast(data.message || 'Action effectuée avec succès', 'success');
            
            if (action === 'delete-user') {
                setTimeout(() => {
                    window.location.href = 'list.php';
                }, 1500);
            } else if (action === 'verify-email') {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else if (action === 'login-as-user') {
                if (data.redirect_url) {
                    window.open(data.redirect_url, '_blank');
                }
            }
        } else {
            showToast(data.message || 'Erreur lors de l\'action', 'error');
        }
    } catch (error) {
        showToast('Erreur de connexion', 'error');
        console.error('Erreur:', error);
    } finally {
        hideLoading();
    }
}
</script>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stats-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #F5F7FA;
    border-radius: 0.375rem;
}

.stats-label {
    font-weight: 500;
    color: #6B7280;
}

.stats-value {
    font-weight: 600;
    color: var(--primary-color);
}

.btn-block {
    width: 100%;
    text-align: center;
}
</style>

<?php include '../includes/footer.php'; ?>