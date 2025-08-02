<?php
declare(strict_types=1);

/**
 * إنشاء شهادة مستخدم جديدة
 * 
 * @return array تحتوي على بيانات الشهادة
 * @throws Exception إذا فشل إنشاء الشهادة
 */
function generateUserCertificate(): array {
    $config = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "encrypt_key" => true,
        "encrypt_key_cipher" => OPENSSL_CIPHER_AES_256_CBC
    ];

    // إنشاء زوج المفاتيح
    $keyPair = openssl_pkey_new($config);
    if ($keyPair === false) {
        throw new Exception("Failed to generate key pair: " . openssl_error_string());
    }

    // استخراج المفتاح الخاص
    openssl_pkey_export($keyPair, $privateKey, null, $config);

    // الحصول على المفتاح العام
    $publicKey = openssl_pkey_get_details($keyPair)['key'];

    // إنشاء بيانات الشهادة
    $certData = [
        'cert_id' => bin2hex(random_bytes(16)),
        'user_id' => bin2hex(random_bytes(8)),
        'public_key' => $publicKey,
        'private_key' => encryptData($privateKey, ENCRYPTION_KEY),
        'created_at' => time(),
        'expires_at' => time() + (5 * 365 * 24 * 60 * 60), // 5 سنوات
        'fingerprint' => hash('sha3-512', $publicKey)
    ];

    // تحرير الذاكرة
    openssl_free_key($keyPair);

    return $certData;
}

/**
 * حفظ الشهادة في ملف
 * 
 * @param array $certData بيانات الشهادة
 * @return string اسم الملف الذي تم الحفظ فيه
 * @throws Exception إذا فشل حفظ الملف
 */
function saveCertificateToFile(array $certData): string {
    $filename = 'cert_' . $certData['cert_id'] . '.pem';
    $filepath = CERTIFICATES_DIR . '/' . $filename;

    $fileContent = json_encode($certData);
    $encryptedContent = encryptData($fileContent, ENCRYPTION_KEY);

    if (file_put_contents($filepath, $encryptedContent, LOCK_EX) === false) {
        throw new Exception("Failed to save certificate file");
    }

    chmod($filepath, 0600); // إعداد صلاحيات الملف

    return $filename;
}

/**
 * تحميل شهادة من ملف
 * 
 * @param string $filepath مسار ملف الشهادة
 * @return array بيانات الشهادة
 * @throws Exception إذا فشل تحميل الشهادة
 */
function loadCertificateFromFile(string $filepath): array {
    if (!file_exists($filepath)) {
        throw new Exception("Certificate file not found");
    }

    $encryptedContent = file_get_contents($filepath);
    if ($encryptedContent === false) {
        throw new Exception("Cannot read certificate file");
    }

    $fileContent = decryptData($encryptedContent, ENCRYPTION_KEY);
    $certData = json_decode($fileContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid certificate format");
    }

    // التحقق من صلاحية الشهادة
    if ($certData['expires_at'] < time()) {
        throw new Exception("Certificate has expired");
    }

    return $certData;
}

/**
 * التحقق من صحة شهادة المستخدم
 * 
 * @param array $certData بيانات الشهادة
 * @return bool صحيح إذا كانت الشهادة صالحة
 */
function validateCertificate(array $certData): bool {
    try {
        // التحقق من وجود جميع الحقول المطلوبة
        $requiredFields = ['cert_id', 'user_id', 'public_key', 'private_key', 'created_at', 'expires_at', 'fingerprint'];
        foreach ($requiredFields as $field) {
            if (!isset($certData[$field])) {
                return false;
            }
        }

        // التحقق من تاريخ الانتهاء
        if ($certData['expires_at'] < time()) {
            return false;
        }

        // التحقق من تطابق البصمة
        if (hash('sha3-512', $certData['public_key']) !== $certData['fingerprint']) {
            return false;
        }

        // محاولة فك تشفير المفتاح الخاص للتحقق
        $privateKey = decryptData($certData['private_key'], ENCRYPTION_KEY);
        if (openssl_pkey_get_private($privateKey) === false) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * توقيع بيانات باستخدام الشهادة
 * 
 * @param string $data البيانات المراد توقيعها
 * @param array $certData بيانات الشهادة
 * @return string التوقيع في تنسيق base64
 * @throws Exception إذا فشل التوقيع
 */
function signDataWithCertificate(string $data, array $certData): string {
    $privateKey = decryptData($certData['private_key'], ENCRYPTION_KEY);
    $pkey = openssl_pkey_get_private($privateKey);
    
    if ($pkey === false) {
        throw new Exception("Invalid private key: " . openssl_error_string());
    }

    $signature = '';
    if (!openssl_sign($data, $signature, $pkey, OPENSSL_ALGO_SHA512)) {
        throw new Exception("Signing failed: " . openssl_error_string());
    }

    openssl_free_key($pkey);
    return base64_encode($signature);
}

/**
 * التحقق من توقيع البيانات
 * 
 * @param string $data البيانات الأصلية
 * @param string $signature التوقيع
 * @param array $certData بيانات الشهادة
 * @return bool صحيح إذا كان التوقيع صالحاً
 */
function verifySignatureWithCertificate(string $data, string $signature, array $certData): bool {
    $pkey = openssl_pkey_get_public($certData['public_key']);
    if ($pkey === false) {
        throw new Exception("Invalid public key: " . openssl_error_string());
    }

    $result = openssl_verify(
        $data, 
        base64_decode($signature), 
        $pkey, 
        OPENSSL_ALGO_SHA512
    );

    openssl_free_key($pkey);
    
    return $result === 1;
}