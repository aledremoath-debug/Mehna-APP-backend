@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/admin-unified.css') }}">
@endpush

@section('content')

<div class="u-page-header">
    <div class="u-header-info">
        <h1 class="u-header-title">إضافة تصنيف منتج</h1>
        <p class="u-header-sub">أضف تصنيفاً جديداً لتصنيف المنتجات في صفحة قطع الغيار</p>
    </div>
    <div class="u-header-actions">
        <a href="{{ route('admin.product_categories.index') }}" class="u-btn-outline">
            <i class='bx bx-arrow-back'></i> رجوع
        </a>
    </div>
</div>

<div class="u-table-wrap" style="max-width: 600px;">
    <form action="{{ route('admin.product_categories.store') }}" method="POST" style="padding: 24px;">
        @csrf

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">اسم التصنيف *</label>
            <input type="text" name="name" value="{{ old('name') }}" required
                   style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 1rem; outline: none;"
                   placeholder="مثال: سباكة، كهرباء، تكييف...">
            @error('name')
                <p style="color: #dc2626; font-size: 0.85rem; margin-top: 4px;">{{ $message }}</p>
            @enderror
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">أيقونة (اختياري)</label>
            <input type="text" name="icon" value="{{ old('icon') }}"
                   style="width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 1rem; outline: none;"
                   placeholder="مثال: bx-wrench">
            <p style="color: #9ca3af; font-size: 0.8rem; margin-top: 4px;">
                استخدم أيقونات <a href="https://boxicons.com/" target="_blank" style="color: #2563eb;">Boxicons</a>
            </p>
        </div>

        <button type="submit" class="u-btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
            <i class='bx bx-plus-circle'></i> إضافة التصنيف
        </button>
    </form>
</div>

@endsection
