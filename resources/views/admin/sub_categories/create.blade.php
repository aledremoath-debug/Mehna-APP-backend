@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/categories.css') }}">
@endpush

@section('content')
<div class="category-page-header">
    <div>
        <h1 class="page-title">إضافة تصنيف فرعي</h1>
        <p class="text-muted">ربط تصفيف فرعي جديد بأحد الأقسام الرئيسية</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.categories.index') }}" class="btn-premium-outline px-4">
            <i class='bx bx-arrow-back'></i> رجوع للكتالوج
        </a>
    </div>
</div>

<div class="premium-form-card">
    <form action="{{ route('admin.sub_categories.store') }}" method="POST">
        @csrf
        
        <div class="mb-5">
            <label class="premium-label">اختر القسم الرئيسي</label>
            <select name="main_category_id" class="premium-select" required>
                <option value="" disabled selected>-- حدد القسم التابع له --</option>
                @foreach($mainCategories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-5">
            <label class="premium-label">اسم التصنيف الفرعي</label>
            <input type="text" name="name" class="premium-input" placeholder="مثلاً: سباكة، كهرباء، صيانة جوالات..." required>
        </div>
        
        <div class="form-actions-bar">
            <button type="submit" class="btn-premium">
                <i class='bx bx-check-double'></i> تأكيد وإضافة الفرع
            </button>
            <a href="{{ route('admin.categories.index') }}" class="btn action-btn text-muted" style="width: auto; padding: 0 25px;">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
