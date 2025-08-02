<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/encryption.php';

// يجب أن تكون الطلبات عبر AJAX فقط
if (!is_ajax_request()) {
    http_response_code(403);
    exit;
}

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'غير مصرح به']);
    exit;
}

// التحقق من رمز CSRF
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'رمز CSRF غير صالح']);
    exit;
}

// معالجة الإجراءات المختلفة
$action = $_POST['action'] ?? '';
$currentUserId = get_current_user_id();

try {
    switch ($action) {
        case 'send_message':
            // إرسال رسالة جديدة
            $chatId = $_POST['chat_id'] ?? '';
            $message = $_POST['message'] ?? '';
            $isTemporary = isset($_POST['is_temporary']);
            
            if (empty($chatId) || empty($message)) {
                throw new Exception('بيانات غير صالحة');
            }
            
            // التحقق من أن المستخدم مشارك في المحادثة
            $stmt = $pdo->prepare("
                SELECT 1 FROM chat_participants 
                WHERE chat_id = ? AND user_id = ?
            ");
            $stmt->execute([$chatId, $currentUserId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('غير مصرح به');
            }
            
            // تشفير الرسالة
            $encryptedMessage = encryptData($message, $_SESSION['current_chat']['access_key']);
            
            // إدخال الرسالة في قاعدة البيانات
            $stmt = $pdo->prepare("
                INSERT INTO messages 
                (chat_id, sender_id, content, is_temporary, created_at, expires_at) 
                VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MINUTE))
            ");
            $stmt->execute([
                $chatId,
                $currentUserId,
                $encryptedMessage,
                $isTemporary ? 1 : 0,
                $isTemporary ? TEMPORARY_MESSAGE_EXPIRY_MINUTES : (MESSAGE_EXPIRY_DAYS * 24 * 60)
            ]);
            
            echo json_encode(['success' => true, 'message_id' => $pdo->lastInsertId()]);
            break;
            
        case 'get_messages':
            // جلب الرسائل الجديدة
            $chatId = $_POST['chat_id'] ?? '';
            $lastMessageId = (int) ($_POST['last_message_id'] ?? 0);
            
            if (empty($chatId)) {
                throw new Exception('بيانات غير صالحة');
            }
            
            // التحقق من أن المستخدم مشارك في المحادثة
            $stmt = $pdo->prepare("
                SELECT 1 FROM chat_participants 
                WHERE chat_id = ? AND user_id = ?
            ");
            $stmt->execute([$chatId, $currentUserId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('غير مصرح به');
            }
            
            // جلب الرسائل الجديدة
            $stmt = $pdo->prepare("
                SELECT id, sender_id, content, is_temporary, created_at 
                FROM messages 
                WHERE chat_id = ? AND id > ? 
                ORDER BY id ASC
            ");
            $stmt->execute([$chatId, $lastMessageId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // فك تشفير الرسائل
            $decryptedMessages = [];
            foreach ($messages as $msg) {
                try {
                    $decryptedMessages[] = [
                        'id' => $msg['id'],
                        'sender_id' => $msg['sender_id'],
                        'content' => decryptData($msg['content'], $_SESSION['current_chat']['access_key']),
                        'is_temporary' => (bool) $msg['is_temporary'],
                        'created_at' => $msg['created_at']
                    ];
                } catch (Exception $e) {
                    // تخطي الرسائل التي لا يمكن فك تشفيرها
                    continue;
                }
            }
            
            echo json_encode(['success' => true, 'messages' => $decryptedMessages]);
            break;
            
        case 'delete_message':
            // حذف رسالة
            $messageId = (int) ($_POST['message_id'] ?? 0);
            
            if ($messageId <= 0) {
                throw new Exception('بيانات غير صالحة');
            }
            
            // التحقق من أن المرسل هو من يحاول الحذف
            $stmt = $pdo->prepare("
                DELETE FROM messages 
                WHERE id = ? AND sender_id = ?
            ");
            $stmt->execute([$messageId, $currentUserId]);
            
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount() > 0]);
            break;
            
        default:
            throw new Exception('إجراء غير معروف');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}