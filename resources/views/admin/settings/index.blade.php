@extends('admin.layouts.app')

@section('title', 'إعدادات التطبيق')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/settings.css') }}">
@endpush

@section('content')

    {{-- Page Header --}}
    <div class="settings-page-header">
        <div class="header-icon"><i class="bx bx-cog"></i></div>
        <div>
            <h1>إعدادات التطبيق</h1>
            <p>إدارة إصدارات التطبيق وإعدادات التحديث لكل منصة</p>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="settings-alert success">
            <i class="bx bx-check-circle" style="font-size:1.3rem;"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="settings-alert error">
            <i class="bx bx-error-circle" style="font-size:1.3rem;"></i>
            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" id="settingsForm">
        @csrf
        @method('PUT')

        <div class="settings-grid">

            {{-- ═══ Android Card ═══ --}}
            <div class="platform-card">
                <div class="platform-card-header">
                    <div class="platform-logo android-logo">
                        <i class="bx bxl-android"></i>
                    </div>
                    <div>
                        <h2>Android</h2>
                        <span>إعدادات تطبيق أندرويد</span>
                    </div>
                </div>
                <div class="platform-card-body">

                    {{-- Version --}}
                    <label class="field-label">رقم الإصدار الحالي</label>
                    <div class="version-input-wrap">
                        <i class="bx bx-code-alt version-icon"></i>
                        <input type="text"
                               name="android_version"
                               class="version-input"
                               value="{{ old('android_version', $settings->android_version) }}"
                               placeholder="مثال: 1.2.0"
                               required>
                    </div>

                    {{-- Update Type --}}
                    <div class="update-type-section">
                        <span class="update-type-label">نوع التحديث</span>
                        <div class="radio-group" id="android-radio-group">

                            {{-- Mandatory --}}
                            @php
                                $androidType = ($settings->android_update_disabled ?? false)
                                    ? 'disabled'
                                    : (($settings->android_update_mandatory ?? false) ? 'mandatory' : 'optional');
                            @endphp

                            <label class="radio-option {{ $androidType === 'mandatory' ? 'selected-mandatory' : '' }}"
                                   id="android-mandatory-option">
                                <input type="radio" name="android_update_type"
                                       value="mandatory"
                                       {{ $androidType === 'mandatory' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>تحديث إجباري</strong>
                                    <small>تظهر رسالة للمستخدم تُلزمه بتحديث التطبيق قبل الاستمرار</small>
                                </div>
                                <span class="badge-mandatory">إجباري</span>
                            </label>

                            {{-- Optional --}}
                            <label class="radio-option {{ $androidType === 'optional' ? 'selected-optional' : '' }}"
                                   id="android-optional-option">
                                <input type="radio" name="android_update_type"
                                       value="optional"
                                       {{ $androidType === 'optional' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>تحديث اختياري</strong>
                                    <small>يُعلَم المستخدم بوجود تحديث ويمكنه تجاهله والمتابعة</small>
                                </div>
                                <span class="badge-optional">اختياري</span>
                            </label>

                            {{-- Disabled --}}
                            <label class="radio-option {{ $androidType === 'disabled' ? 'selected-disabled' : '' }}"
                                   id="android-disabled-option">
                                <input type="radio" name="android_update_type"
                                       value="disabled"
                                       {{ $androidType === 'disabled' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>إلغاء التحديث</strong>
                                    <small>تعطيل أي طلب تحديث تماماً — لن يُعلَم المستخدم بأي تحديث</small>
                                </div>
                                <span class="badge-disabled">معطّل</span>
                            </label>

                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══ iOS Card ═══ --}}
            <div class="platform-card">
                <div class="platform-card-header">
                    <div class="platform-logo ios-logo">
                        <i class="bx bxl-apple"></i>
                    </div>
                    <div>
                        <h2>iOS</h2>
                        <span>إعدادات تطبيق آبل</span>
                    </div>
                </div>
                <div class="platform-card-body">

                    {{-- Version --}}
                    <label class="field-label">رقم الإصدار الحالي</label>
                    <div class="version-input-wrap">
                        <i class="bx bx-code-alt version-icon"></i>
                        <input type="text"
                               name="ios_version"
                               class="version-input"
                               value="{{ old('ios_version', $settings->ios_version) }}"
                               placeholder="مثال: 1.2.0"
                               required>
                    </div>

                    {{-- Update Type --}}
                    <div class="update-type-section">
                        <span class="update-type-label">نوع التحديث</span>
                        <div class="radio-group" id="ios-radio-group">

                            @php
                                $iosType = ($settings->ios_update_disabled ?? false)
                                    ? 'disabled'
                                    : (($settings->ios_update_mandatory ?? false) ? 'mandatory' : 'optional');
                            @endphp

                            {{-- Mandatory --}}
                            <label class="radio-option {{ $iosType === 'mandatory' ? 'selected-mandatory' : '' }}"
                                   id="ios-mandatory-option">
                                <input type="radio" name="ios_update_type"
                                       value="mandatory"
                                       {{ $iosType === 'mandatory' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>تحديث إجباري</strong>
                                    <small>تظهر رسالة للمستخدم تُلزمه بتحديث التطبيق قبل الاستمرار</small>
                                </div>
                                <span class="badge-mandatory">إجباري</span>
                            </label>

                            {{-- Optional --}}
                            <label class="radio-option {{ $iosType === 'optional' ? 'selected-optional' : '' }}"
                                   id="ios-optional-option">
                                <input type="radio" name="ios_update_type"
                                       value="optional"
                                       {{ $iosType === 'optional' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>تحديث اختياري</strong>
                                    <small>يُعلَم المستخدم بوجود تحديث ويمكنه تجاهله والمتابعة</small>
                                </div>
                                <span class="badge-optional">اختياري</span>
                            </label>

                            {{-- Disabled --}}
                            <label class="radio-option {{ $iosType === 'disabled' ? 'selected-disabled' : '' }}"
                                   id="ios-disabled-option">
                                <input type="radio" name="ios_update_type"
                                       value="disabled"
                                       {{ $iosType === 'disabled' ? 'checked' : '' }}>
                                <div class="radio-custom"></div>
                                <div class="radio-text">
                                    <strong>إلغاء التحديث</strong>
                                    <small>تعطيل أي طلب تحديث تماماً — لن يُعلَم المستخدم بأي تحديث</small>
                                </div>
                                <span class="badge-disabled">معطّل</span>
                            </label>

                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- end grid --}}

        {{-- Save Button --}}
        <div class="settings-footer">
            <button type="submit" class="btn-save-settings">
                <i class="bx bx-save"></i>
                حفظ الإعدادات
            </button>
        </div>

    </form>

<script>
    function setupRadioGroup(groupId, options) {
        const group = document.getElementById(groupId);
        if (!group) return;

        const radios = group.querySelectorAll('input[type="radio"]');

        function updateStyles() {
            radios.forEach(radio => {
                const option = radio.closest('.radio-option');
                option.classList.remove('selected-mandatory', 'selected-optional', 'selected-disabled');
                if (radio.checked) {
                    if (radio.value === 'mandatory') option.classList.add('selected-mandatory');
                    else if (radio.value === 'optional') option.classList.add('selected-optional');
                    else if (radio.value === 'disabled') option.classList.add('selected-disabled');
                }
            });
        }

        radios.forEach(radio => radio.addEventListener('change', updateStyles));

        options.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                updateStyles();
            });
        });
    }

    setupRadioGroup('android-radio-group', ['android-mandatory-option', 'android-optional-option', 'android-disabled-option']);
    setupRadioGroup('ios-radio-group',     ['ios-mandatory-option',     'ios-optional-option',     'ios-disabled-option']);
</script>

@endsection
