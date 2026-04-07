@extends('admin.layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/locations.css') }}">

<div class="page-header">
    <div>
        <h1 class="page-title">إدارة الفروع والمحافظات</h1>
        <p class="text-muted">إدارة المواقع الجغرافية للنظام</p>
    </div>
    <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">
        <i class='bx bx-plus mr-2'></i> إضافة موقع جديد
    </a>
</div>





@if($locations->count() > 0)
    <div class="locations-elite-grid">
        @foreach($locations as $governorate => $districts)
        <div class="gov-elite-card animate-reveal">
            <div class="gov-elite-header">
                <div class="gov-elite-meta">
                    <span class="elite-dot"></span>
                    <h2 class="elite-gov-name">{{ $governorate }}</h2>
                </div>
                <div class="elite-stats-pill">
                    {{ $districts->count() }} تفرع
                </div>
            </div>

            <div class="elite-districts-list">
                @foreach($districts as $location)
                <div class="elite-district-row">
                    <div class="elite-district-info">
                        <span class="district-primary">{{ $location->district }}</span>
                        <div class="district-secondary">
                            <i class='bx bx-user-circle'></i>
                            <span>{{ $location->users_count ?? 0 }} مستخدم مـؤكد</span>
                        </div>
                    </div>
                    <div class="elite-district-ops">
                        <a href="{{ route('admin.locations.edit', $location->id) }}" class="elite-op-btn edit" aria-label="Edit">
                            <i class='bx bx-edit-alt'></i>
                        </a>
                        <form action="{{ route('admin.locations.destroy', $location->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="elite-op-btn delete" aria-label="Delete">
                                <i class='bx bx-trash-alt'></i>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="elite-empty-space">
        <div class="empty-vector-aura">
            <i class='bx bx-map-pin'></i>
        </div>
        <h3>لا توجد بيانات جغرافية</h3>
        <p>ابدأ بتهيئة المحافظات والمديريات لتمكين الفرز التلقائي</p>
        <a href="{{ route('admin.locations.create') }}" class="elite-prime-btn">
            <i class='bx bx-plus-circle'></i> إضافة أول موقع
        </a>
    </div>
@endif

@endsection




