<?php
declare(strict_types=1);

/**
 * تشفير البيانات باستخدام AES-256-GCM
 * 
 * @param string $data البيانات المراد تشفيرها
 * @param string $key مفتاح التشفير (يجب أن يكون 32 بايت)
 * @return string البيانات المشفرة مع IV و TAG في تنسيق base64
 * @throws Exception إذا فشل التشفير
 */
function encryptData(string $data, string $key): string {
    if (strlen($key) !== 32) {
        throw new Exception("Encryption key must be 32 bytes long");
    }

    $iv = random_bytes(ENCRYPTION_IV_LENGTH);
    $encrypted = openssl_encrypt(
        $data, 
        ENCRYPTION_CIPHER, 
        $key, 
        OPENSSL_RAW_DATA, 
        $iv, 
        $tag
    );

    if ($encrypted === false) {
        throw new Exception("Encryption failed: " . openssl_error_string());
    }

    return base64_encode($iv . $tag . $encrypted);
}

/**
 * فك تشفير البيانات المشفرة باستخدام AES-256-GCM
 * 
 * @param string $encryptedData البيانات المشفرة مع IV و TAG
 * @param string $key مفتاح التشفير المستخدم في التشفير
 * @return string البيانات الأصلية
 * @throws Exception إذا فشل فك التشفير
 */
function decryptData(string $encryptedData, string $key): string {
    if (strlen($key) !== 32) {
        throw new Exception("Decryption key must be 32 bytes long");
    }

    $data = base64_decode($encryptedData);
    if ($data === false) {
        throw new Exception("Invalid base64 data");
    }

    $iv = substr($data, 0, ENCRYPTION_IV_LENGTH);
    $tag = substr($data, ENCRYPTION_IV_LENGTH, 16);
    $encrypted = substr($data, ENCRYPTION_IV_LENGTH + 16);

    $decrypted = openssl_decrypt(
        $encrypted, 
        ENCRYPTION_CIPHER, 
        $key, 
        OPENSSL_RAW_DATA, 
        $iv, 
        $tag
    );

    if ($decrypted === false) {
        throw new Exception("Decryption failed: " . openssl_error_string());
    }

    return $decrypted;
}

/**
 * إنشاء توقيع HMAC للتحقق من سلامة البيانات
 * 
 * @param string $data البيانات المراد توقيعها
 * @param string $key مفتاح التوقيع
 * @return string التوقيع في تنسيق hex
 */
function createHmacSignature(string $data, string $key): string {
    return hash_hmac('sha3-256', $data, $key);
}

/**
 * التحقق من صحة توقيع HMAC
 * 
 * @param string $data البيانات الأصلية
 * @param string $signature التوقيع المقدم
 * @param string $key مفتاح التوقيع
 * @return bool صحيح إذا كان التوقيع صالحاً
 */
function verifyHmacSignature(string $data, string $signature, string $key): bool {
    $calculatedSignature = createHmacSignature($data, $key);
    return hash_equals($calculatedSignature, $signature);
}

/**
 * تشفير ملف كامل
 * 
 * @param string $inputFile مسار الملف المدخل
 * @param string $outputFile مسار الملف المشفر
 * @param string $key مفتاح التشفير
 * @throws Exception إذا فشل تشفير الملف
 */
function encryptFile(string $inputFile, string $outputFile, string $key): void {
    $iv = random_bytes(ENCRYPTION_IV_LENGTH);
    $stream = fopen($inputFile, 'rb');
    $dest = fopen($outputFile, 'wb');

    if (!$stream || !$dest) {
        throw new Exception("Cannot open files for encryption");
    }

    fwrite($dest, $iv); // كتابة IV في بداية الملف

    $options = OPENSSL_RAW_DATA;
    $context = openssl_cipher_iv_length(ENCRYPTION_CIPHER);
    $tag = '';

    if (!openssl_encrypt_stream($stream, $dest, ENCRYPTION_CIPHER, $key, $options, $iv, $tag)) {
        throw new Exception("File encryption failed: " . openssl_error_string());
    }

    fwrite($dest, $tag); // إضافة TAG في نهاية الملف
    fclose($stream);
    fclose($dest);
}