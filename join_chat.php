<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// إذا لم يكن المستخدم مسجلاً، توجيه إلى تسجيل الدخول
if (!is_logged_in()) {
    redirect('login.php');
}

$pageTitle = "الانضمام إلى محادثة";
$currentUserId = get_current_user_id();
$error = '';

// معالجة الانضمام إلى المحادثة
if (isset($_GET['chat']) && isset($_GET['token'])) {
    $chatId = $_GET['chat'];
    $token = $_GET['token'];
    
    try {
        // التحقق من صحة الرمز المميز لمرة واحدة
        $stmt = $pdo->prepare("
            SELECT access_key FROM chats 
            WHERE chat_id = ? AND one_time_token = ?
        ");
        $stmt->execute([$chatId, $token]);
        
        if ($stmt->rowCount() === 1) {
            $accessKey = $stmt->fetchColumn();
            
            // تعطيل الرمز المميز لمرة واحدة بعد الاستخدام
            $stmt = $pdo->prepare("
                UPDATE chats SET one_time_token = NULL 
                WHERE chat_id = ?
            ");
            $stmt->execute([$chatId]);
            
            // إضافة المستخدم إلى المحادثة
            $stmt = $pdo->prepare("
                INSERT INTO chat_participants 
                (chat_id, user_id, joined_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$chatId, $currentUserId]);
            
            // تعيين المحادثة الحالية في الجلسة
            $_SESSION['current_chat'] = [
                'chat_id' => $chatId,
                'access_key' => $accessKey
            ];
            
            // توجيه إلى صفحة الدردشة
            redirect('chat.php');
        } else {
            $error = "رابط المحادثة غير صالح أو مستخدم بالفعل";
        }
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء الانضمام إلى المحادثة: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="join-chat-container">
    <div class="join-chat-card">
        <h2>الانضمام إلى محادثة</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="join-instructions">
            <p>للالتحاق بمحادثة، يرجى استخدام الرابط الذي تم إرساله إليك من قبل صاحب المحادثة.</p>
            <p>الروابط الصالحة تبدو كالتالي:</p>
            <code><?php echo get_full_url('join_chat.php?chat=abc123&token=xyz456'); ?></code>
        </div>
        
        <div class="alternative-actions">
            <a href="create_chat.php" class="btn btn-secondary">إنشاء محادثة جديدة</a>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>