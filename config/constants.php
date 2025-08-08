<?php

define('SITE_NAME', 'PrestaCapi');
define('SITE_TAGLINE', 'Votre partenaire financier de confiance depuis 2008');
define('SITE_URL', 'https://prestacapi.com');
define('SITE_EMAIL', 'contact@prestacapi.com');
define('ADMIN_EMAIL', 'admin@prestacapi.com');
define('SUPPORT_EMAIL', 'support@prestacapi.com');

define('MIN_LOAN_AMOUNT', 500);
define('MAX_LOAN_AMOUNT', 50000);
define('MIN_LOAN_DURATION', 6);
define('MAX_LOAN_DURATION', 60);

define('MAX_FILE_SIZE', 5242880);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf']);

define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('DOCUMENTS_DIR', UPLOAD_DIR . 'documents/');
define('BLOG_DIR', UPLOAD_DIR . 'blog/');
define('PARTNERS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/images/partners/');

define('DEFAULT_LANGUAGE', 'fr');
define('SUPPORTED_LANGUAGES', ['fr', 'en', 'es', 'de']);

define('SESSION_TIMEOUT', 7200);
define('REMEMBER_ME_DURATION', 2592000);

define('PAGINATION_LIMIT', 20);
define('BLOG_POSTS_PER_PAGE', 10);
define('TESTIMONIALS_PER_PAGE', 12);

define('MAINTENANCE_MODE', false);
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'no-reply@prestacapi.com');

define('CURRENCY_SYMBOL', '€');
define('CURRENCY_CODE', 'EUR');
define('TIMEZONE', 'Europe/Paris');

define('GOOGLE_ANALYTICS_ID', '');
define('FACEBOOK_PIXEL_ID', '');

define('API_RATE_LIMIT', 100);
define('API_RATE_LIMIT_WINDOW', 3600);

define('PASSWORD_MIN_LENGTH', 8);
define('USERNAME_MIN_LENGTH', 3);

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900);

date_default_timezone_set(TIMEZONE);