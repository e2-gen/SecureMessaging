// تهيئة مفاتيح التشفير
let cryptoKeys = {
    publicKey: null,
    privateKey: null
};

// تهيئة التشفير عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', async function() {
    await initializeCrypto();
});

// تهيئة مفاتيح التشفير
async function initializeCrypto() {
    try {
        // جلب المفاتيح من تخزين المتصفح المحلي
        const storedKeys = localStorage.getItem('cryptoKeys');
        
        if (storedKeys) {
            cryptoKeys = JSON.parse(storedKeys);
        } else {
            // إنشاء مفاتيح جديدة إذا لم تكن موجودة
            const keyPair = await window.crypto.subtle.generateKey(
                {
                    name: "RSA-OAEP",
                    modulusLength: 2048,
                    publicExponent: new Uint8Array([0x01, 0x00, 0x01]),
                    hash: "SHA-256",
                },
                true,
                ["encrypt", "decrypt"]
            );
            
            // تصدير المفاتيح لتخزينها
            const [publicKey, privateKey] = await Promise.all([
                window.crypto.subtle.exportKey("jwk", keyPair.publicKey),
                window.crypto.subtle.exportKey("jwk", keyPair.privateKey)
            ]);
            
            cryptoKeys = { publicKey, privateKey };
            localStorage.setItem('cryptoKeys', JSON.stringify(cryptoKeys));
        }
        
        // تحميل المفتاح العام للطرف الآخر إذا كان معروفاً
        loadRecipientPublicKey();
        
    } catch (error) {
        console.error('Failed to initialize crypto:', error);
    }
}

// تشفير الرسالة
async function encryptMessage(message) {
    try {
        // تحويل الرسالة إلى Uint8Array
        const encoder = new TextEncoder();
        const encodedMessage = encoder.encode(message);
        
        // استيراد المفتاح العام للمستلم
        const recipientPublicKey = await getRecipientPublicKey();
        
        // تشفير الرسالة
        const encrypted = await window.crypto.subtle.encrypt(
            {
                name: "RSA-OAEP"
            },
            recipientPublicKey,
            encodedMessage
        );
        
        // تحويل النتيجة إلى Base64 للنقل
        return arrayBufferToBase64(encrypted);
    } catch (error) {
        console.error('Encryption failed:', error);
        throw error;
    }
}

// فك تشفير الرسالة
async function decryptMessage(encryptedMessage) {
    try {
        // تحويل Base64 إلى ArrayBuffer
        const encryptedData = base64ToArrayBuffer(encryptedMessage);
        
        // استيراد المفتاح الخاص
        const privateKey = await window.crypto.subtle.importKey(
            "jwk",
            cryptoKeys.privateKey,
            {
                name: "RSA-OAEP",
                hash: "SHA-256"
            },
            true,
            ["decrypt"]
        );
        
        // فك التشفير
        const decrypted = await window.crypto.subtle.decrypt(
            {
                name: "RSA-OAEP"
            },
            privateKey,
            encryptedData
        );
        
        // تحويل النتيجة إلى نص
        const decoder = new TextDecoder();
        return decoder.decode(decrypted);
    } catch (error) {
        console.error('Decryption failed:', error);
        throw error;
    }
}

// الحصول على المفتاح العام للمستلم
async function getRecipientPublicKey() {
    // في تطبيق حقيقي، هذا سيتم جلبها من الخادم
    const recipientId = document.getElementById('recipient-id').value;
    
    try {
        const response = await fetch(`/api/public_key?user_id=${recipientId}`);
        const publicKeyJwk = await response.json();
        
        return await window.crypto.subtle.importKey(
            "jwk",
            publicKeyJwk,
            {
                name: "RSA-OAEP",
                hash: "SHA-256"
            },
            true,
            ["encrypt"]
        );
    } catch (error) {
        console.error('Failed to get recipient public key:', error);
        throw error;
    }
}

// تحويلات مساعدة
function arrayBufferToBase64(buffer) {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function base64ToArrayBuffer(base64) {
    const binaryString = window.atob(base64);
    const len = binaryString.length;
    const bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

// تبادل المفاتيح مع الطرف الآخر
async function exchangeKeysWithRecipient(recipientId) {
    try {
        const response = await fetch('/api/exchange_keys', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                recipient_id: recipientId,
                public_key: cryptoKeys.publicKey
            })
        });
        
        if (!response.ok) {
            throw new Error('Key exchange failed');
        }
        
        return await response.json();
    } catch (error) {
        console.error('Key exchange error:', error);
        throw error;
    }
}

// تصدير المفتاح العام لمشاركته
function exportPublicKey() {
    return cryptoKeys.publicKey;
}

// التحقق من توقيع الرسالة
async function verifyMessageSignature(message, signature, publicKeyJwk) {
    try {
        const key = await window.crypto.subtle.importKey(
            "jwk",
            publicKeyJwk,
            {
                name: "RSASSA-PKCS1-v1_5",
                hash: "SHA-256"
            },
            false,
            ["verify"]
        );
        
        const encoder = new TextEncoder();
        const encodedMessage = encoder.encode(message);
        const signatureBuffer = base64ToArrayBuffer(signature);
        
        return await window.crypto.subtle.verify(
            "RSASSA-PKCS1-v1_5",
            key,
            signatureBuffer,
            encodedMessage
        );
    } catch (error) {
        console.error('Signature verification failed:', error);
        return false;
    }
}