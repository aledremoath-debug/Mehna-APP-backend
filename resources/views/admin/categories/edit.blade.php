@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/categories.css') }}">
@endpush

@section('content')
<div class="category-page-header">
    <div>
        <h1 class="page-title">تعديل التصنيف</h1>
        <p class="text-muted">تعديل بيانات التصنيف الرئيسي: {{ $category->name }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.categories.index') }}" class="btn-premium-outline px-4">
            <i class='bx bx-arrow-back'></i> رجوع للكتالوج
        </a>
    </div>
</div>

<div class="premium-form-card">
    <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="mb-5">
            <label class="premium-label">اسم التصنيف</label>
            <input type="text" name="name" class="premium-input" value="{{ $category->name }}" required>
        </div>
        
        <div class="mb-5">
            <label class="premium-label">صورة التصنيف</label>
            
            @if($category->image)
                <div class="preview-container mb-4">
                    <div class="preview-img-box">
                        <img src="{{ asset('storage/' . $category->image) }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <p class="text-muted small mt-2">الصورة الحالية</p>
                </div>
            @endif

            <div class="file-upload-wrapper">
                <i class='bx bx-refresh upload-icon'></i>
                <span class="upload-text">اضغط لرفع صورة جديدة</span>
                <span class="text-muted small">(اتركها فارغة للإبقاء على الصورة الحالية)</span>
                <input type="file" name="image" class="file-upload-input" onchange="previewImage(this)">
            </div>
            
            <div id="imagePreview" class="preview-container d-none">
                <div class="preview-img-box" style="border-color: var(--cat-success);">
                    <img id="previewImg" src="#" alt="New Image Preview" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <p class="text-muted small mt-3">معاينة الصورة الجديدة</p>
            </div>
        </div>
        
        <div class="form-actions-bar">
            <button type="submit" class="btn-premium">
                <i class='bx bx-save'></i> تحديث البيانات
            </button>
            <a href="{{ route('admin.categories.index') }}" class="btn action-btn text-muted" style="width: auto; padding: 0 25px;">
                إلغاء التغييرات
            </a>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const img = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
