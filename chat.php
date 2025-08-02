<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// إذا لم يكن المستخدم مسجلاً، توجيه إلى تسجيل الدخول
if (!is_logged_in()) {
    redirect('login.php');
}

$pageTitle = "الدردشة";
$currentUserId = get_current_user_id();

// إذا لم يكن هناك محادثة حالية، توجيه لإنشاء واحدة
if (!isset($_SESSION['current_chat'])) {
    redirect('create_chat.php');
}

$chatId = $_SESSION['current_chat']['chat_id'];
$accessKey = $_SESSION['current_chat']['access_key'];

include 'templates/header.php';
include 'templates/chat_header.php';
?>

<div class="chat-container">
    <div class="messages-container" id="messages-container">
        <!-- سيتم تحميل الرسائل هنا عبر JavaScript -->
    </div>
    
    <div class="message-input-area">
        <div class="message-options">
            <label class="temp-message-toggle">
                <input type="checkbox" id="temp-message-checkbox">
                <span>رسالة مؤقتة</span>
            </label>
        </div>
        
        <input type="text" id="message-input" placeholder="اكتب رسالة هنا..." autocomplete="off">
        <button id="send-button">
            <i class="icon-send"></i>
        </button>
    </div>
</div>

<script>
// تهيئة متغيرات الدردشة
const chatId = '<?php echo $chatId; ?>';
const currentUserId = '<?php echo $currentUserId; ?>';
const accessKey = '<?php echo $accessKey; ?>';
let lastMessageId = 0;

// تهيئة الدردشة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    loadMessages();
    startMessagePolling();
    
    // تهيئة WebSocket إذا كان مدعوماً
    if (typeof WebSocket !== 'undefined') {
        initWebSocket();
    }
});

// باقي كود JavaScript للدردشة
</script>

<?php include 'templates/footer.php'; ?>