<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/certificate.php';

// إذا كان المستخدم مسجلاً، توجيه إلى صفحة الدردشة
if (is_logged_in()) {
    redirect('chat.php');
}

$pageTitle = "تسجيل الدخول";
$error = '';

// معالجة تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['certificate'])) {
    try {
        $certData = validateCertificate($_FILES['certificate']);
        
        if ($certData && login_with_certificate($certData)) {
            redirect('chat.php');
        } else {
            $error = "شهادة غير صالحة أو منتهية الصلاحية";
        }
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء تسجيل الدخول: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>تسجيل الدخول</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="auth-form">
            <div class="form-group">
                <label for="certificate">اختر ملف الشهادة:</label>
                <input type="file" name="certificate" id="certificate" required accept=".pem">
                <small>الرجاء اختيار ملف الشهادة الذي تم تنزيله عند التسجيل</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">تسجيل الدخول</button>
            </div>
            
            <div class="auth-footer">
                <p>ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></p>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>