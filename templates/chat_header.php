<?php
/**
 * رأس مخصص لصفحات الدردشة
 */
if (!isset($chatTitle)) {
    $chatTitle = 'محادثة جديدة';
}
?>
<header class="chat-header">
    <div class="header-left">
        <button class="back-button" onclick="window.history.back()">
            <i class="icon-arrow-left"></i>
        </button>
        <h1><?php echo htmlspecialchars($chatTitle); ?></h1>
    </div>
    
    <div class="header-right">
        <?php if (isset($chatId)): ?>
            <div class="chat-actions">
                <button id="invite-button" title="دعوة أشخاص">
                    <i class="icon-user-plus"></i>
                </button>
                <button id="qr-button" title="مشاركة عبر QR Code">
                    <i class="icon-qrcode"></i>
                </button>
                <button id="settings-button" title="إعدادات المحادثة">
                    <i class="icon-settings"></i>
                </button>
            </div>
            
            <!-- قائمة إعدادات المحادثة (مخفية) -->
            <div id="chat-settings-menu" class="dropdown-menu">
                <ul>
                    <li><a href="#" id="clear-chat">مسح المحادثة</a></li>
                    <li><a href="#" id="leave-chat">مغادرة المحادثة</a></li>
                    <li><a href="#" id="export-chat">تصدير المحادثة</a></li>
                </ul>
            </div>
            
            <!-- نافذة مشاركة QR Code (مخفية) -->
            <div id="qr-modal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>مشاركة المحادثة</h2>
                    <div id="qr-code-container"></div>
                    <p>امسح رمز QR للانضمام إلى المحادثة</p>
                    <input type="text" id="chat-link" value="<?php echo htmlspecialchars(get_chat_invite_link($chatId)); ?>" readonly>
                    <button id="copy-link-button">نسخ الرابط</button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>

<script>
// تهيئة أحداث واجهة الدردشة
document.addEventListener('DOMContentLoaded', function() {
    // فتح/إغلاق إعدادات المحادثة
    const settingsBtn = document.getElementById('settings-button');
    const settingsMenu = document.getElementById('chat-settings-menu');
    
    if (settingsBtn && settingsMenu) {
        settingsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            settingsMenu.style.display = settingsMenu.style.display === 'block' ? 'none' : 'block';
        });
        
        // إغلاق القائمة عند النقر خارجها
        document.addEventListener('click', function() {
            settingsMenu.style.display = 'none';
        });
    }
    
    // عرض نافذة QR Code
    const qrBtn = document.getElementById('qr-button');
    const qrModal = document.getElementById('qr-modal');
    
    if (qrBtn && qrModal) {
        qrBtn.addEventListener('click', function() {
            qrModal.style.display = 'block';
            generateQRCode();
        });
        
        // إغلاق النافذة
        const closeBtn = qrModal.querySelector('.close');
        closeBtn.addEventListener('click', function() {
            qrModal.style.display = 'none';
        });
    }
    
    // نسخ رابط المحادثة
    const copyBtn = document.getElementById('copy-link-button');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const linkInput = document.getElementById('chat-link');
            linkInput.select();
            document.execCommand('copy');
            showNotification('تم نسخ الرابط بنجاح', 'success');
        });
    }
});

// إنشاء QR Code
function generateQRCode() {
    const chatLink = document.getElementById('chat-link').value;
    const container = document.getElementById('qr-code-container');
    
    // مسح المحتوى السابق
    container.innerHTML = '';
    
    // إنشاء QR Code جديد
    new QRCode(container, {
        text: chatLink,
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}
</script>