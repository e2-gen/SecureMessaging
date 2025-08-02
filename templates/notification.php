<?php
/**
 * قالب عرض الإشعارات
 * 
 * @param string $message نص الإشعار
 * @param string $type نوع الإشعار (success, error, warning, info)
 * @param bool $dismissible هل يمكن إغلاق الإشعار؟
 * @param int $autoHide الوقت التلقائي للإخفاء (بالمللي ثانية)، 0 لتعطيل
 */
function display_notification($message, $type = 'info', $dismissible = true, $autoHide = 5000) {
    $classes = "notification notification-$type";
    if ($dismissible) {
        $classes .= " notification-dismissible";
    }
    ?>
    <div class="<?php echo $classes; ?>" role="alert" <?php if ($autoHide > 0) echo 'data-auto-hide="' . $autoHide . '"'; ?>>
        <div class="notification-content">
            <?php if ($type === 'success'): ?>
                <i class="icon-check-circle"></i>
            <?php elseif ($type === 'error'): ?>
                <i class="icon-alert-circle"></i>
            <?php elseif ($type === 'warning'): ?>
                <i class="icon-alert-triangle"></i>
            <?php else: ?>
                <i class="icon-info"></i>
            <?php endif; ?>
            
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        
        <?php if ($dismissible): ?>
            <button class="notification-close" aria-label="إغلاق">
                <i class="icon-x"></i>
            </button>
        <?php endif; ?>
    </div>
    
    <script>
    // إدارة إغلاق الإشعارات
    document.addEventListener('DOMContentLoaded', function() {
        const notifications = document.querySelectorAll('.notification');
        
        notifications.forEach(notification => {
            // إغلاق عند النقر على الزر
            const closeBtn = notification.querySelector('.notification-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                });
            }
            
            // الإغلاق التلقائي
            const autoHide = notification.dataset.autoHide;
            if (autoHide && autoHide > 0) {
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }, parseInt(autoHide));
            }
        });
    });
    </script>
    <?php
}

/**
 * حفظ إشعار في الجلسة لعرضه لاحقاً
 */
function set_session_notification($message, $type = 'info') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * عرض إشعارات الجلسة إذا وجدت
 */
function display_session_notifications() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!empty($_SESSION['notification'])) {
        display_notification(
            $_SESSION['notification']['message'],
            $_SESSION['notification']['type'],
            true,
            5000
        );
        
        unset($_SESSION['notification']);
    }
}