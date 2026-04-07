@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">تفاصيل الطلب #{{ $order->id }}</h1>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-back">
        <i class='bx bx-arrow-back mr-2'></i> العودة للقائمة
    </a>
</div>

<div class="details-grid">
    <!-- Order Info -->
    <div class="info-card">
        <div class="card-header">
            <h3 class="card-title">معلومات الطلب</h3>
        </div>
        <div class="card-body">
            <div class="info-group">
                <label class="info-label">الحالة</label>
                <span class="status-badge {{ $order->status === 'completed' ? 'status-completed' : ($order->status === 'cancelled' ? 'status-cancelled' : 'status-pending') }}">
                    {{ $order->status === 'completed' ? 'مكتمل' : ($order->status === 'cancelled' ? 'ملغي' : 'قيد الانتظار') }}
                </span>
            </div>
            <div class="info-group">
                <label class="info-label">التاريخ</label>
                <p class="info-value">{{ $order->created_at->format('F j, Y, g:i a') }}</p>
            </div>
            <div class="info-group">
                <label class="info-label">الإجمالي</label>
                <p class="info-value">{{ number_format($order->total_price, 2) }} ريال</p>
            </div>
        </div>
    </div>

    <!-- Parties Info -->
    <div class="info-card">
        <div class="card-header">
            <h3 class="card-title">الأطراف المشاركة</h3>
        </div>
        <div class="card-body">
            <div class="info-group">
                <label class="info-label">العميل</label>
                <div class="user-profile">
                    <div class="avatar-circle">
                        {{ substr($order->user?->full_name ?? 'X', 0, 1) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $order->user?->full_name ?? 'مستخدم محذوف' }}</div>
                        <div class="user-meta">{{ $order->user?->email ?? 'لا يوجد بريد' }}</div>
                        <div class="user-meta">{{ $order->user?->phone ?? 'لا يوجد هاتف' }}</div>
                    </div>
                </div>
            </div>

            <div>
                <label class="info-label">مقدم الخدمة</label>
                @if($order->provider)
                <div class="user-profile">
                    <div class="avatar-circle avatar-blue">
                        {{ substr($order->provider->user?->full_name ?? 'X', 0, 1) }}
                    </div>
                    <div class="user-details">
                        <div class="user-name">{{ $order->provider->user?->full_name ?? 'مستخدم محذوف' }}</div>
                        <div class="user-meta">{{ $order->provider->user?->email ?? 'لا يوجد بريد' }}</div>
                        <div class="user-meta">{{ $order->provider->user?->phone ?? 'لا يوجد هاتف' }}</div>
                        <div class="user-subtext">{{ $order->provider->profession?->name ?? 'مهنة غير معروفة' }}</div>
                    </div>
                </div>
                @else
                <p class="empty-text">لم يتم تعيين مقدم خدمة بعد.</p>
                @endif
            </div>
        </div>
    </div>
</div>

</div>
@endsection
