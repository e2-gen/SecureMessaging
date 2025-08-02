# Secure Messenger

تطبيق مراسلة آمن بنسبة 100% مع تشفير من طرف إلى طرف ولا يتطلب أي معلومات شخصية للتسجيل.

## المميزات

- تشفير من طرف إلى طرف لجميع الرسائل
- لا يوجد تسجيل - لا بريد إلكتروني ولا رقم هاتف
- رسائل مؤقتة تختفي بعد القراءة أو بعد وقت محدد
- روابط دعوة لمرة واحدة للمحادثات
- لا يتم تخزين أي بيانات شخصية على الخادم

## متطلبات التشغيل

- خادم ويب (Apache أو Nginx)
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- OpenSSL extension

## التثبيت

1. استنسخ المستودع:
   ```bash
   git clone https://github.com/yourusername/secure-messenger.git
   ```

2. قم بتعيين أذونات المجلدات:
   ```bash
   chmod -R 750 storage/ logs/
   chmod 640 .env
   ```

3. قم بتثبيت dependencies عبر Composer:
   ```bash
   composer install
   ```

4. قم بإنشاء قاعدة البيانات واستورد المخطط:
   ```sql
   CREATE DATABASE secure_messenger;
   USE secure_messenger;
   SOURCE database/schema.sql;
   ```

5. قم بتعيين إعدادات الخادم في ملف `.env`:
   ```ini
   DB_HOST=localhost
   DB_NAME=secure_messenger
   DB_USER=username
   DB_PASS=password
   ENCRYPTION_KEY=your-256-bit-encryption-key
   ```

## جدولة المهام

لتنظيف الرسائل المنتهية الصلاحية تلقائياً، أضف مهمة Cron:

```bash
0 3 * * * /usr/bin/php /path/to/secure-messenger/message_cleanup.php
```

## الأمان

- جميع الرسائل مشفرة قبل التخزين
- لا يمكن للخادم قراءة محتوى الرسائل
- جلسات آمنة مع HttpOnly و Secure cookies
- حماية من CSRF و XSS

## الرخصة

هذا المشروع مرخص تحت [MIT License](LICENSE).