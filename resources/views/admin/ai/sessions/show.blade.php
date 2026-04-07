@extends('admin.layouts.app')

@section('title', 'تفاصيل المحادثة')

@section('content')
<div class="reports-container">
    <div class="reports-header">
        <h1>محادثة: {{ $session->user->full_name ?? 'مستخدم' }}</h1>
        <a href="{{ route('admin.ai.sessions.index') }}" class="btn-secondary">العودة للجلسات</a>
    </div>

    <div class="chat-history shadow-sm p-4 bg-white rounded">
        @foreach($session->messages as $message)
            <div class="message-item mb-4 {{ $message->role == 'user' ? 'text-end' : 'text-start' }}">
                <div class="message-meta small text-muted mb-1">
                    <strong>{{ $message->role == 'user' ? 'المستخدم' : 'المساعد الذكي' }}</strong>
                    <span> - {{ $message->created_at ? $message->created_at->format('H:i') : '' }}</span>
                    @if($message->detected_intent)
                        <span class="badge bg-light text-dark border ms-2">المصدر: {{ $message->detected_intent }}</span>
                    @endif
                </div>
                <div class="message-content d-inline-block p-3 rounded {{ $message->role == 'user' ? 'bg-primary text-white' : 'bg-light border' }}" 
                     style="max-width: 80%; white-space: pre-wrap;">
                    {{ $message->content }}
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
