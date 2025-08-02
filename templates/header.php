<?php
/**
 * رأس الصفحة الرئيسي - يحتوي على عناصر مشتركة لجميع الصفحات
 */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Secure Messenger'); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- رؤوس الأمان -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;">
    <meta http-equiv="Strict-Transport-Security" content="max-age=63072000; includeSubDomains; preload">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="Referrer-Policy" content="no-referrer">
    
    <!-- مكتبات JS الأساسية -->
    <script src="/assets/js/crypto.js" defer></script>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <header class="main-header">
        <div class="container">
            <div class="logo">
                <a href="/">
                    <img src="/assets/images/logo.png" alt="Secure Messenger Logo">
                    <span>Secure Messenger</span>
                </a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <?php if (is_logged_in()): ?>
                        <li><a href="/chat.php">المحادثات</a></li>
                        <li><a href="/create_chat.php">محادثة جديدة</a></li>
                        <li><a href="/logout.php">تسجيل الخروج</a></li>
                    <?php else: ?>
                        <li><a href="/login.php">تسجيل الدخول</a></li>
                        <li><a href="/register.php">إنشاء حساب</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- محتوى الصفحة الرئيسي -->
    <main class="main-content">
        <div class="container">
            <!-- إشعارات النظام -->
            <?php if (!empty($_SESSION['notification'])): ?>
                <div class="notification <?php echo $_SESSION['notification']['type']; ?>">
                    <?php echo htmlspecialchars($_SESSION['notification']['message']); ?>
                </div>
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
