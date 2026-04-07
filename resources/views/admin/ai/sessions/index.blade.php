@extends('admin.layouts.app')

@section('title', 'جلسات الدردشة الذكية')

@section('content')
<div class="reports-container">
    <div class="reports-header">
        <h1>مراقبة جلسات المساعد الذكي</h1>
    </div>

    <div class="table-container">
        <table class="reports-table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>الحالة</th>
                    <th>تاريخ البدء</th>
                    <th>العمليات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                <tr>
                    <td>{{ $session->user->full_name ?? 'مستخدم غير معروف' }} ({{ $session->user->phone ?? '' }})</td>
                    <td>
                        <span class="status-badge {{ $session->session_status == 'active' ? 'status-pending' : 'status-completed' }}">
                            {{ $session->session_status == 'active' ? 'نشط' : 'مؤرشف' }}
                        </span>
                    </td>
                    <td>{{ $session->created_at ? $session->created_at->format('Y-m-d H:i') : 'غير معروف' }}</td>
                    <td>
                        <a href="{{ route('admin.ai.sessions.show', $session->id) }}" class="btn-view">عرض المحادثة</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $sessions->links() }}
    </div>
</div>
@endsection
