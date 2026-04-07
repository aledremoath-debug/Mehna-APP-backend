<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تطبيق مهنة - استعادة كلمة المرور</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            text-align: right;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2D637F;
            margin: 0;
            font-size: 28px;
        }
        .content {
            color: #555555;
            line-height: 1.6;
        }
        .code-box {
            background-color: #f0f4f8;
            border: 2px dashed #2D637F;
            padding: 20px;
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            color: #2D637F;
            letter-spacing: 10px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 14px;
            color: #999999;
            border-top: 1px solid #eeeeee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>تطبيق مهنة</h1>
        </div>
        <div class="content">
            <p>مرحباً بك،</p>
            <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في تطبيق مهنة. يرجى استخدام رمز التحقق التالي لإكمال العملية:</p>
            
            <div class="code-box">
                {{ $code }}
            </div>
            
            <p>هذا الرمز صالح لمدة 15 دقيقة فقط. إذا لم تكن أنت من طلب هذا الرمز، يرجى تجاهل هذا البريد الإلكتروني.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} تطبيق مهنة. جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>
