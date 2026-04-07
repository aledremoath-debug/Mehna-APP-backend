<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Models\AppSetting;

class ChatBotControllerWithGemni2 extends Controller
{
    public function chat(Request $request)
    {
        try {
            // التحقق من حالة المساعد الذكي
            $settings = AppSetting::latest()->first();
            if (!$settings->ai_assistant_enabled) {
                return response()->json([
                    'reply' => 'عذراً، المساعد الذكي غير متاح حالياً بقرار من الإدارة. جرب في وقت لاحق.',
                    'source' => 'system_off'
                ], 200);
            }

            $userMessage = $request->input('message');
            $user = $request->user();
            $userId = $user ? $user->user_id : 1;

            if (!$userMessage) {
                return response()->json(['reply' => 'أهلاً بك في تطبيق مهنة! كيف يمكنني مساعدتك؟']);
            }

            // 0. إدارة الجلسة وتسجيل رسالة المستخدم
            $session = $this->getOrCreateSession($userId);
            AiMessage::create([
                'ai_session_id' => $session->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            // 1. فلتر المواضيع المحظورة (حماية التخصص)
            if ($this->isForbiddenTopic($userMessage)) {
                $reply = 'عذراً، أنا هنا لمساعدتك في صيانة منزلك وخدمات مهنة في اليمن. لا أستطيع الإجابة على استفسارات طبية أو سياسية أو خارج تخصصي.';
                $this->saveBotReply($session->id, $reply, 'safety_filter');
                return response()->json([
                    'reply' => $reply,
                    'source' => 'safety_filter'
                ]);
            }

            // 2. البحث في قاعدة المعرفة (الأسئلة الثابتة)
            try {
                $knowledge = DB::table('chatbot_knowledge')
                    ->where('question', 'LIKE', "%{$userMessage}%")
                    ->first();
                if ($knowledge) {
                    return response()->json(['reply' => $knowledge->answer, 'source' => 'local_database']);
                }
            } catch (\Exception $e) {
            }

            // 3. تحليل النية والبحث في البيانات (فنيين وتجار)
            $personInfo = $this->searchForPersonOrVendor($userMessage);

            // 4. جلب مقترحات للأفضل
            $appData = $this->getAppDataContext();

            // 5. إرسال السؤال إلى Gemini مع السياق الذكي
            return $this->askGeminiWithContext($userMessage, $personInfo, $appData, $session);

        } catch (\Exception $e) {
            Log::error("ChatBot2 Error: " . $e->getMessage());
            return response()->json(['reply' => 'عذراً يا صاحبي، حدث خطأ فني بسيط، جرب مرة ثانية.'], 500);
        }
    }

    private function isForbiddenTopic($message)
    {
        $blackList = ['طب', 'طبيب', 'علاج', 'أعراض', 'مرض', 'دواء', 'كلى', 'سرطان', 'سياسة', 'سياسي', 'رئيس', 'انتخابات', 'حرب', 'عسكري', 'ثورة', 'أسلحة'];

        // Use regular expression to match whole words only to prevent 'تطبيق' from matching 'طب'
        foreach ($blackList as $word) {
            // \b doesn't work well with Arabic, use (?<!\p{L}) and (?!\p{L}) for boundaries
            $pattern = '/(?<!\p{L})' . preg_quote($word, '/') . '(?!\p{L})/u';
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        return false;
    }

    private function searchForPersonOrVendor($message)
    {
        $results = [];
        try {
            // 1. استخراج الكلمات المفتاحية الذكية لمعالجة لهجة "اشتي كهربائي"
            $stopWords = ['اشتي', 'اريد', 'ابى', 'ابغى', 'احتاج', 'ضروري', 'افضل', 'احسن', 'شاطر', 'ممتاز', 'في', 'من', 'عن', 'على', 'رقم', 'عندكم', 'يوجد', 'مطلوب', 'دور', 'ابي', 'عندي', 'مشكلة', 'خربان'];
            $cleanMessage = str_replace($stopWords, ' ', $message);
            $keywords = array_filter(explode(' ', trim($cleanMessage)), function ($w) {
                return mb_strlen(trim($w)) >= 2; // أخذ الكلمات الأكثر من حرفين
            });

            if (empty($keywords)) {
                $keywords = [trim($message)];
            }

            // 2. البحث عن المهنيين (أفضل 3 فنيين تقييماً يطابقون الطلب)
            $providerQuery = DB::table('service_providers')
                ->join('users', 'service_providers.user_id', '=', 'users.user_id')
                ->join('services', 'services.service_provider_id', '=', 'service_providers.id')
                ->select('users.full_name', 'services.title as profession', 'users.phone', 'service_providers.rating_average', 'users.address_description')
                ->where('users.is_active', 1)
                ->orderBy('service_providers.rating_average', 'desc');

            $providerQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('services.title', 'LIKE', '%' . $word . '%')
                        ->orWhere('services.description', 'LIKE', '%' . $word . '%');
                }
            });

            $topProviders = $providerQuery->limit(3)->get();

            if ($topProviders->isNotEmpty()) {
                $results[] = "الفنيين المقترحين والأفضل تقييماً المتطابقين مع الطلب:";
                foreach ($topProviders as $provider) {
                    $rate = $provider->rating_average ? number_format($provider->rating_average, 1) : 'جديد';
                    $results[] = "- الكابتن/ {$provider->full_name} | التخصص: {$provider->profession} | التقييم: {$rate}/5 | هاتف: {$provider->phone}";
                }
            }

            // 3. بحث عن متاجر (تجار) أو قطع غيار
            $vendorQuery = DB::table('sellers')
                ->join('users', 'sellers.user_id', '=', 'users.user_id')
                ->select('sellers.shop_name', 'sellers.shop_description', 'users.phone', 'users.address_description');

            $vendorQuery->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('sellers.shop_name', 'LIKE', '%' . $word . '%')
                        ->orWhere('sellers.shop_description', 'LIKE', '%' . $word . '%');
                }
            });

            // ضمان جلب المتاجر إذا ذكرها بلسانه
            if (mb_strpos($message, 'قطع غيار') !== false || mb_strpos($message, 'محل') !== false || mb_strpos($message, 'متجر') !== false) {
                $vendorQuery->orWhere('sellers.shop_description', 'LIKE', '%قطع غيار%');
            }

            $topVendors = $vendorQuery->limit(2)->get();

            if ($topVendors->isNotEmpty()) {
                $results[] = "\nمتاجر يمنية مقترحة لها علاقة بالطلب:";
                foreach ($topVendors as $vendor) {
                    $results[] = "- متجر: {$vendor->shop_name} | الوصف: {$vendor->shop_description} | هاتف: {$vendor->phone}";
                }
            }

        } catch (\Exception $e) {
            Log::error("Search Person/Vendor Error: " . $e->getMessage());
        }

        return !empty($results) ? implode("\n", $results) : null;
    }

    private function getAppDataContext()
    {
        try {
            return cache()->remember('chatbot_gemini_context_v2', 300, function () {
                $context = "";

                // أفضل المهنيين
                $topProviders = DB::table('service_providers')
                    ->join('users', 'service_providers.user_id', '=', 'users.user_id')
                    ->join('services', 'services.service_provider_id', '=', 'service_providers.id')
                    ->orderBy('service_providers.rating_average', 'desc')
                    ->limit(2)
                    ->select('users.full_name', 'services.title as profession', 'users.phone')
                    ->get();

                if ($topProviders->isNotEmpty()) {
                    $context .= "أفضل المهنيين المتاحين: " . $topProviders->map(fn($p) => "{$p->full_name} ({$p->profession}): {$p->phone}")->implode('، ') . "\n";
                }

                // أفضل المتاجر (تجار قطع الغيار)
                $topVendors = DB::table('sellers')
                    ->join('users', 'sellers.user_id', '=', 'users.user_id')
                    ->limit(2)
                    ->select('sellers.shop_name', 'users.phone', 'users.address_description')
                    ->get();

                if ($topVendors->isNotEmpty()) {
                    $context .= "متاجر قطع غيار مقترحة: " . $topVendors->map(fn($v) => "{$v->shop_name} (العنوان: {$v->address_description}): {$v->phone}")->implode('، ') . "\n";
                }

                return $context;
            });
        } catch (\Exception $e) {
            return "";
        }
    }

    private function askGeminiWithContext($message, $personInfo, $appData, $session)
    {
        $apiKey = env('GEMINI_API_KEY');

        // الاعتماد في البداية على نماذج Flash السريعة جداً لضمان سرعة الرد للمستخدم
        $models = [
            ['ver' => 'v1beta', 'name' => 'gemini-flash-latest'],
            ['ver' => 'v1beta', 'name' => 'gemini-2.5-flash'],
            ['ver' => 'v1beta', 'name' => 'gemini-3-flash-preview'],
            ['ver' => 'v1beta', 'name' => 'gemini-3.1-pro-preview'],
        ];

        $system = "أنت 'خبير تطبيق مهنة' في اليمن. هويتك: مساعد ذكي، ودود، وخبير في التوجيه السريع للاستفادة من خدمات وتطبيق مهنة.\n\n";
        $system .= "دليل النظام وطريقة عمل التطبيق (احفظها تماماً لترد بوضوح على استفسارات المستخدم):\n";
        $system .= "- نبذة عن تطبيق مهنة: هو منصة خدمية شاملة ورائدة في اليمن، تهدف لتسهيل حياة الناس عبر التوفيق بين الباحثين عن الخدمات (كالصيانة المنزلية، السباكة، الكهرباء، إلخ) وبين أفضل الفنيين والمقاولين الموثوقين. كما يوفر التطبيق سوقاً إلكترونياً لشراء المنتجات وقطع الغيار من المتاجر المحلية بسلاسة.\n";
        $system .= "- لفتح حساب 'مهني' أو 'مزود خدمة': قم بالذهاب من الشاشة الرئيسية إلى القائمة الجانبية (أو الملف الشخصي)، ثم اختر (الانضمام كمهني/مزود خدمة). سيُطلب منك إدخال تفاصيل مهنتك ورفع هويتك، وبعد الإرسال يقوم فريق مهنة بمراجعة طلبك والموافقة لتتمكن من استقبال الطلبات وزيادة دخلك.\n";
        $system .= "- لفتح حساب 'متجر' أو 'تاجر قطع غيار': من القائمة الجانبية (أو الملف الشخصي)، اختر (الانضمام كمتجر / الانضمام كتاجر). أدخل بيانات واسم المتجر. بعد مراجعته من الإدارة، سيمكنك إضافة منتجاتك المادية (المسوقة) لجميع مستخدمي المنصة.\n";
        $system .= "- كيفية طلب خدمة وتحديد مهني: من الشاشة الرئيسية، ادخل على قسم (الخدمات)، اختر التصنيف المطلوب (مثال: كهرباء أو سباكة)، لتظهر لك قائمة بأفضل الفنيين مع تقييماتهم. اختر الفني الذي يناسبك واضغط على زر (طلب خدمة)، ويمكنك كتابة تفاصيل حول المشكلة ليصله إشعار مباشر بطلبك.\n";
        $system .= "- تصفح المتاجر والمنتجات: يمكنك الدخول إلى قسم (المنتجات) لتصفح المتاجر اليمنية وشراء قطع الغيار والأدوات بضغطة زر.\n\n";

        $system .= "قواعد الإجابة الإلزامية:\n";
        $system .= "1. التوجيه المباشر والواضح: إذا سأل كيف أفتح متجر، اشرح له مسار التطبيق بوضوح وبدقة وبالترتيب.\n";
        $system .= "2. ترشيح المهنيين بذكاء: إذا كان المستخدم يطلب 'فني' (مثال: اشتي كهربائي، أريد سباك)، سأزودك أدناه في (البيانات المتاحة) بأفضل المطابقين. قم بصياغة عرض الفنيين للمستخدم بشكل منظم وابتعد عن ذكر أي تخصص غير المطلوب واذكر التقييم والاسم والرقم بأسلوب راقي.\n";
        $system .= "3. لا تؤلف بيانات: إذا لم تجد مهنيين في السياق لا تخترع أرقام هواتف أبداً، بل وجهه لفتح قائمة (الخدمات) واختيار القسم المناسب.\n";
        $system .= "4. أسلوب ولهجة الرد: تكلم بلهجة يمنية بيضاء وقريبة للقلب، رحب به (حياك الله أخي الغالي، على عيني وراسي)، وكن مثل خدمة العملاء المثالية السريعة. \n";
        $system .= "5. التخصص: اعتذر دائماً عن الإجابة في المواضيع الطبية والسياسية وبرر ذلك بأنك مساعد مخصص لخدمات تطبيق مهنة والصيانة المنزلية فقط.\n";

        $dataContext = "البيانات المتاحة من النظام الآن:\n";
        if ($personInfo)
            $dataContext .= "- نتائج البحث المباشرة: {$personInfo}\n";
        if ($appData)
            $dataContext .= "- مقترحات إضافية: {$appData}\n";
        $dataContext .= "- طريقة طلب مهني بالبرنامج: (الرئيسية > الخدمات > اختر الخدمة > اختر الفني > اضغط طلب).\n";

        foreach ($models as $m) {
            $version = $m['ver'];
            $modelName = $m['name'];
            $url = "https://generativelanguage.googleapis.com/{$version}/models/{$modelName}:generateContent?key=" . $apiKey;

            try {
                $response = Http::timeout(30)->withoutVerifying()->post($url, [
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => "System Instructions:\n{$system}\n\nAvailable Context:\n{$dataContext}\n\nUser Message: {$message}"]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.6,
                        'maxOutputTokens' => 1000,
                    ]
                ]);

                if ($response->failed()) {
                    $errorData = $response->json();
                    $errorMsg = $errorData['error']['message'] ?? 'Unknown Error';
                    Log::warning("Gemini API Warning ($modelName): " . $errorMsg);

                    // إذا كان الخطأ متعلق بالحصص أو الموديل، ننتقل للموديل التالي
                    if ($response->status() == 429 || $response->status() == 404 || $response->status() == 400) {
                        continue;
                    }

                    // نرجع كود 200 بدلاً من 500 لضمان وصول الرسالة للمستخدم في Flutter دون "فشل اتصال"
                    return response()->json(['reply' => 'يا غالي، نظام الذكاء الاصطناعي حالياً تحت الصيانة البسيطة، جرب مرة ثانية بعد قليل.', 'debug' => $errorMsg], 200);
                }

                $data = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($reply) {
                    $this->saveBotReply($session->id, trim($reply), 'gemini-api-' . $modelName);
                    return response()->json(['reply' => trim($reply), 'source' => 'gemini-api-' . $modelName]);
                }

            } catch (\Exception $e) {
                Log::error("Gemini Request Exception ($modelName): " . $e->getMessage());
                continue;
            }
        }

        // إذا فشل كل شيء في جيميناي، نحاول الرد بناءً على نتائج البحث المحلية بدلاً من إظهار رسالة خطأ
        if ($personInfo) {
            $fallbackReply = "يا غالي، جيميناي عنده ضغط شوية حالياً، لكن بحثت لك في النظام ووجدت هذا:\n" . $personInfo . "\n\nتقدر تطلب الخدمة مباشرة من قسم الخدمات.";
            return response()->json(['reply' => $fallbackReply, 'source' => 'local_fallback_search'], 200);
        }

        $defaultReply = "يا صاحبي، جيميناي حالياً مشغول بالرد على مستخدمين آخرين. \n\nبإمكانك البحث عن 'فنيين' أو 'قطع غيار' مباشرة من الشاشة الرئيسية > الخدمات. \nأنا هنا دائماً لمساعدتك، جرب تراسلني بعد قليل!";

        return response()->json(['reply' => $defaultReply, 'source' => 'busy_fallback'], 200);
    }

    private function getOrCreateSession($userId)
    {
        $session = AiChatSession::where('customer_id', $userId)
            ->where('session_status', 'active')
            ->latest()
            ->first();

        if (!$session) {
            $session = AiChatSession::create([
                'customer_id' => $userId,
                'session_status' => 'active'
            ]);
        }
        return $session;
    }

    private function saveBotReply($sessionId, $content, $source)
    {
        AiMessage::create([
            'ai_session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $content,
            'detected_intent' => $source
        ]);
    }
}