<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected $serverKey;

    public function __construct()
    {
        // استخدام المفتاح القديم كقيمة احتياطية (Legacy Server Key)
        // لكن المعتمد فعلياً في الكود بالأسفل هو الإصدار الجديد للـ API v1 (OAuth2)
        $this->serverKey = env('FCM_SERVER_KEY');
    }

    /**
     * إرسال إشعار فوري (Push Notification) لجهاز أو هاتف مستخدم معين.
     * 
     * @param string $token رمز الـ FCM الخاص بالهاتف (يُسجل عند فتح التطبيق)
     * @param string $title عنوان الإشعار
     * @param string $body نص أو محتوى الإشعار
     * @param array $data بيانات إضافية مخفية ترسل للتطبيق (صيغة JSON) ليستخدمها برمجياً
     * @param string|null $image رابط صورة إضافية للإشعار (اختياري)
     * @return bool يُرجع تفويض نجاح (true) أو فشل (false)
     */
    public function sendNotification($token, $title, $body, $data = [], $image = null)
    {
        // 1. يجب التأكد من وجود توكن للهاتف أولاً وإلا فلا داعي للمحاولة
        if (empty($token)) {
            Log::warning('FCM: Missing token');
            return false;
        }

        try {
            // 2. البحث عن ملف تفويض جوجل (Service Account JSON) من إعدادات الـ .env
            $credentialsPath = env('FIREBASE_CREDENTIALS');
            
            // تهيئة المسار الصحيح للملف
            if ($credentialsPath && !file_exists($credentialsPath)) {
                $credentialsPath = base_path($credentialsPath);
            }

            // إذا لم يجده في المسار المحدد، يبحث عنه مباشرة في مجلد المشروع الرئيسي بالاسم الافتراضي
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                $credentialsPath = base_path('firebase_credentials.json');
            }
            
            // في حال عدم توفر الملف إطلاقاً، نوقف العملية ونسجل خطأ بالصلاحيات
            if (!file_exists($credentialsPath)) {
                Log::error('FCM: firebase_credentials.json not found! Checked: ' . $credentialsPath);
                return false;
            }

            // 3. إعداد "عميل جوجل" (Google Client) لطلب تصريح (OAuth2 Access Token)
            $client = new \Google\Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            
            // استخدام مسار شهادة الأمان لحل مشكلة cURL error 60 على بيئة التطوير
            $httpClient = new \GuzzleHttp\Client(['verify' => base_path('cacert.pem')]);
            $client->setHttpClient($httpClient);

            // محاولة جلب التوكن الخاص بجوجل لنتمكن من مناداة الـ API الخاص بـ Firebase
            try {
                // تمرير $httpClient مع خيار verify لدالة fetchAccessTokenWithAssertion لضمان عدم حدوث خطأ cURL 60
                $client->fetchAccessTokenWithAssertion($httpClient);
                $accessToken = $client->getAccessToken();
            } catch (\Exception $e) {
                Log::error('FCM: OAuth2 Token Error: ' . $e->getMessage());
                return false;
            }

            if (!$accessToken || !isset($accessToken['access_token'])) {
                Log::error('FCM: Failed to retrieve Access Token.');
                return false;
            }

            // 4. استخراج معرّف مشروعك (Project ID) من ملف الـ JSON السري
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $credentials['project_id'] ?? 'projectprofession-3b3f5';

            // 5. تجهيز البيانات الإضافية بصيغة نصوص حصراً (FCM يقبل القيم النصية للـ data)
            $stringData = [];
            foreach ($data as $k => $v) {
                $stringData[$k] = (string) $v;
            }
            // إعلام تطبيق فلاتر بكيفية التصرف عند نقر المستخدم على الإشعار
            $stringData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';

            // 6. تجهيز هيكل ومحتوى الإشعار الرئيسي
            $notification = [
                'title' => (string) $title,
                'body' => (string) $body,
            ];

            // إدراج صورة إذا تم إرسالها (لتظهر الإشعارات بصورة غنية)
            if ($image) {
                $notification['image'] = $image;
            }

            // 7. الإرسال (POST Request) باستخدام الـ API الجديد لـ FCM (المعروف بـ v1)
            // مع إرفاق توكن المصادقة الخاص بنا والذي تم جلبه في خطوة (3)
            $response = Http::withoutVerifying()
                ->withToken($accessToken['access_token'])
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $token, // الهاتف المستهدف
                    'notification' => $notification, // نص الإشعار الظاهر
                    'android' => [
                        'priority' => 'high', // الأولوية "عالية" لضمان ظهوره كمنبثق ورنين الهاتف
                        'notification' => [
                            'sound' => 'default', // تشغيل صوت الإشعار الافتراضي
                            'channel_id' => 'channelId', // قناة فلاتر المسموعة في نظام أندرويد
                        ],
                    ],
                    'data' => $stringData, // البيانات المخفية
                ]
            ]);

            // 8. التحقق من نجاح إرسال الإشعار
            if ($response->successful()) {
                Log::info('FCM: Notification sent successfully to token: ' . $token);
                return true;
            }

            // تسجيل تفاصيل الخطأ بملفات النظام المخفية Laravel Logs إن وجد مشكلة
            Log::error('FCM: Notification failed. Status: ' . $response->status() . ' Response: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            // معالجة وإمساك أي أخطاء وقتية مفاجئة (مثلا: انقطاع الإنترنت عن السيرفر) 
            Log::error('FCM: Error sending notification: ' . $e->getMessage());
            return false;
        }
    }
}
