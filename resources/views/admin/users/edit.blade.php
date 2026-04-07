@extends('admin.layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/user-form.css') }}">

<div class="page-header">
    <div>
        <h1 class="page-title">تعديل المستخدم</h1>
        <p class="page-subtitle">تحديث بيانات المستخدم: {{ $user->full_name }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn-cancel">
        <i class='bx bx-arrow-back'></i> رجوع
    </a>
</div>

<div class="form-card">
    <form action="{{ route('admin.users.update', $user->user_id) }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('error'))
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <div class="alert-content">
                    <strong>خطأ:</strong>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                <i class='bx bx-check-circle'></i>
                <div class="alert-content">
                    <strong>نجاح:</strong>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <i class='bx bx-error-circle'></i>
                <div class="alert-content">
                    <strong>يوجد أخطاء في النموذج:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">الاسم الكامل</label>
                <input type="text" name="full_name" class="form-input" value="{{ old('full_name', $user->full_name) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label required">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone', $user->phone) }}">
            </div>

            <div class="form-group">
                <label class="form-label required">نوع المستخدم</label>
                <select name="user_type" class="form-input" required>
                    <option value="admin" {{ $user->user_type == 9 ? 'selected' : '' }}>مدير</option>
                    <option value="customer" {{ $user->user_type == 0 ? 'selected' : '' }}>عميل</option>
                    <option value="provider" {{ $user->user_type == 1 ? 'selected' : '' }}>مقدم خدمة</option>
                    <option value="vendor" {{ $user->user_type == 2 ? 'selected' : '' }}>تاجر</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">كلمة المرور الجديدة</label>
            <input type="password" name="password" class="form-input" placeholder="اتركه فارغاً إذا كنت لا تريد تغييره">
            <small class="text-meta">
                <i class='bx bx-info-circle'></i> اترك هذا الحقل فارغاً إذا كنت لا تريد تغيير كلمة المرور
            </small>
        </div>

        @if($user->location)
        <div class="form-group">
            <label class="form-label">الموقع الحالي</label>
            <div class="form-input-readonly">
                <i class='bx bx-map'></i> {{ $user->location->governorate }} - {{ $user->location->district }}
            </div>
            <small class="text-meta">
                <i class='bx bx-info-circle'></i> لتغيير الموقع، يرجى التواصل مع المطور
            </small>
        </div>
        @endif

        {{-- حقول مقدم الخدمة --}}
        <div id="provider_fields" class="conditional-field {{ $user->user_type == 1 ? 'active' : '' }}">
            <h3 class="form-section-header">
                <i class='bx bx-briefcase'></i> بيانات مقدم الخدمة
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">المهنة الرئيسية</label>
                    <select name="main_category_id" class="form-input">
                        <option value="">اختر المهنة</option>
                        @foreach($mainCategories as $category)
                            <option value="{{ $category->id }}" {{ old('main_category_id', $user->serviceProvider->main_category_id ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">سنوات الخبرة</label>
                    <input type="number" name="experience_years" class="form-input" value="{{ old('experience_years', $user->serviceProvider->experience_years ?? '') }}" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label">نبذة عني</label>
                    <textarea name="bio" class="form-input" rows="3" placeholder="اكتب نبذة مختصرة عنك...">{{ old('bio', $user->serviceProvider->bio ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- حقول التاجر --}}
        <div id="vendor_fields" class="conditional-field {{ $user->user_type == 2 ? 'active' : '' }}">
            <h3 class="form-section-header">
                <i class='bx bx-store'></i> بيانات التاجر
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">اسم المتجر</label>
                    <input type="text" name="shop_name" class="form-input" value="{{ old('shop_name', $user->seller->shop_name ?? '') }}" placeholder="أدخل اسم المتجر">
                </div>

                <div class="form-group">
                    <label class="form-label">رقم السجل التجاري</label>
                    <input type="text" name="commercial_register" class="form-input" value="{{ old('commercial_register', $user->seller->commercial_register ?? '') }}" placeholder="1234567890">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">وصف المتجر</label>
                <textarea name="shop_description" class="form-input" rows="3" placeholder="وصف مختصر عن المتجر والمنتجات...">{{ old('shop_description', $user->seller->shop_description ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">صورة المتجر</label>
                @if(isset($user->seller->shop_image))
                    <div class="mb-10">
                        <img src="{{ asset('storage/' . $user->seller->shop_image) }}" alt="Shop Image" class="shop-img-preview">
                    </div>
                @endif
                <div class="file-upload-wrapper">
                    <label for="shop_image" class="file-upload-label">
                        <i class='bx bx-image-add'></i>
                        <div class="file-upload-text">
                            <strong>اختر صورة المتجر</strong>
                            <span>PNG, JPG, أو JPEG (الحد الأقصى: 2MB)</span>
                        </div>
                    </label>
                    <input type="file" id="shop_image" name="shop_image" accept="image/*">
                </div>
            </div>
        </div>

        <script>
            function toggleUserFields() {
                var userType = document.querySelector('select[name="user_type"]').value;
                var providerFields = document.getElementById('provider_fields');
                var vendorFields = document.getElementById('vendor_fields');

                providerFields.classList.remove('active');
                vendorFields.classList.remove('active');

                if (userType === 'provider' || userType == 1) {
                    providerFields.classList.add('active');
                } else if (userType === 'vendor' || userType == 2) {
                    vendorFields.classList.add('active');
                }
            }
            
            document.querySelector('select[name="user_type"]').addEventListener('change', toggleUserFields);
            
            // Run on load
            document.addEventListener('DOMContentLoaded', function() {
                toggleUserFields();
            });
        </script>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
                <span>
                    <i class='bx bx-check-circle'></i> حساب نشط
                </span>
            </label>
        </div>

        <div class="info-card">
            <i class='bx bx-info-circle'></i>
            <div>
                <strong class="info-card-title">معلومة:</strong>
                <p class="info-card-text">
                    رقم المستخدم: <strong>{{ $user->user_id }}</strong>
                    @if($user->created_at)
                     | تاريخ التسجيل: <strong>{{ $user->created_at->format('Y-m-d H:i') }}</strong>
                    @endif
                </p>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('admin.users.index') }}" class="btn-cancel">
                <i class='bx bx-x'></i> إلغاء
            </a>
            <button type="submit" class="btn-submit">
                <i class='bx bx-save'></i> حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
