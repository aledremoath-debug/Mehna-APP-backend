@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/services-formal.css') }}">
@endpush

@section('content')
<!-- <div class="formal-breadcrumb">
    <a href="{{ route('admin.categories.index') }}">إدارة الخدمات</a>
    <i class='bx bx-chevron-left'></i>
    <span>تعديل بيانات العرض</span>
</div> -->

<div class="formal-page-header">
    <div>
        <h1 class="formal-title">تعديل تفاصيل عرض الخدمة</h1>
        <p class="formal-subtitle">قم بتحديث المعلومات أدناه لضمان دقة البيانات المعروضة في المنصة</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.categories.show', $service->main_category_id) }}" class="btn-formal-outline">
            <i class='bx bx-arrow-back'></i> إلغاء والعودة
        </a>
    </div>
</div>

<form action="{{ route('admin.services.update', $service->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="formal-card">
        <div class="formal-section-header">
            <i class='bx bxs-edit-alt'></i> المعلومات الأساسية للعرض
        </div>

        <div class="row">
            <div class="col-md-12 mb-5">
                <label class="formal-label">عنوان الخدمة (الاسم التجاري)</label>
                <input type="text" name="title" class="formal-input" value="{{ $service->title }}" placeholder="مثلاً: صيانة أنظمة التكييف المركزية" required>
            </div>
            
            <div class="col-md-12 mb-5">
                <label class="formal-label">الوصف التفصيلي للعرض</label>
                <textarea name="description" class="formal-textarea" rows="6" placeholder="تفاصيل العرض والمميزات...">{{ $service->description }}</textarea>
            </div>
        </div>

        <div class="formal-section-header mt-4">
            <i class='bx bxs-grid-alt'></i> التصنيف والبيانات التشغيلية
        </div>

        <div class="row">
            <div class="col-md-6 mb-5">
                <label class="formal-label">مزود الخدمة</label>
                <select name="service_provider_id" class="formal-select" required>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" {{ $service->service_provider_id == $provider->id ? 'selected' : '' }}>
                            {{ optional($provider->user)->full_name ?? 'مزود مجهول' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">السعر التقديري (ج.س)</label>
                <input type="number" name="price" class="formal-input" value="{{ $service->price }}" step="0.01" required>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">التصنيف الرئيسي</label>
                <select id="main_category_select" name="main_category_id" class="formal-select" required>
                    @foreach($mainCategories as $cat)
                        <option value="{{ $cat->id }}" {{ $service->main_category_id == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">التصنيف الفرعي</label>
                <select id="sub_category_select" name="sub_category_id" class="formal-select" required>
                    @foreach($subCategories as $sub)
                        <option value="{{ $sub->id }}" 
                                data-main="{{ $sub->main_category_id }}"
                                {{ $service->sub_category_id == $sub->id ? 'selected' : '' }}>
                            {{ $sub->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-center mt-5 border-top pt-5">
            <button type="submit" class="btn-formal-primary">
                <i class='bx bx-save'></i> تحديث واعتماد البيانات
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_category_select');
        const subSelect = document.getElementById('sub_category_select');
        const allSubOptions = Array.from(subSelect.options);
        const currentSubId = "{{ $service->sub_category_id }}";

        function filterSubCategories(isInitial = false) {
            const selectedMainId = mainSelect.value;
            
            // Reset
            subSelect.innerHTML = '<option value="">-- اختر القسم الفرعي --</option>';
            
            if (!selectedMainId) {
                subSelect.disabled = true;
                return;
            }

            const filteredOptions = allSubOptions.filter(opt => opt.getAttribute('data-main') === selectedMainId);
            
            if (filteredOptions.length > 0) {
                subSelect.disabled = false;
                filteredOptions.forEach(opt => {
                    const newOpt = opt.cloneNode(true);
                    if (isInitial && newOpt.value === currentSubId) {
                        newOpt.selected = true;
                    }
                    subSelect.appendChild(newOpt);
                });
            } else {
                subSelect.disabled = true;
            }
        }

        mainSelect.addEventListener('change', () => filterSubCategories(false));
        
        // Initial run
        filterSubCategories(true);
    });
</script>
@endpush
@endsection
