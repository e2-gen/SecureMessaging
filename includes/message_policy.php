<?php
declare(strict_types=1);

/**
 * تطبيق سياسة انتهاء صلاحية الرسائل
 * 
 * @param PDO $pdo كائن PDO للاتصال بقاعدة البيانات
 * @return int عدد الرسائل المحذوفة
 */
function apply_message_expiration_policy(PDO $pdo): int {
    try {
        // حذف الرسائل المؤقتة المنتهية الصلاحية
        $stmt = $pdo->prepare("
            DELETE FROM messages 
            WHERE is_temporary = 1 
            AND created_at < DATE_SUB(NOW(), INTERVAL :temp_expiry MINUTE)
        ");
        $stmt->bindValue(':temp_expiry', TEMPORARY_MESSAGE_EXPIRY_MINUTES, PDO::PARAM_INT);
        $stmt->execute();
        $tempDeleted = $stmt->rowCount();

        // حذف الرسائل العادية المنتهية الصلاحية
        $stmt = $pdo->prepare("
            DELETE FROM messages 
            WHERE is_temporary = 0 
            AND created_at < DATE_SUB(NOW(), INTERVAL :normal_expiry DAY)
        ");
        $stmt->bindValue(':normal_expiry', MESSAGE_EXPIRY_DAYS, PDO::PARAM_INT);
        $stmt->execute();
        $normalDeleted = $stmt->rowCount();

        return $tempDeleted + $normalDeleted;
    } catch (PDOException $e) {
        log_error("Failed to apply message expiration policy: " . $e->getMessage());
        return 0;
    }
}

/**
 * التحقق من صلاحية الرسالة للعرض
 * 
 * @param array $message بيانات الرسالة
 * @return bool صحيح إذا كانت الرسالة صالحة للعرض
 */
function is_message_valid(array $message): bool {
    // التحقق من وجود البيانات الأساسية
    if (!isset($message['id'], $message['content'], $message['created_at'], $message['is_temporary'])) {
        return false;
    }

    // التحقق من انتهاء الصلاحية
    $createdAt = strtotime($message['created_at']);
    $expirySeconds = $message['is_temporary'] 
        ? TEMPORARY_MESSAGE_EXPIRY_MINUTES * 60 
        : MESSAGE_EXPIRY_DAYS * 24 * 60 * 60;

    return ($createdAt + $expirySeconds) > time();
}

/**
 * حذف الرسائل المقروءة
 * 
 * @param PDO $pdo كائن PDO للاتصال بقاعدة البيانات
 * @param array $messageIds مصفوفة بمعرفات الرسائل
 * @return int عدد الرسائل المحذوفة
 */
function delete_read_messages(PDO $pdo, array $messageIds): int {
    if (empty($messageIds)) {
        return 0;
    }

    try {
        // إنشاء مكانس للاستعلام (للمساعدة في منع SQL Injection)
        $placeholders = rtrim(str_repeat('?,', count($messageIds)), ',');
        
        $stmt = $pdo->prepare("
            DELETE FROM messages 
            WHERE id IN ($placeholders) 
            AND is_temporary = 1
        ");
        $stmt->execute($messageIds);
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        log_error("Failed to delete read messages: " . $e->getMessage());
        return 0;
    }
}

/**
 * جدولة مهمة تنظيف الرسائل
 */
function schedule_message_cleanup(): void {
    // في تطبيق حقيقي، هذا سيكون مهمة Cron
    // هنا ننفذها مباشرة كمثال
    global $pdo;
    apply_message_expiration_policy($pdo);
}

/**
 * تسجيل وقت مشاهدة الرسالة
 * 
 * @param PDO $pdo كائن PDO للاتصال بقاعدة البيانات
 * @param int $messageId معرف الرسالة
 * @param string $userId معرف المستخدم
 * @return bool صحيح إذا تم التسجيل بنجاح
 */
function log_message_view(PDO $pdo, int $messageId, string $userId): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO message_views 
            (message_id, user_id, viewed_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = NOW()
        ");
        return $stmt->execute([$messageId, $userId]);
    } catch (PDOException $e) {
        log_error("Failed to log message view: " . $e->getMessage());
        return false;
    }
}

/**
 * التحقق مما إذا كانت الرسالة قد شوهدت من قبل المستخدم
 * 
 * @param PDO $pdo كائن PDO للاتصال بقاعدة البيانات
 * @param int $messageId معرف الرسالة
 * @param string $userId معرف المستخدم
 * @return bool صحيح إذا كانت الرسالة قد شوهدت
 */
function is_message_viewed(PDO $pdo, int $messageId, string $userId): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT 1 FROM message_views 
            WHERE message_id = ? AND user_id = ?
        ");
        $stmt->execute([$messageId, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        log_error("Failed to check message view: " . $e->getMessage());
        return false;
    }
}