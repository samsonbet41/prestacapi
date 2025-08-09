<?php
require_once '../classes/Admin.php';

session_start();

$admin = new Admin();

if ($admin->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}
?>