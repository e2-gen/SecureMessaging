<?php
declare(strict_types=1);

/**
 * تسجيل الدخول باستخدام شهادة المستخدم
 * 
 * @param array $certData بيانات الشهادة
 * @return bool صحيح إذا نجح تسجيل الدخول
 */
function login_with_certificate(array $certData): bool {
    try {
        // التحقق من صحة الشهادة
        if (!validateCertificate($certData)) {
            return false;
        }

        // بدء الجلسة إذا لم تكن بدأت
        if (session_status() !== PHP_SESSION_ACTIVE) {
            start_secure_session();
        }

        // تخزين بيانات المستخدم في الجلسة
        $_SESSION['user_id'] = $certData['user_id'];
        $_SESSION['cert_id'] = $certData['cert_id'];
        $_SESSION['public_key'] = $certData['public_key'];
        $_SESSION['fingerprint'] = $certData['fingerprint'];
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();

        // تسجيل حدث تسجيل الدخول
        log_audit_event($certData['user_id'], 'login', 'User logged in with certificate');

        return true;
    } catch (Exception $e) {
        log_error("Login failed: " . $e->getMessage());
        return false;
    }
}

/**
 * تسجيل الخروج
 */
function logout(): void {
    // تسجيل حدث تسجيل الخروج إذا كان المستخدم مسجلاً
    if (isset($_SESSION['user_id'])) {
        log_audit_event($_SESSION['user_id'], 'logout', 'User logged out');
    }

    // تنظيف بيانات الجلسة
    $_SESSION = [];
    session_unset();
    session_destroy();

    // حذف كوكيز الجلسة
    setcookie(session_name(), '', time() - 3600, '/');
}

/**
 * التحقق من تسجيل الدخول
 * 
 * @return bool صحيح إذا كان المستخدم مسجلاً
 */
function is_logged_in(): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_secure_session();
    }

    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true &&
           isset($_SESSION['user_id']) &&
           isset($_SESSION['last_activity']) &&
           ($_SESSION['last_activity'] + SESSION_LIFETIME) > time();
}

/**
 * الحصول على معرف المستخدم الحالي
 * 
 * @return string|null معرف المستخدم أو null إذا لم يكن مسجلاً
 */
function get_current_user_id(): ?string {
    return is_logged_in() ? $_SESSION['user_id'] : null;
}

/**
 * تسجيل حدث الأمان
 * 
 * @param string $userId معرف المستخدم
 * @param string $event نوع الحدث
 * @param string $description وصف الحدث
 * @return bool صحيح إذا تم التسجيل بنجاح
 */
function log_audit_event(string $userId, string $event, string $description): bool {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (user_id, event_type, description, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $userId,
            $event,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        log_error("Failed to log audit event: " . $e->getMessage());
        return false;
    }
}

/**
 * التحقق من صلاحيات المستخدم
 * 
 * @param string $userId معرف المستخدم
 * @param string $permission الصلاحية المطلوبة
 * @return bool صحيح إذا كان لديه الصلاحية
 */
function check_user_permission(string $userId, string $permission): bool {
    // في هذا النظام المبسط، جميع المستخدمين لديهم نفس الصلاحيات
    // يمكن توسيع هذه الوظيفة حسب الحاجة
    return true;
}

/**
 * تحديث نشاط المستخدم الأخير
 */
function update_user_activity(): void {
    if (is_logged_in()) {
        $_SESSION['last_activity'] = time();
    }
}

/**
 * التحقق من تثبيت الجلسة
 * 
 * @return bool صحيح إذا كانت الجلسة آمنة
 */
function validate_session(): bool {
    if (!is_logged_in()) {
        return false;
    }

    // التحقق من عنوان IP
    if ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
        return false;
    }

    // التحقق من وكيل المستخدم
    if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
        return false;
    }

    return true;
}

/**
 * إعادة إنشاء معرف الجلسة بشكل دوري
 */
function regenerate_session_id_periodically(): void {
    if (is_logged_in() && rand(1, 100) <= 10) { // 10% فرصة للتجديد
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// التحقق من الجلسة عند كل طلب
if (is_logged_in()) {
    if (!validate_session()) {
        logout();
        redirect('login.php');
    }

    update_user_activity();
    regenerate_session_id_periodically();
}
