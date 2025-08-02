<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/message_policy.php';

// هذا الملف يجب أن يتم تنفيذه عبر Cron Job وليس عبر الويب
if (php_sapi_name() !== 'cli') {
    die('هذا الملف يجب تنفيذه عبر سطر الأوامر فقط');
}

// تسجيل وقت البدء
$startTime = microtime(true);
$logMessage = "بدء عملية تنظيف الرسائل في " . date('Y-m-d H:i:s') . "\n";

try {
    // تطبيق سياسة انتهاء الصلاحية على الرسائل
    $deletedCount = apply_message_expiration_policy($pdo);
    $logMessage .= "تم حذف $deletedCount رسالة منتهية الصلاحية\n";
    
    // حذف المحادثات الفارغة
    $stmt = $pdo->query("
        DELETE FROM chats 
        WHERE id IN (
            SELECT c.id FROM chats c
            LEFT JOIN messages m ON c.chat_id = m.chat_id
            WHERE m.id IS NULL
        )
    ");
    $emptyChatsDeleted = $stmt->rowCount();
    $logMessage .= "تم حذف $emptyChatsDeleted محادثة فارغة\n";
    
    // تسجيل وقت الانتهاء
    $executionTime = round(microtime(true) - $startTime, 2);
    $logMessage .= "اكتملت العملية في $executionTime ثانية\n";
    
    // تسجيل النتائج
    file_put_contents(LOGS_DIR . '/cleanup.log', $logMessage, FILE_APPEND);
    
    echo $logMessage;
} catch (PDOException $e) {
    $errorMessage = "خطأ في عملية التنظيف: " . $e->getMessage() . "\n";
    file_put_contents(LOGS_DIR . '/cleanup_errors.log', $errorMessage, FILE_APPEND);
    echo $errorMessage;
    exit(1);
}