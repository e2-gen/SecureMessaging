<?php
declare(strict_types=1);

// إعدادات التطبيق الأساسية
define('APP_NAME', 'SecureMessenger');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production'); // يمكن أن تكون 'development' أو 'testing'

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'secure_messenger_db');
define('DB_USER', 'secure_messenger_user');
define('DB_PASS', 'S3cur3P@ssw0rd!2023');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// إعدادات التشفير
define('ENCRYPTION_KEY', bin2hex(random_bytes(32)));
define('ENCRYPTION_CIPHER', 'aes-256-gcm');
define('ENCRYPTION_IV_LENGTH', openssl_cipher_iv_length(ENCRYPTION_CIPHER));

// إعدادات الجلسة
define('SESSION_NAME', 'SECURE_MSG_SESS');
define('SESSION_LIFETIME', 3600); // ثانية واحدة
define('SESSION_SSL', true);
define('SESSION_HTTP_ONLY', true);
define('SESSION_SAME_SITE', 'Strict');

// إعدادات الرسائل
define('MESSAGE_EXPIRY_DAYS', 7); // الرسائل العادية
define('TEMPORARY_MESSAGE_EXPIRY_MINUTES', 60); // الرسائل المؤقتة

// مسارات الملفات
define('CERTIFICATES_DIR', __DIR__ . '/../certs');
define('TEMPLATES_DIR', __DIR__ . '/../templates');
define('LOGS_DIR', __DIR__ . '/../logs');

// إعدادات البريد الإلكتروني (إذا لزم الأمر)
define('SMTP_HOST', 'smtp.secure.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'no-reply@secure.example.com');
define('SMTP_PASS', 'EmailP@ssw0rd!2023');

// تمكين وضع تصحيح الأخطاء في بيئة التطوير
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// إنشاء دليل الشهادات إذا لم يكن موجوداً
if (!file_exists(CERTIFICATES_DIR)) {
    mkdir(CERTIFICATES_DIR, 0700, true);
}

// إنشاء دليل السجلات إذا لم يكن موجوداً
if (!file_exists(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0700, true);
}

// تسجيل الأخطاء
ini_set('log_errors', '1');
ini_set('error_log', LOGS_DIR . '/error.log');