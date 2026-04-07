<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Models\User;
use App\Models\Service;

class ChatBotController extends Controller
{
    /**
     * الدالة الرئيسية لمعالجة رسائل المستخدم - تستخدم mehna-bot المحلي فقط
     */
    public function chat(Request $request)
    {
        try {
            $userMessage = $request->input('message');
            $user = $request->user();
            $userId = $user ? $user->user_id : 1; 

            if (!$userMessage) {
                return response()->json(['reply' => 'أهلاً بك في تطبيق مهنة! كيف يمكنني مساعدتك؟']);
            }

            $session = $this->getOrCreateSession($userId);

            AiMessage::create([
                'ai_session_id' => $session->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            // 1. فلتر الأسئلة الخارجية (منع الإجابات غير المتعلقة بالتطبيق)
            if ($this->isForbiddenTopic($userMessage)) {
                $reply = 'عذراً، بصفتي مساعد تطبيق "مهنة"، أنا متخصص فقط في مساعدتك في إيجاد فنيين أو خدمات الصيانة والمتاجر في اليمن. لا أستطيع الإجابة على استفسارات طبية أو سياسية أو خارج تخصصي.';
                $this->saveBotReply($session->id, $reply, 'safety_filter');
                return response()->json(['reply' => $reply, 'source' => 'safety_filter']);
            }

            // 2. قاعدة المعرفة
            try {
                $knowledge = DB::table('chatbot_knowledge')
                    ->where('question', 'LIKE', "%{$userMessage}%")
                    ->first();

                if ($knowledge) {
                    $reply = $knowledge->answer;
                    $this->saveBotReply($session->id, $reply, 'local_database');
                    return response()->json(['reply' => $reply, 'source' => 'local_database']);
                }
            } catch (\Exception $e) { }

            // 3. تحليل النية والسياق
            $isSearchingService = $this->checkIfSearchingService($userMessage);
            $personInfo = $isSearchingService ? $this->searchForPersonOrService($userMessage) : null;
            $appData = $isSearchingService ? $this->getAppDataContext() : null;

            // 4. استشارة الذكاء الاصطناعي المحلي
            $aiResponse = $this->askLocalAI($userMessage, $personInfo, $appData);
            
            $this->saveBotReply($session->id, $aiResponse['reply'], $aiResponse['source']);

            return response()->json($aiResponse);
            
        } catch (\Exception $e) {
            Log::error("ChatBotController Error: " . $e->getMessage());
            return response()->json(['reply' => 'عذراً، حدث خطأ، يرجى المحاولة لاحقاً.'], 500);
        }
    }

    private function isForbiddenTopic($message)
    {
        // قائمة الكلمات الدالة على مواضيع خارج تخصص التطبيق
        $blackList = [
            'طب', 'علاج', 'أعراض', 'مرض', 'دواء', 'كلى', 'سرطان', 'قلب', 
            'سياسة', 'رئيس', 'انتخابات', 'حرب', 'ثورة', 
            'عاصمة', 'تاريخ', 'جغرافيا', 'قارة', 'مصر', 'السعودية', 'أمريكا'
        ];
        foreach ($blackList as $word) {
            if (mb_strpos($message, $word) !== false) return true;
        }
        return false;
    }

    private function checkIfSearchingService($message)
    {
        $keywords = ['فني', 'سباك', 'كهربائي', 'نجار', 'تاجر', 'متجر', 'خدمة', 'توصيل', 'بناء', 'تصليح', 'ميكانيك', 'مهنة', 'تطبيق', 'صالح', 'محمد', 'أفضل', 'قطع', 'غيار', 'أشتي', 'اريد', 'ابحث'];
        foreach ($keywords as $word) {
            if (mb_strpos($message, $word) !== false) return true;
        }
        return false;
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

    private function searchForPersonOrService($message)
    {
        $contextResults = [];

        // 1. البحث في الخدمات وتصنيف العطل (التشخيص)
        try {
            $service = Service::where('service_name', 'LIKE', "%{$message}%")
                ->orWhere('description', 'LIKE', "%{$message}%")
                ->first();
            if ($service) {
                $contextResults[] = "خدمة '{$service->service_name}': {$service->description}.";
            }
        } catch (\Exception $e) { }

        // 2. البحث عن أفضل فني (حسب التقييم)
        try {
            $provider = DB::table('service_providers')
                ->join('users', 'service_providers.user_id', '=', 'users.user_id')
                ->join('services', 'service_providers.service_id', '=', 'services.id')
                ->where(function ($query) use ($message) {
                    $query->where('services.service_name', 'LIKE', "%{$message}%")
                          ->orWhereRaw("? LIKE CONCAT('%', services.service_name, '%')", [$message]);
                })
                ->select('users.full_name', 'services.service_name as profession', 'users.phone', 'service_providers.rating_average')
                ->orderBy('service_providers.rating_average', 'desc')
                ->first();

            if ($provider) {
                $contextResults[] = "أفضل فني متاح هو '{$provider->full_name}' (تقييم: {$provider->rating_average})، متوفر لخدمة '{$provider->profession}'، هاتف التواصل: {$provider->phone}.";
            }
        } catch (\Exception $e) { }
        
        // 3. البحث عن تجار قطع الغيار والمتاجر
        try {
            $vendor = DB::table('sellers')
                ->join('users', 'sellers.user_id', '=', 'users.user_id')
                ->where('sellers.shop_name', 'LIKE', "%{$message}%")
                ->orWhere('sellers.shop_description', 'LIKE', "%{$message}%")
                ->select('sellers.shop_name', 'sellers.shop_description', 'users.phone')
                ->first();
            
            if ($vendor) {
                $contextResults[] = "يوجد متجر متخصص: '{$vendor->shop_name}'، الوصف: {$vendor->shop_description}، رقم التواصل: {$vendor->phone}.";
            }
        } catch (\Exception $e) { }

        return !empty($contextResults) ? implode("\n", $contextResults) : null;
    }

    private function getAppDataContext()
    {
        try {
            return cache()->remember('chatbot_app_context', 300, function () {
                $topProviders = DB::table('service_providers')
                    ->join('users', 'service_providers.user_id', '=', 'users.user_id')
                    ->join('services', 'service_providers.service_id', '=', 'services.id')
                    ->orderBy('service_providers.rating_average', 'desc')
                    ->limit(2)
                    ->select('users.full_name', 'services.service_name as profession', 'users.phone')
                    ->get();

                if ($topProviders->isEmpty()) return "";

                return "فنيين مقترحين: " . $topProviders->map(fn($p) => "{$p->full_name} ({$p->profession}): {$p->phone}")->implode('، ');
            });
        } catch (\Exception $e) { return ""; }
    }

    private function askLocalAI($message, $personInfo, $appData)
    {
        $url = "http://localhost:11434/api/generate";
        $model = "mehna-bot";

        $system = "أنت مساعد تطبيق 'مهنة' في اليمن. أجب بلهجة يمنية مهذبة ومختصرة.\n";
        $system .= "1. التشخيص: إذا كانت المشكلة (لمبة، حنفية، إلخ) وجه العميل للفني المناسب وانصحه بخطوة بسيطة.\n";
        $system .= "2. الإرشاد: اشرح كيفية الطلب (الخدمات > اختر الفني > اطلب).\n";
        $system .= "3. البيانات: لا تذكر أرقام هواتف إلا من النتائج التالية.\n";
        
        $context = ($personInfo ? "بيانات: {$personInfo}\n" : "") . ($appData ? "مقترحات: {$appData}\n" : "");

        $prompt = "### System:\n{$system}\n\n### Data:\n{$context}\n\n### User:\n{$message}\n\n### Assistant:";

        try {
            $response = Http::timeout(30)->post($url, [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_predict' => 120, // تقليل الطول لسرعة الرد
                    'num_ctx' => 1024,    // تقليل الذاكرة لسرعة المعالجة
                    'temperature' => 0.4,  // إجابة أكثر تركيزاً وسرعة
                ]
            ]);

            if ($response->successful()) {
                $reply = $response->json()['response'] ?? "...";
                return ['reply' => trim($reply), 'source' => 'ollama-mehna-bot'];
            }
            return ['reply' => 'خادم Ollama استغرق وقتاً طويلاً. حاول مجدداً.', 'source' => 'timeout'];
        } catch (\Exception $e) {
            return ['reply' => 'تأكد من تشغيل Ollama ووجود الموديل.', 'source' => 'exception'];
        }
    }
}