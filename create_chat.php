<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// إذا لم يكن المستخدم مسجلاً، توجيه إلى تسجيل الدخول
if (!is_logged_in()) {
    redirect('login.php');
}

$pageTitle = "إنشاء محادثة جديدة";
$currentUserId = get_current_user_id();

// معالجة إنشاء المحادثة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $chatId = bin2hex(random_bytes(16));
        $accessKey = bin2hex(random_bytes(32));
        $oneTimeToken = bin2hex(random_bytes(32));
        
        // تخزين المحادثة في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO chats 
            (chat_id, access_key, one_time_token, creator_id, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$chatId, $accessKey, $oneTimeToken, $currentUserId]);
        
        // تعيين المحادثة الحالية في الجلسة
        $_SESSION['current_chat'] = [
            'chat_id' => $chatId,
            'access_key' => $accessKey
        ];
        
        // إنشاء رابط لمرة واحدة
        $oneTimeLink = get_full_url("join_chat.php?chat=$chatId&token=$oneTimeToken");
        
        // عرض صفحة المشاركة
        $pageTitle = "مشاركة المحادثة";
        include 'templates/header.php';
        include 'templates/chat_header.php';
        ?>
        
        <div class="share-chat-container">
            <h3>تم إنشاء المحادثة بنجاح</h3>
            <p>شارك الرابط أدناه مع الشخص الذي تريد الدردشة معه:</p>
            
            <div class="share-link-box">
                <input type="text" id="chat-link" value="<?php echo htmlspecialchars($oneTimeLink); ?>" readonly>
                <button id="copy-link-btn">نسخ</button>
            </div>
            
            <div class="qr-code-container">
                <div id="qr-code"></div>
                <p>أو استخدم رمز QR للمشاركة</p>
            </div>
            
            <div class="security-notice">
                <i class="icon-lock"></i>
                <p>هذا الرابط صالح لاستخدام واحد فقط وسينتهي بعد الانضمام إلى المحادثة</p>
            </div>
        </div>
        
        <script src="/assets/js/qrcode.min.js"></script>
        <script>
        // إنشاء QR Code
        new QRCode(document.getElementById("qr-code"), {
            text: "<?php echo $oneTimeLink; ?>",
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // نسخ الرابط
        document.getElementById('copy-link-btn').addEventListener('click', function() {
            const linkInput = document.getElementById('chat-link');
            linkInput.select();
            document.execCommand('copy');
            
            // عرض إشعار
            const notification = document.createElement('div');
            notification.className = 'notification notification-success';
            notification.textContent = 'تم نسخ الرابط بنجاح';
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        });
        </script>
        
        <?php
        include 'templates/footer.php';
        exit;
    } catch (PDOException $e) {
        $error = "حدث خطأ أثناء إنشاء المحادثة: " . $e->getMessage();
    }
}

include 'templates/header.php';
include 'templates/chat_header.php';
?>

<div class="create-chat-container">
    <h3>إنشاء محادثة جديدة</h3>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="create-chat-form">
        <div class="form-group">
            <label>
                <input type="checkbox" name="secure_chat" checked>
                <span>محادثة آمنة (تشفير من طرف إلى طرف)</span>
            </label>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="self_destruct">
                <span>محادثة مؤقتة (سيتم حذفها بعد 24 ساعة)</span>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">إنشاء محادثة</button>
        </div>
    </form>
</div>

<?php include 'templates/footer.php'; ?>