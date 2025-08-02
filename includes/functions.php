<?php
declare(strict_types=1);

/**
 * تحويل البيانات إلى تنسيق JSON آمن
 * 
 * @param mixed $data البيانات المراد تحويلها
 * @return string النتيجة JSON
 * @throws RuntimeException إذا فشل التحويل
 */
function safe_json_encode($data): string {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('JSON encode error: ' . json_last_error_msg());
    }
    
    return $json;
}

/**
 * إعادة توجيه المستخدم إلى عنوان URL معين
 * 
 * @param string $url عنوان URL للانتقال إليه
 * @param int $statusCode كود حالة HTTP (302 افتراضي)
 */
function redirect(string $url, int $statusCode = 302): void {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * التحقق من أن الطلب هو AJAX
 * 
 * @return bool صحيح إذا كان الطلب AJAX
 */
function is_ajax_request(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * تصفية وإزالة البيانات من المدخلات
 * 
 * @param mixed $data البيانات المراد تصفيتها
 * @param string $type نوع التصفية (خاص|string|int|float|email|url)
 * @return mixed البيانات المصفاة
 */
function sanitize_input($data, string $type = 'string') {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }

    switch ($type) {
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        case 'special':
            return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        case 'string':
        default:
            return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES);
    }
}

/**
 * إنشاء رمز CSRF وحفظه في الجلسة
 * 
 * @return string رمز CSRF
 */
function generate_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();

    return $token;
}

/**
 * التحقق من رمز CSRF
 * 
 * @param string $token الرمز المراد التحقق منه
 * @param int $timeout الوقت الأقصى للرمز بالثواني (3600 افتراضي)
 * @return bool صحيح إذا كان الرمز صالحاً
 */
function verify_csrf_token(string $token, int $timeout = 3600): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || 
        empty($_SESSION['csrf_token_time']) {
        return false;
    }

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }

    if ($_SESSION['csrf_token_time'] + $timeout < time()) {
        return false;
    }

    return true;
}

/**
 * تسجيل رسالة خطأ
 * 
 * @param string $message رسالة الخطأ
 * @param array $context سياق إضافي
 */
function log_error(string $message, array $context = []): void {
    $logEntry = sprintf(
        "[%s] ERROR: %s %s\n",
        date('Y-m-d H:i:s'),
        $message,
        !empty($context) ? json_encode($context) : ''
    );

    file_put_contents(
        LOGS_DIR . '/app_errors.log',
        $logEntry,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * إنشاء QR Code من البيانات
 * 
 * @param string $data البيانات المراد ترميزها
 * @param int $size حجم الصورة (200 افتراضي)
 * @return string بيانات صورة PNG كسلسلة ثنائية
 */
function generate_qr_code(string $data, int $size = 200): string {
    $qrCode = new Endroid\QrCode\QrCode($data);
    $qrCode->setSize($size);
    $qrCode->setMargin(10);
    $qrCode->setEncoding('UTF-8');
    $qrCode->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::HIGH);

    return $qrCode->writeString();
}

/**
 * حساب وقت منقضي بطريقة مقروءة
 * 
 * @param int $timestamp timestamp للوقت المراد حسابه
 * @return string الوقت المنقضي بصيغة مقروءة
 */
function time_elapsed_string(int $timestamp): string {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'الآن';
    }
    
    $intervals = [
        31536000 => 'سنة',
        2592000 => 'شهر',
        604800 => 'أسبوع',
        86400 => 'يوم',
        3600 => 'ساعة',
        60 => 'دقيقة'
    ];
    
    foreach ($intervals as $secs => $str) {
        $d = $diff / $secs;
        if ($d >= 1) {
            $r = round($d);
            return "منذ $r $str" . ($r > 1 ? 's' : '');
        }
    }
}