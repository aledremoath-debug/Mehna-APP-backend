<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotKnowledge;
use App\Models\AppSetting;
use App\Models\AiChatSession;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /**
     * عرض لوحة تحكم المساعد الذكي
     */
    public function dashboard()
    {
        $knowledge = ChatbotKnowledge::latest()->paginate(10);
        $sessions  = AiChatSession::with('user')->latest()->take(5)->get();
        $settings  = AppSetting::first() ?? new AppSetting(['ai_assistant_enabled' => false]);

        return view('admin.ai.dashboard', compact('knowledge', 'sessions', 'settings'));
    }

    // ─── Knowledge Base CRUD ───────────────────────────────────────────────────

    /**
     * حفظ رد جديد في قاعدة المعرفة
     */
    public function knowledgeStore(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer'   => 'required|string',
        ], [
            'question.required' => 'حقل السؤال مطلوب.',
            'answer.required'   => 'حقل الإجابة مطلوب.',
        ]);

        ChatbotKnowledge::create([
            'question' => $request->question,
            'answer'   => $request->answer,
        ]);

        return redirect()->route('admin.ai.dashboard')
            ->with('success', 'تم إضافة الرد بنجاح.');
    }

    /**
     * تحديث رد موجود في قاعدة المعرفة
     */
    public function knowledgeUpdate(Request $request, $id)
    {
        $request->validate([
            'question' => 'required|string|max:500',
            'answer'   => 'required|string',
        ], [
            'question.required' => 'حقل السؤال مطلوب.',
            'answer.required'   => 'حقل الإجابة مطلوب.',
        ]);

        $item = ChatbotKnowledge::findOrFail($id);
        $item->update([
            'question' => $request->question,
            'answer'   => $request->answer,
        ]);

        return redirect()->route('admin.ai.dashboard')
            ->with('success', 'تم تحديث الرد بنجاح.');
    }

    /**
     * حذف رد من قاعدة المعرفة
     */
    public function knowledgeDestroy($id)
    {
        ChatbotKnowledge::findOrFail($id)->delete();

        return redirect()->route('admin.ai.dashboard')
            ->with('success', 'تم حذف الرد بنجاح.');
    }

    // ─── AI Toggle ─────────────────────────────────────────────────────────────

    /**
     * تفعيل / تعطيل المساعد الذكي
     */
    public function toggle(Request $request)
    {
        $settings = AppSetting::first();
        if (!$settings) {
            $settings = AppSetting::create(['ai_assistant_enabled' => false]);
        }

        $settings->update(['ai_assistant_enabled' => $request->boolean('enabled')]);

        return response()->json([
            'success' => true,
            'status'  => $settings->ai_assistant_enabled,
        ]);
    }

    // ─── Sessions ──────────────────────────────────────────────────────────────

    /**
     * عرض جميع جلسات الدردشة
     */
    public function sessionsIndex()
    {
        $sessions = AiChatSession::with('user')->latest()->paginate(20);
        return view('admin.ai.sessions.index', compact('sessions'));
    }

    /**
     * عرض تفاصيل جلسة محددة
     */
    public function sessionsShow($id)
    {
        $session = AiChatSession::with(['user', 'messages'])->findOrFail($id);
        return view('admin.ai.sessions.show', compact('session'));
    }
}
