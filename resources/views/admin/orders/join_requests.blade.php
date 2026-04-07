@extends('admin.layouts.app')

@section('title', 'طلبات الانضمام')

@section('content')
<div class="page-header">
    <h1 class="page-title">طلبات الانضمام (مقدمو الخدمات والتجار)</h1>
</div>

<div class="content-card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>النوع</th>
                    <th>التفاصيل</th>
                    <th>رقم الهاتف</th>
                    <th>التاريخ</th>
                    <th class="text-left">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $user)
                <tr>
                    <td>
                        <strong>{{ $user->full_name }}</strong><br>
                        <small class="text-muted">{{ $user->email }}</small>
                    </td>
                    <td>
                        <span class="badge {{ $user->user_type == 1 ? 'badge-info' : 'badge-warning' }}">
                            {{ $user->user_type_label }}
                        </span>
                    </td>
                    <td>
                        @if($user->user_type == 1 && $user->serviceProvider)
                            <div class="fs-sm">
                                <strong>المهنة:</strong> {{ $user->serviceProvider->mainCategory->name ?? 'غير محدد' }}<br>
                                <strong>الخبرة:</strong> {{ $user->serviceProvider->experience_years }} سنة
                            </div>
                        @elseif($user->user_type == 2 && $user->seller)
                            <div class="fs-sm">
                                <strong>المتجر:</strong> {{ $user->seller->shop_name }}<br>
                                <strong>السجل:</strong> {{ $user->seller->commercial_register ?? 'لا يوجد' }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->created_at ? $user->created_at->format('Y-m-d') : '-' }}</td>
                    <td class="text-left">
                        <div class="flex-gap-8 justify-end">
                            <form action="{{ route('admin.join-requests.approve', $user->user_id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-approve-sm">قبول</button>
                            </form>
                            
                            <button type="button" class="btn-reject-sm" 
                                    onclick="openRejectModal({{ $user->user_id }}, '{{ $user->full_name }}')">
                                رفض
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-40">لا توجد طلبات انضمام حالياً.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4">
        {{ $requests->links() }}
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="modal-overlay">
    <div class="modal-box">
        <h3 class="mb-20">رفض طلب <span id="rejectUserName"></span></h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-20">
                <label class="d-block mb-8">سبب الرفض (اختياري)</label>
                <textarea name="reason" rows="3" class="form-textarea"></textarea>
            </div>
            <div class="flex-gap-10 justify-end">
                <button type="button" onclick="closeRejectModal()" class="btn-cancel-sm">إلغاء</button>
                <button type="submit" class="btn-reject-sm">تأكيد الرفض</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(userId, userName) {
        document.getElementById('rejectUserName').innerText = userName;
        document.getElementById('rejectForm').action = '/admin/join-requests/' + userId + '/reject';
        document.getElementById('rejectModal').style.display = 'flex';
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }
</script>


@endsection
