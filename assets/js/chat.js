// متغيرات عامة
let currentChatId = null;
let lastMessageId = 0;
let isTyping = false;
let typingTimeout = null;

// تهيئة الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
    setupEventListeners();
});

// تهيئة الدردشة
function initializeChat() {
    // جلب معرف المحادثة من URL إذا كان موجوداً
    const urlParams = new URLSearchParams(window.location.search);
    currentChatId = urlParams.get('chat_id');
    
    if (currentChatId) {
        loadChatHistory();
        startMessagePolling();
    }
    
    // تهيئة WebSocket (إذا تم استخدامه)
    initializeWebSocket();
}

// إعداد مستمعي الأحداث
function setupEventListeners() {
    // إرسال الرسالة
    document.getElementById('send-button').addEventListener('click', sendMessage);
    document.getElementById('message-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // كتابة رسالة
    document.getElementById('message-input').addEventListener('input', function() {
        updateTypingStatus(true);
    });
    
    // إرفاق ملف
    document.getElementById('file-input').addEventListener('change', handleFileUpload);
}

// تحميل سجل المحادثة
async function loadChatHistory() {
    try {
        const response = await fetch(`/api/messages?chat_id=${currentChatId}&last_id=${lastMessageId}`);
        const messages = await response.json();
        
        if (messages.length > 0) {
            appendMessages(messages);
            lastMessageId = messages[messages.length - 1].id;
            scrollToBottom();
        }
    } catch (error) {
        console.error('Failed to load chat history:', error);
    }
}

// إرسال رسالة
async function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message || !currentChatId) return;
    
    try {
        // تشفير الرسالة قبل الإرسال
        const encryptedMessage = await encryptMessage(message);
        
        const response = await fetch('/api/send_message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                chat_id: currentChatId,
                message: encryptedMessage,
                is_temporary: document.getElementById('temp-message-checkbox').checked
            })
        });
        
        if (response.ok) {
            input.value = '';
            updateTypingStatus(false);
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        showNotification('Failed to send message', 'error');
    }
}

// تلقي الرسائل الجديدة
function startMessagePolling() {
    setInterval(async () => {
        try {
            const response = await fetch(`/api/messages?chat_id=${currentChatId}&last_id=${lastMessageId}`);
            const messages = await response.json();
            
            if (messages.length > 0) {
                appendMessages(messages);
                lastMessageId = messages[messages.length - 1].id;
                scrollToBottom();
                
                // تأكيد استلام الرسائل
                confirmMessageReceipt(messages.map(msg => msg.id));
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 3000); // استطلاع كل 3 ثوان
}

// إضافة رسائل جديدة إلى الواجهة
function appendMessages(messages) {
    const container = document.getElementById('messages-container');
    
    messages.forEach(msg => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${msg.sender_id === currentUserId ? 'sent' : 'received'}`;
        
        // فك تشفير الرسالة
        decryptMessage(msg.content).then(decryptedContent => {
            messageDiv.innerHTML = `
                <div class="message-text">${escapeHtml(decryptedContent)}</div>
                <div class="message-info">
                    <span>${formatTime(msg.created_at)}</span>
                    ${msg.is_temporary ? '<span class="temp-label">Temporary</span>' : ''}
                </div>
            `;
            
            if (msg.is_temporary) {
                messageDiv.classList.add('temporary-message');
                startMessageTimer(messageDiv, msg.id, msg.expires_at);
            }
            
            container.appendChild(messageDiv);
        });
    });
}

// مؤقت الرسائل المؤقتة
function startMessageTimer(messageElement, messageId, expiresAt) {
    const expiryTime = new Date(expiresAt).getTime();
    const now = new Date().getTime();
    const remainingTime = expiryTime - now;
    
    if (remainingTime > 0) {
        setTimeout(() => {
            messageElement.style.opacity = '0.5';
            messageElement.style.border = '1px dashed #ff0000';
            
            // إعلام الخادم بحذف الرسالة
            fetch(`/api/delete_message`, {
                method: 'POST',
                body: JSON.stringify({ message_id: messageId })
            });
            
            setTimeout(() => {
                messageElement.remove();
            }, 2000);
        }, remainingTime);
    } else {
        messageElement.remove();
    }
}

// وظائف مساعدة
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

function showNotification(message, type = 'info') {
    // تنفيذ إشعار للمستخدم
}