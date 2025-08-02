<?php
declare(strict_types=1);

/**
 * تعيين رؤوس أمان HTTP
 */
function set_security_headers(): void {
    // سياسة أمان المحتوى (CSP)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; media-src 'self'; object-src 'none'; frame-src 'none'; base-uri 'self'; form-action 'self'");

    // HTTP Strict Transport Security (HSTS)
    header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");

    // X-Frame-Options
    header("X-Frame-Options: DENY");

    // X-Content-Type-Options
    header("X-Content-Type-Options: nosniff");

    // Referrer-Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Permissions-Policy
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

    // X-XSS-Protection (للدعم القديم)
    header("X-XSS-Protection: 1; mode=block");

    // Feature-Policy (للدعم القديم)
    header("Feature-Policy: geolocation 'none'; microphone 'none'; camera 'none'");
}

/**
 * تعيين رؤوس التحكم في الذاكرة المؤقتة
 */
function set_cache_headers(): void {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

/**
 * بدء جلسة آمنة
 */
function start_secure_session(): void {
    // إعدادات الجلسة الآمنة
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.sid_length', '128');
    ini_set('session.sid_bits_per_character', '6');
    ini_set('session.hash_function', 'sha256');
    
    // إعداد اسم الجلسة المخصص
    session_name(SESSION_NAME);
    
    // بدء الجلسة
    session_start();
    
    // تجديد معرف الجلسة لمنع تثبيت الجلسة
    if (rand(1, 100) <= 10) { // 10% فرصة لتجديد الجلسة
        session_regenerate_id(true);
    }
    
    // التحقق من تثبيت الجلسة
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    } else {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_unset();
            session_destroy();
            start_secure_session();
        }
    }
}

/**
 * منع هجمات التصيد عبر الموقع (Clickjacking)
 */
function prevent_clickjacking(): void {
    header('X-Frame-Options: DENY');
}

/**
 * منع هجمات MIME Sniffing
 */
function prevent_mime_sniffing(): void {
    header('X-Content-Type-Options: nosniff');
}

/**
 * تطبيق جميع إجراءات الأمان
 */
function apply_all_security_measures(): void {
    set_security_headers();
    set_cache_headers();
    start_secure_session();
    prevent_clickjacking();
    prevent_mime_sniffing();
}

// تطبيق إجراءات الأمان عند تحميل الصفحة
apply_all_security_measures();