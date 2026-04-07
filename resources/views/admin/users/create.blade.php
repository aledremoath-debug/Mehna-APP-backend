@extends('admin.layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin/user-form.css') }}">

<div class="page-header">
    <div>
        <h1 class="page-title">إضافة مستخدم جديد</h1>
        <p class="page-subtitle">أدخل بيانات المستخدم الجديد</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn-cancel">
        <i class='bx bx-arrow-back'></i> رجوع
    </a>
</div>

<div class="form-card">
    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data">
        @csrf



        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">الاسم الكامل</label>
                <input type="text" name="full_name" class="form-input" value="{{ old('full_name') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label required">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">رقم الهاتف</label>
                <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label required">كلمة المرور</label>
                <input type="password" name="password" class="form-input" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group" id="location_group">
                <label class="form-label required">الموقع (المحافظة / المديرية)</label>
                <select name="location_id" id="location_id" class="form-input">
                    <option value="">اختر الموقع</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->governorate }} - {{ $location->district }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label required">نوع المستخدم</label>
                <select name="user_type" id="user_type" class="form-input" required onchange="toggleUserFields()">
                    <option value="">اختر النوع</option>
                    <option value="admin" {{ old('user_type') == 'admin' ? 'selected' : '' }}>مدير</option>
                    <option value="customer" {{ old('user_type') == 'customer' ? 'selected' : '' }}>عميل</option>
                    <option value="provider" {{ old('user_type') == 'provider' ? 'selected' : '' }}>مقدم خدمة</option>
                    <option value="vendor" {{ old('user_type') == 'vendor' ? 'selected' : '' }}>تاجر</option>
                </select>
            </div>
        </div>

        <div class="form-group" id="address_group">
            <label class="form-label required">وصف العنوان (شارع، معلم قريب...)</label>
            <textarea name="address_description" id="address_description" class="form-input" rows="2" placeholder="مثال: شارع حدة، خلف سوبر ماركت...">{{ old('address_description') }}</textarea>
        </div>

        {{-- حقول مقدم الخدمة --}}
        <div id="provider_fields" class="conditional-field">
            <h3 class="form-section-header">
                <i class='bx bx-briefcase'></i> بيانات مقدم الخدمة
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">المهنة الرئيسية</label>
                    <select name="main_category_id" class="form-input">
                        <option value="">اختر المهنة</option>
                        @foreach($mainCategories as $category)
                            <option value="{{ $category->id }}" {{ old('main_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">سنوات الخبرة</label>
                    <input type="number" name="experience_years" class="form-input" value="{{ old('experience_years') }}" min="0" placeholder="0">
                </div>

                <div class="form-group">
                    <label class="form-label">نبذة عني</label>
                    <textarea name="bio" class="form-input" rows="3" placeholder="اكتب نبذة مختصرة عنك...">{{ old('bio') }}</textarea>
                </div>
            </div>
        </div>

        {{-- حقول التاجر --}}
        <div id="vendor_fields" class="conditional-field">
            <h3 class="form-section-header">
                <i class='bx bx-store'></i> بيانات التاجر
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">اسم المتجر</label>
                    <input type="text" name="shop_name" class="form-input" value="{{ old('shop_name') }}" placeholder="أدخل اسم المتجر">
                </div>

                <div class="form-group">
                    <label class="form-label">رقم السجل التجاري</label>
                    <input type="text" name="commercial_register" class="form-input" value="{{ old('commercial_register') }}" placeholder="1234567890">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">وصف المتجر</label>
                <textarea name="shop_description" class="form-input" rows="3" placeholder="وصف مختصر عن المتجر والمنتجات...">{{ old('shop_description') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">صورة المتجر</label>
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
                var userType = document.getElementById('user_type').value;
                var providerFields = document.getElementById('provider_fields');
                var vendorFields = document.getElementById('vendor_fields');
                var locationGroup = document.getElementById('location_group');
                var addressGroup = document.getElementById('address_group');
                
                var locationInput = document.getElementById('location_id');
                var addressInput = document.getElementById('address_description');

                // إخفاء الكل أولاً
                providerFields.classList.remove('active');
                vendorFields.classList.remove('active');
                
                // التحكم في ظهور حقول الموقع والوصف
                if (userType === 'admin' || userType === '') {
                    locationGroup.style.display = 'none';
                    addressGroup.style.display = 'none';
                    locationInput.removeAttribute('required');
                    addressInput.removeAttribute('required');
                } else {
                    locationGroup.style.display = 'block';
                    addressGroup.style.display = 'block';
                    locationInput.setAttribute('required', 'required');
                    addressInput.setAttribute('required', 'required');
                }

                if (userType === 'provider') {
                    providerFields.classList.add('active');
                } else if (userType === 'vendor') {
                    vendorFields.classList.add('active');
                }
            }
            
            document.addEventListener('DOMContentLoaded', function() {
                toggleUserFields();
            });
        </script>

        <div class="form-actions">
            <a href="{{ route('admin.users.index') }}" class="btn-cancel">
                <i class='bx bx-x'></i> إلغاء
            </a>
            <button type="submit" class="btn-submit">
                <i class='bx bx-save'></i> حفظ المستخدم
            </button>
        </div>
    </form>
</div>
@endsection
