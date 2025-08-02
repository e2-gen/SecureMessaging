<?php
/**
 * تذييل الصفحة الرئيسي - يحتوي على عناصر مشتركة لجميع الصفحات
 */
?>
        </div> <!-- نهاية .container -->
    </main>

    <!-- تذييل الصفحة -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-section">
                <h3>عن التطبيق</h3>
                <p>Secure Messenger هو تطبيق مراسلة آمنة بنسبة 100% مع تشفير من طرف إلى طرف.</p>
            </div>
            
            <div class="footer-section">
                <h3>روابط سريعة</h3>
                <ul>
                    <li><a href="/privacy.php">سياسة الخصوصية</a></li>
                    <li><a href="/terms.php">شروط الاستخدام</a></li>
                    <li><a href="/contact.php">اتصل بنا</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>التواصل الاجتماعي</h3>
                <div class="social-links">
                    <a href="#" aria-label="Twitter"><i class="icon-twitter"></i></a>
                    <a href="#" aria-label="Facebook"><i class="icon-facebook"></i></a>
                    <a href="#" aria-label="GitHub"><i class="icon-github"></i></a>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> Secure Messenger. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- مكتبات JS -->
    <script src="/assets/js/chat.js" defer></script>
    
    <!-- تحليلات (اختياري) -->
    <script>
        // يمكن إضافة كود التحليلات هنا
        if (typeof crypto !== 'undefined') {
            // تعطيل بعض ميزات التتبع إذا كان crypto API متاحاً
            Object.defineProperty(window, 'ga', { value: null });
            Object.defineProperty(window, 'fbq', { value: null });
        }
    </script>
</body>
</html>