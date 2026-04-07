@extends('admin.layouts.app')

@section('content')
<div class="page-header">
    <h1 class="page-title">التقارير والإحصائيات</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-icon-wrapper icon-indigo">
                <i class='bx bxs-user stat-icon'></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">إجمالي المستخدمين</p>
                <p class="stat-value">{{ $stats['total_users'] }}</p>
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-icon-wrapper icon-blue">
                <i class='bx bxs-hard-hat stat-icon'></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">مقدمو الخدمات</p>
                <p class="stat-value">{{ $stats['total_providers'] }}</p>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-icon-wrapper icon-green">
                <i class='bx bxs-cart stat-icon'></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">إجمالي الطلبات</p>
                <p class="stat-value">{{ $stats['total_orders'] }}</p>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-content">
            <div class="stat-icon-wrapper icon-yellow">
                <i class='bx bxs-error stat-icon'></i>
            </div>
            <div class="stat-info">
                <p class="stat-label">الشكاوى الأخيرة</p>
                <p class="stat-value">{{ $stats['recent_complaints']->count() }}</p>
            </div>
        </div>
    </div>
</div>

<div class="content-grid">
    <div class="section-card">
        <h3 class="section-title">الطلبات حسب الحالة</h3>
        <div class="progress-list">
            @foreach($stats['orders_by_status'] as $status => $count)
            <div class="progress-item">
                <div class="progress-header">
                    <span class="progress-label">{{ $status == 'pending' ? 'قيد الانتظار' : ($status == 'completed' ? 'مكتمل' : ($status == 'cancelled' ? 'ملغي' : $status)) }}</span>
                    <span class="progress-count">{{ $count }}</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: {{ ($count / max($stats['total_orders'], 1)) * 100 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="section-card">
        <h3 class="section-title">الشكاوى الأخيرة</h3>
        <div class="complaints-list">
            @forelse($stats['recent_complaints'] as $complaint)
            <div class="complaint-item">
                <div class="complaint-content">
                    <div>
                        <p class="complaint-subject">{{ $complaint->subject }}</p>
                        <p class="complaint-details">{{ Str::limit($complaint->details, 50) }}</p>
                    </div>
                    <span class="status-badge {{ $complaint->status == 'open' ? 'status-open' : 'status-closed' }}">
                        {{ $complaint->status == 'open' ? 'مفتوحة' : 'مغلقة' }}
                    </span>
                </div>
            </div>
            @empty
            <p class="empty-state">لا توجد شكاوى حديثة.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
