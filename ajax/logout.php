<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$currentLang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr';
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: /" . $currentLang . "/");
exit;

?>