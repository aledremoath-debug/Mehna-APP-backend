@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/categories.css') }}">
@endpush

@section('content')
<div class="category-page-header">
    <div>
        <h1 class="page-title">تعديل القسم الفرعي</h1>
        <p class="text-muted">تعديل بيانات القسم: {{ $subCategory->name }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.categories.index') }}" class="btn-premium-outline px-4">
            <i class='bx bx-arrow-back'></i> رجوع للكتالوج
        </a>
    </div>
</div>

<div class="premium-form-card">
    <form action="{{ route('admin.sub_categories.update', $subCategory->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-5">
            <label class="premium-label">القسم الرئيسي التابع له</label>
            <select name="main_category_id" class="premium-select" required>
                @foreach($mainCategories as $cat)
                    <option value="{{ $cat->id }}" {{ $subCategory->main_category_id == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-5">
            <label class="premium-label">اسم التصنيف الفرعي</label>
            <input type="text" name="name" class="premium-input" value="{{ $subCategory->name }}" required>
        </div>
        
        <div class="form-actions-bar">
            <button type="submit" class="btn-premium">
                <i class='bx bx-pencil'></i> تحديث بيانات الفرع
            </button>
            <a href="{{ route('admin.categories.index') }}" class="btn action-btn text-muted" style="width: auto; padding: 0 25px;">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
