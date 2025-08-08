<?php

require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'name' => 'PRESTACAPI_SESSION',
        'cookie_lifetime' => SESSION_TIMEOUT,
        'cookie_path' => '/',
        'cookie_domain' => '',
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

function autoload($className) {
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    return false;
}

spl_autoload_register('autoload');

if (MAINTENANCE_MODE && !isAdminPath()) {
    http_response_code(503);
    include __DIR__ . '/../maintenance.php';
    exit;
}

function isAdminPath() {
    return strpos($_SERVER['REQUEST_URI'], '/admin/') === 0;
}

function redirect($url, $permanent = false) {
    $code = $permanent ? 301 : 302;
    header("Location: $url", true, $code);
    exit;
}

function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[\+]?[\d\s\-\(\)]{8,20}$/', $phone);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function formatCurrency($amount, $symbol = CURRENCY_SYMBOL) {
    return number_format($amount, 2, ',', ' ') . ' ' . $symbol;
}

function formatDate($date, $format = 'd/m/Y') {
    if (is_string($date)) {
        $date = strtotime($date);
    }
    return date($format, $date);
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function logError($message, $file = null, $line = null) {
    if (!LOG_ERRORS) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($file) {
        $logMessage .= " in $file";
    }
    
    if ($line) {
        $logMessage .= " on line $line";
    }
    
    error_log($logMessage);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

function getClientIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

function rateLimit($key, $limit = API_RATE_LIMIT, $window = API_RATE_LIMIT_WINDOW) {
    $file = sys_get_temp_dir() . '/rate_limit_' . md5($key);
    $now = time();
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && $now - $data['time'] < $window) {
            if ($data['count'] >= $limit) {
                return false;
            }
            $data['count']++;
        } else {
            $data = ['time' => $now, 'count' => 1];
        }
    } else {
        $data = ['time' => $now, 'count' => 1];
    }
    
    file_put_contents($file, json_encode($data));
    return true;
}

function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireHTTPS() {
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        redirect($redirectURL, true);
    }
}

function getUploadedFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isValidImageType($mimeType) {
    return in_array($mimeType, ALLOWED_IMAGE_TYPES);
}

function isValidDocumentType($mimeType) {
    return in_array($mimeType, ALLOWED_DOCUMENT_TYPES);
}

try {
    $db = Database::getInstance();
    $db->createTablesIfNotExists();
} catch (Exception $e) {
    logError('Database connection failed: ' . $e->getMessage());
    if (DEBUG_MODE) {
        die('Database connection failed: ' . $e->getMessage());
    } else {
        die('Service temporarily unavailable. Please try again later.');
    }
}