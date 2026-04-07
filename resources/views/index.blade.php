<!-- <!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار مساعد مهنة الذكي</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        #chat-container { width: 400px; height: 500px; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); display: flex; flex-direction: column; overflow: hidden; }
        #messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .message { padding: 10px 15px; border-radius: 15px; max-width: 80%; line-height: 1.4; }
        .user { align-self: flex-start; background-color: #007bff; color: white; border-bottom-left-radius: 0; }
        .bot { align-self: flex-end; background-color: #e9e9eb; color: #333; border-bottom-right-radius: 0; }
        #input-area { display: flex; padding: 10px; border-top: 1px solid #ddd; }
        input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        button { background: #007bff; color: white; border: none; padding: 10px 15px; margin-right: 5px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div id="chat-container">
    <div id="messages"></div>
    <div id="input-area">
        <input type="text" id="user-input" placeholder="اكتب سؤالك هنا...">
        <button onclick="sendMessage()">إرسال</button>
    </div>
</div>

<script>
    async function sendMessage() {
        const input = document.getElementById('user-input');
        const messagesDiv = document.getElementById('messages');
        const text = input.value.trim();

        if (!text) return;

        // إضافة رسالة المستخدم للواجهة
        appendMessage(text, 'user');
        input.value = '';

        try {
            // الاتصال بـ Laravel API
            const response = await fetch(`http://127.0.0.1:8000/api/chat?message=${encodeURIComponent(text)}`);
            const data = await response.json();
            
            // إضافة رد البوت للواجهة
            appendMessage(data.reply, 'bot');
        } catch (error) {
            console.error("Chat Error:", error);
            appendMessage("خطأ: تعذر الاتصال بالسيرفر. تأكد من تشغيل Laravel أو افحص سجل الأخطاء (Log).", 'bot');
        }
    }

    function appendMessage(text, side) {
        const messagesDiv = document.getElementById('messages');
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${side}`;
        msgDiv.innerText = text;
        messagesDiv.appendChild(msgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
</script>

</body>
</html> -->