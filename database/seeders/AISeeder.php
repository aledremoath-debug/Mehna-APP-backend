<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AiChatSession;
use App\Models\AiMessage;

class AISeeder extends Seeder
{
    public function run(): void
    {
        $session = AiChatSession::create([    
            'customer_id' => 1,
            'session_status' => 'active'
        ]);

        // ملاحظة: تأكد من مسميات الأعمدة من ملف الـ Migration الخاص بك
        AiMessage::create([
            'ai_session_id' => $session->id,
            'role'          => 'user',    // استبدل sender_type بـ role إذا لزم الأمر
            'content'       => 'مرحباً، كيف يمكنك مساعدتي؟' // استبدل message_content بـ content
        ]);
    }
}