<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/certificate.php';

// إذا كان المستخدم مسجلاً، توجيه إلى صفحة الدردشة
if (is_logged_in()) {
    redirect('chat.php');
}

$pageTitle = "إنشاء حساب جديد";
$error = '';

// معالجة إنشاء الحساب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // إنشاء شهادة جديدة
        $certData = generateUserCertificate();
        $filename = saveCertificateToFile($certData);
        
        // إعداد ملف للتنزيل
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize(CERTIFICATES_DIR.'/'.$filename));
        readfile(CERTIFICATES_DIR.'/'.$filename);
        
        // بعد التنزيل، احذف الملف من السيرفر
        unlink(CERTIFICATES_DIR.'/'.$filename);
        
        // تسجيل الدخول تلقائياً
        if (login_with_certificate($certData)) {
            redirect('chat.php');
        }
        
        exit;
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء إنشاء الحساب: " . $e->getMessage();
    }
}

include 'templates/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>إنشاء حساب جديد</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <p>سيتم إنشاء شهادة مشفرة وتنزيلها على جهازك. هذه الشهادة هي طريقة تسجيل دخولك الوحيدة.</p>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">إنشاء حساب وتنزيل الشهادة</button>
            </div>
            
            <div class="auth-footer">
                <p>هل لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>