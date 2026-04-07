@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/categories.css') }}">
@endpush

@section('content')
<div class="category-page-header">
    <div>
        <h1 class="page-title">إضافة تصنيف جديد</h1>
        <p class="text-muted">قم بإنشاء تصنيف رئيسي جديد لعروض الخدمات</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.categories.index') }}" class="btn-premium-outline px-4">
            <i class='bx bx-arrow-back'></i> رجوع للكتالوج
        </a>
    </div>
</div>

<div class="premium-form-card">
    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="mb-5">
            <label class="premium-label">اسم التصنيف الرئيسي</label>
            <input type="text" name="name" class="premium-input" placeholder="مثلاً: صيانة منزلية، خدمات طبية..." required>
        </div>
        
        <div class="mb-5">
            <label class="premium-label">صورة أو أيقونة التصنيف</label>
            <div class="file-upload-wrapper">
                <i class='bx bx-cloud-upload upload-icon'></i>
                <span class="upload-text">اسحب الصورة هنا أو اضغط للاختيار</span>
                <span class="text-muted small">PNG, JPG (أقصى حجم 2MB)</span>
                <input type="file" name="image" class="file-upload-input" onchange="previewImage(this)">
            </div>
            
            <div id="imagePreview" class="preview-container d-none">
                <div class="preview-img-box">
                    <img id="previewImg" src="#" alt="Image Preview" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <p class="text-muted small mt-3">معاينة الصورة المختارة</p>
            </div>
        </div>
        
        <div class="form-actions-bar">
            <button type="submit" class="btn-premium">
                <i class='bx bx-check-circle'></i> حفظ التصنيف الجديد
            </button>
            <a href="{{ route('admin.categories.index') }}" class="btn action-btn text-muted" style="width: auto; padding: 0 25px;">
                إلغاء
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
