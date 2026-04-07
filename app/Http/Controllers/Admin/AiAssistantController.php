<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotKnowledge;
use App\Models\AiChatSession;
use App\Models\AiMessage;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function index()
    {
        $settings = AppSetting::latest()->first();
        $knowledge = ChatbotKnowledge::latest()->paginate(15);
        $sessions = AiChatSession::with(['user'])->latest()->limit(5)->get();
        
        return view('admin.ai.dashboard', compact('settings', 'knowledge', 'sessions'));
    }

    public function toggleStatus(Request $request)
    {
        $settings = AppSetting::latest()->first();
        $settings->update([
            'ai_assistant_enabled' => $request->has('enabled') ? $request->enabled : false
        ]);

        return response()->json(['success' => true, 'status' => $settings->ai_assistant_enabled]);
    }

    /**
     * Knowledge Base Management
     */
    public function knowledgeIndex()
    {
        return redirect()->route('admin.ai.index');
    }

    public function knowledgeStore(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        ChatbotKnowledge::create($request->all());

        return redirect()->back()->with('success', 'تمت إضافة الرد بنجاح.');
    }

    public function knowledgeUpdate(Request $request, $id)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $entry = ChatbotKnowledge::findOrFail($id);
        $entry->update($request->all());

        return redirect()->back()->with('success', 'تم تحديث الرد بنجاح.');
    }

    public function knowledgeDestroy($id)
    {
        $entry = ChatbotKnowledge::findOrFail($id);
        $entry->delete();

        return redirect()->back()->with('success', 'تم حذف الرد بنجاح.');
    }

    /**
     * Chat Sessions Overview
     */
    public function sessionsIndex()
    {
        $sessions = AiChatSession::with(['user'])->latest()->paginate(10);
        return view('admin.ai.sessions.index', compact('sessions'));
    }

    public function sessionShow($id)
    {
        $session = AiChatSession::with(['user', 'messages'])->findOrFail($id);
        return view('admin.ai.sessions.show', compact('session'));
    }
}
