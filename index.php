<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// إذا كان المستخدم مسجلاً، توجيه إلى صفحة الدردشة
if (is_logged_in()) {
    redirect('chat.php');
}

// تعيين عنوان الصفحة
$pageTitle = "Secure Messenger - مراسلة آمنة";

// تضمين رأس الصفحة
include 'templates/header.php';
?>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>مراسلة آمنة بنسبة 100%</h1>
            <p>تطبيق مراسلة مشفر من طرف إلى طرف لا يخزن أي بيانات شخصية</p>
            
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">إنشاء حساب جديد</a>
                <a href="login.php" class="btn btn-secondary">تسجيل الدخول</a>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="/assets/images/secure-chat.png" alt="Secure Chat Illustration">
        </div>
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2>مميزات التطبيق</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="icon-lock"></i>
                </div>
                <h3>تشفير من طرف إلى طرف</h3>
                <p>جميع رسائلك مشفرة ولا يمكن لأي شخص قراءتها غيرك أنت والمرسل إليه</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="icon-clock"></i>
                </div>
                <h3>رسائل مؤقتة</h3>
                <p>إمكانية إرسال رسائل تختفي بعد قراءتها أو بعد وقت محدد</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="icon-user-x"></i>
                </div>
                <h3>لا يوجد تسجيل</h3>
                <p>لا نحتاج إلى بريدك الإلكتروني أو رقم هاتفك، فقط شهادة مشفرة على جهازك</p>
            </div>
        </div>
    </div>
</div>

<?php
// تضمين تذييل الصفحة
include 'templates/footer.php';