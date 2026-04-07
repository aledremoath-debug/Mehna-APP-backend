@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/services-formal.css') }}">
@endpush

@section('content')
<div class="formal-breadcrumb">
    <a href="{{ route('admin.categories.index') }}">إدارة الخدمات</a>
    <i class='bx bx-chevron-left'></i>
    <span>إنشاء عرض جديد</span>
</div>

<div class="formal-page-header">
    <div>
        <h1 class="formal-title">إضافة عرض خدمة جديد</h1>
        <p class="formal-subtitle">يرجى ملء كافة الحقول أدناه لإنشاء عرض خدمة احترافي في النظام</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.services.index') }}" class="btn-formal-outline">
            <i class='bx bx-arrow-back'></i> إلغاء والعودة
        </a>
    </div>
</div>

<form action="{{ route('admin.services.store') }}" method="POST">
    @csrf
    <div class="formal-card">
        <div class="formal-section-header">
            <i class='bx bxs-info-circle'></i> المعلومات الأساسية للعرض
        </div>

        <div class="row">
            <div class="col-md-12 mb-5">
                <label class="formal-label">عنوان الخدمة (الاسم التجاري للعرض)</label>
                <input type="text" name="title" class="formal-input" placeholder="مثلاً: صيانة أنظمة التكييف المركزية بقدرة 5 طن" required>
            </div>
            
            <div class="col-md-12 mb-5">
                <label class="formal-label">الوصف التفصيلي للخدمة</label>
                <textarea name="description" class="formal-textarea" rows="6" placeholder="اكتب تفاصيل ما تشمله الخدمة، وما هي الضمانات أو المميزات المقدمة للعميل..."></textarea>
            </div>
        </div>

        <div class="formal-section-header mt-4">
            <i class='bx bxs-category'></i> التصنيف والبيانات التشغيلية
        </div>

        <div class="row">
            <div class="col-md-6 mb-5">
                <label class="formal-label">مزود الخدمة المعتمد</label>
                <select name="service_provider_id" class="formal-select" required>
                    <option value="" disabled selected>-- اختر المزود من القائمة --</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}">{{ optional($provider->user)->full_name ?? 'مزود مجهول' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">السعر التقديري (ج.س)</label>
                <input type="number" name="price" class="formal-input" step="0.01" placeholder="0.00" required>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">التصنيف الرئيسي</label>
                <select id="main_category_select" name="main_category_id" class="formal-select" required>
                    <option value="" disabled selected>-- اختر القسم الرئيسي --</option>
                    @foreach($mainCategories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 mb-5">
                <label class="formal-label">التصنيف الفرعي</label>
                <select id="sub_category_select" name="sub_category_id" class="formal-select" required disabled>
                    <option value="">-- اختر القسم الفرعي أولاً --</option>
                    @foreach($subCategories as $sub)
                        <option value="{{ $sub->id }}" data-main="{{ $sub->main_category_id }}">
                            {{ $sub->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-center mt-5 border-top pt-5">
            <button type="submit" class="btn-formal-primary">
                <i class='bx bx-check-double'></i> حفظ واعتماد العرض
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainSelect = document.getElementById('main_category_select');
        const subSelect = document.getElementById('sub_category_select');
        const allSubOptions = Array.from(subSelect.options).filter(opt => opt.value !== "");

        function filterSubCategories() {
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
                filteredOptions.forEach(opt => subSelect.appendChild(opt.cloneNode(true)));
            } else {
                subSelect.disabled = true;
            }
        }

        mainSelect.addEventListener('change', filterSubCategories);
    });
</script>
@endpush
@endsection
