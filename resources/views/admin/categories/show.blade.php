@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

{{-- ── Breadcrumb ── --}}
<nav class="u-breadcrumb">
    <a href="{{ route('admin.dashboard') }}">الرئيسية</a>
    <i class='bx bx-chevron-left'></i>
    <a href="{{ route('admin.categories.index') }}">الخدمات الرئيسية</a>
    <i class='bx bx-chevron-left'></i>
    <span>{{ $category->name }}</span>
</nav>

{{-- ── Category Header ── --}}
<div class="u-page-header">
    <div class="u-cat-profile">
        <div class="u-cat-img">
            @if($category->image)
                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}">
            @else
                <i class='bx bx-category'></i>
            @endif
        </div>
        <div class="u-header-info">
            <h1 class="u-header-title">{{ $category->name }}</h1>
            <p class="u-header-sub">استعراض الخدمات الفرعية وعروض الخدمات المندرجة تحت هذه الخدمة</p>
        </div>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.categories.edit', $category->id) }}" class="u-btn-outline">
            <i class='bx bx-edit'></i> تعديل الخدمة
        </a>
        <a href="{{ route('admin.sub_categories.create', ['main_category_id' => $category->id]) }}" class="u-btn-primary">
            <i class='bx bx-plus-circle'></i> إضافة خدمة فرعية
        </a>
    </div>
</div>

{{-- ── Sub-Categories ── --}}
@forelse($category->subCategories as $sub)
    <div class="u-sub-card">
        {{-- Sub header --}}
        <div class="u-sub-card-header">
            <div class="u-sub-card-info">
                <div class="u-sub-card-icon">
                    <i class='bx bx-subdirectory-left'></i>
                </div>
                <div>
                    <h3 class="u-sub-card-title">{{ $sub->name }}</h3>
                    <span class="u-sub-card-count">{{ $sub->services->count() }} خدمة مسجلة</span>
                </div>
            </div>
            <div class="u-header-actions">
                <a href="{{ route('admin.sub_categories.edit', $sub->id) }}" class="u-action-btn edit" title="تعديل الخدمة الفرعية">
                    <i class='bx bx-edit-alt'></i>
                </a>
                <a href="{{ route('admin.services.create', ['sub_category_id' => $sub->id]) }}" class="u-btn-primary" style="padding: 10px 20px; font-size: .85rem; color: var(--u-secondary) !important; background: white; border: 1px solid var(--u-border); box-shadow: var(--u-shadow-sm);">
                    <i class='bx bx-plus'></i> إضافة خدمة
                </a>
            </div>
        </div>

        {{-- Services Table --}}
        <div class="u-table-wrap" style="box-shadow: none; border: none; border-radius: 0;">
            <table class="u-table">
                <thead>
                    <tr>
                        <th width="38%">اسم الخدمة</th>
                        <th width="25%">المزود</th>
                        <th width="20%">السعر</th>
                        <th width="17%" class="text-left">العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sub->services as $service)
                        <tr>
                            <td>
                                <div class="u-row-title">{{ $service->title }}</div>
                                <div class="u-row-sub">{{ Str::limit($service->description, 65) }}</div>
                            </td>
                            <td>
                                <span class="u-badge u-badge-emerald">
                                    <i class='bx bxs-user-badge'></i>
                                    {{ optional(optional($service->provider)->user)->full_name ?? 'غير معروف' }}
                                </span>
                            </td>
                            <td>
                                <span class="u-price-amount">{{ number_format($service->price) }}</span>
                                <span class="u-price-unit">ج.س</span>
                            </td>
                            <td class="text-left">
                                <div class="u-action-group">
                                    <a href="{{ route('admin.services.edit', $service->id) }}" class="u-action-btn edit" title="تعديل">
                                        <i class='bx bx-edit-alt'></i>
                                    </a>
                                    <form action="{{ route('admin.services.destroy', $service->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الخدمة؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="u-action-btn delete" title="حذف">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="u-empty-state">
                                <i class='bx bx-info-circle'></i>
                                <h4>لا توجد خدمات</h4>
                                <p>لم يتم إضافة أي عروض في هذه الخدمة الفرعية بعد.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="u-sub-card" style="text-align: center; padding: 60px 20px;">
        <i class='bx bx-folder-open' style="font-size: 4rem; color: var(--u-border); display: block; margin-bottom: 20px;"></i>
        <h3 style="font-weight: 800; margin-bottom: 10px;">لا توجد خدمات فرعية</h3>
        <p class="text-muted" style="margin-bottom: 24px;">ابدأ بإضافة خدمات فرعية لتنظيم العروض تحت هذه الخدمة الرئيسية.</p>
        <a href="{{ route('admin.sub_categories.create', ['main_category_id' => $category->id]) }}" class="u-btn-primary" style="background: var(--u-primary); color: white !important; display: inline-flex;">
            <i class='bx bx-plus-circle'></i> إضافة أول خدمة فرعية
        </a>
    </div>
@endforelse

@endsection
