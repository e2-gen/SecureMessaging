<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// تسجيل الخروج
logout();

// توجيه إلى الصفحة الرئيسية مع رسالة
set_session_notification("تم تسجيل الخروج بنجاح", "success");
redirect('index.php');