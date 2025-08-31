<?php

if (!defined('SITE_NAME')) define('SITE_NAME', 'PrestaCapi');
if (!defined('SITE_TAGLINE')) define('SITE_TAGLINE', 'Votre partenaire financier de confiance depuis 2008');
if (!defined('SITE_URL')) define('SITE_URL', 'https://prestacapi.com');
if (!defined('SITE_EMAIL')) define('SITE_EMAIL', 'contact@prestacapi.com');
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', 'admin@prestacapi.com');
if (!defined('SUPPORT_EMAIL')) define('SUPPORT_EMAIL', 'support@prestacapi.com');

if (!defined('MIN_LOAN_AMOUNT')) define('MIN_LOAN_AMOUNT', 500);
if (!defined('MAX_LOAN_AMOUNT')) define('MAX_LOAN_AMOUNT', 50000);
if (!defined('MIN_LOAN_DURATION')) define('MIN_LOAN_DURATION', 6);
if (!defined('MAX_LOAN_DURATION')) define('MAX_LOAN_DURATION', 60);

if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5242880);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
if (!defined('ALLOWED_DOCUMENT_TYPES')) define('ALLOWED_DOCUMENT_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf']);

if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
if (!defined('DOCUMENTS_DIR')) define('DOCUMENTS_DIR', UPLOAD_DIR . 'documents/');
if (!defined('BLOG_DIR')) define('BLOG_DIR', UPLOAD_DIR . 'blog/');
if (!defined('PARTNERS_DIR')) define('PARTNERS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/images/partners/');

if (!defined('DEFAULT_LANGUAGE')) define('DEFAULT_LANGUAGE', 'fr');
if (!defined('SUPPORTED_LANGUAGES')) define('SUPPORTED_LANGUAGES', ['fr', 'en', 'es', 'de']);

if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 7200);
if (!defined('REMEMBER_ME_DURATION')) define('REMEMBER_ME_DURATION', 2592000);

if (!defined('PAGINATION_LIMIT')) define('PAGINATION_LIMIT', 20);
if (!defined('BLOG_POSTS_PER_PAGE')) define('BLOG_POSTS_PER_PAGE', 10);
if (!defined('TESTIMONIALS_PER_PAGE')) define('TESTIMONIALS_PER_PAGE', 12);

if (!defined('MAINTENANCE_MODE')) define('MAINTENANCE_MODE', false);
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
if (!defined('LOG_ERRORS')) define('LOG_ERRORS', true);

if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.hostinger.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'ssl');
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'no-reply@prestacapi.com');

if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '€');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'EUR');
if (!defined('TIMEZONE')) define('TIMEZONE', 'Europe/Paris');

if (!defined('GOOGLE_ANALYTICS_ID')) define('GOOGLE_ANALYTICS_ID', '');
if (!defined('FACEBOOK_PIXEL_ID')) define('FACEBOOK_PIXEL_ID', '');

if (!defined('API_RATE_LIMIT')) define('API_RATE_LIMIT', 100);
if (!defined('API_RATE_LIMIT_WINDOW')) define('API_RATE_LIMIT_WINDOW', 3600);

if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 8);
if (!defined('USERNAME_MIN_LENGTH')) define('USERNAME_MIN_LENGTH', 3);

if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('LOGIN_LOCKOUT_DURATION')) define('LOGIN_LOCKOUT_DURATION', 900);

date_default_timezone_set(TIMEZONE);