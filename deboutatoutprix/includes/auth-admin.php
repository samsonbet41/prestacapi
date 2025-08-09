<?php
require_once '../classes/Admin.php';

$admin = new Admin();

if (!$admin->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentAdmin = $admin->getCurrentAdmin();

function requirePermission($permission) {
    global $admin;
    if (!$admin->hasPermission($permission)) {
        header('HTTP/1.1 403 Forbidden');
        die('Accès refusé - Permission insuffisante');
    }
}

function hasPermission($permission) {
    global $admin;
    return $admin->hasPermission($permission);
}

function isRole($role) {
    global $currentAdmin;
    return $currentAdmin['role'] === $role;
}

function formatDateTime($datetime) {
    return date('d/m/Y à H:i', strtotime($datetime));
}

function formatCurrency($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">En attente</span>',
        'under_review' => '<span class="badge badge-info">En cours</span>',
        'approved' => '<span class="badge badge-success">Approuvé</span>',
        'rejected' => '<span class="badge badge-error">Rejeté</span>',
        'processed' => '<span class="badge badge-success">Traité</span>',
        'active' => '<span class="badge badge-success">Actif</span>',
        'inactive' => '<span class="badge badge-secondary">Inactif</span>',
        'suspended' => '<span class="badge badge-error">Suspendu</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

function getPriorityClass($status) {
    $priorities = [
        'pending' => 'priority-high',
        'under_review' => 'priority-medium',
        'approved' => 'priority-low',
        'rejected' => 'priority-none',
        'processed' => 'priority-none'
    ];
    
    return $priorities[$status] ?? 'priority-none';
}

function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>