@extends('admin.layouts.app')

@section('title', 'إعدادات التطبيق')

@section('content')
<div class="page-header" style="margin-bottom: 30px;">
    <h1 class="page-title" style="font-size: 1.8rem; font-weight: 800; color: #1e293b; margin: 0;">
        إعدادات التطبيق
    </h1>
    <p style="color: #64748b; margin-top: 5px;">إدارة إصدارات التطبيق وإعدادات التحديث لكل منصة</p>
</div>

@if(session('success'))
<div style="background:#d1fae5; border:1px solid #6ee7b7; border-radius:12px; padding:14px 20px; margin-bottom:20px; color:#065f46; font-weight:600;">
    ✅ {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('admin.settings.save') }}">
    @csrf

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

        {{-- ─── Android ─────────────────────────────────── --}}
        <div class="premium-table-card" style="padding: 28px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#34d399,#10b981);display:flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M6 18c0 .55.45 1 1 1h1v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h2v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h1c.55 0 1-.45 1-1V8H6v10zM3.5 8C2.67 8 2 8.67 2 9.5v7c0 .83.67 1.5 1.5 1.5S5 17.33 5 16.5v-7C5 8.67 4.33 8 3.5 8zm17 0c-.83 0-1.5.67-1.5 1.5v7c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-7c0-.83-.67-1.5-1.5-1.5zm-4.97-5.84l1.3-1.3c.2-.2.2-.51 0-.71-.2-.2-.51-.2-.71 0l-1.48 1.48A5.84 5.84 0 0 0 12 1.5c-.96 0-1.86.23-2.66.63L7.85.65c-.2-.2-.51-.2-.71 0-.2.2-.2.51 0 .71l1.31 1.31A5.957 5.957 0 0 0 6.06 6H18a5.96 5.96 0 0 0-2.47-3.84zM10 5H9V4h1v1zm5 0h-1V4h1v1z"/></svg>
                </div>
                <h2 style="font-size:1.2rem; font-weight:700; color:#1e293b; margin:0;">Android</h2>
            </div>

            <div class="form-group" style="margin-bottom:18px;">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:8px; font-size:.9rem;">الإصدار المطلوب</label>
                <input type="text" name="android_version"
                    value="{{ old('android_version', $settings->android_version ?? '1.0.0') }}"
                    placeholder="مثال: 2.0.0"
                    style="width:100%; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:12px; font-family:inherit; font-size:.95rem; outline:none; transition:border .2s;"
                    onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                @error('android_version')<p style="color:#ef4444; font-size:.8rem; margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" style="margin-bottom:18px;">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:8px; font-size:.9rem;">رابط Google Play</label>
                <input type="url" name="android_store_url"
                    value="{{ old('android_store_url', $settings->android_store_url ?? '') }}"
                    placeholder="https://play.google.com/store/apps/..."
                    style="width:100%; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:12px; font-family:inherit; font-size:.85rem; direction:ltr; outline:none; transition:border .2s;"
                    onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div class="form-group">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:10px; font-size:.9rem;">نوع التحديث</label>
                <div style="display:flex; gap:12px;">
                    <label style="flex:1; border:2px solid {{ (old('android_update_mandatory', $settings->android_update_mandatory ?? false) == false) ? '#0ea5e9' : '#e2e8f0' }}; border-radius:12px; padding:14px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:.2s;" id="lbl-android-optional">
                        <input type="radio" name="android_update_mandatory" value="0"
                            {{ old('android_update_mandatory', $settings->android_update_mandatory ?? false) ? '' : 'checked' }}
                            onchange="toggleBorder('android','optional')" style="accent-color:#0ea5e9;">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:.9rem;">اختياري</div>
                            <div style="font-size:.75rem; color:#64748b;">يمكن تجاوزه</div>
                        </div>
                    </label>
                    <label style="flex:1; border:2px solid {{ (old('android_update_mandatory', $settings->android_update_mandatory ?? false)) ? '#ef4444' : '#e2e8f0' }}; border-radius:12px; padding:14px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:.2s;" id="lbl-android-mandatory">
                        <input type="radio" name="android_update_mandatory" value="1"
                            {{ old('android_update_mandatory', $settings->android_update_mandatory ?? false) ? 'checked' : '' }}
                            onchange="toggleBorder('android','mandatory')" style="accent-color:#ef4444;">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:.9rem;">إجباري</div>
                            <div style="font-size:.75rem; color:#64748b;">يمنع الاستخدام</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- ─── iOS ─────────────────────────────────────── --}}
        <div class="premium-table-card" style="padding: 28px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
                <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#818cf8,#6366f1);display:flex;align-items:center;justify-content:center;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M22 17.607c-.786 2.28-3.139 6.317-5.563 6.361-1.608.031-2.125-.953-3.963-.953-1.837 0-2.412.923-3.932.983-2.572.099-6.542-5.827-6.542-10.995 0-4.747 3.308-7.1 6.198-7.143 1.55-.028 3.014 1.045 3.959 1.045.944 0 2.718-1.293 4.578-1.103.782.033 2.979.315 4.389 2.377l-.893.58c-.763-1.168-2.084-1.84-3.496-1.808-2.714.061-4.785 2.196-4.785 4.925 0 3.06 2.437 5.488 5.542 5.488 2.108 0 2.726-1.157 4.509-1.803z"/></svg>
                </div>
                <h2 style="font-size:1.2rem; font-weight:700; color:#1e293b; margin:0;">iOS</h2>
            </div>

            <div class="form-group" style="margin-bottom:18px;">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:8px; font-size:.9rem;">الإصدار المطلوب</label>
                <input type="text" name="ios_version"
                    value="{{ old('ios_version', $settings->ios_version ?? '1.0.0') }}"
                    placeholder="مثال: 2.0.0"
                    style="width:100%; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:12px; font-family:inherit; font-size:.95rem; outline:none; transition:border .2s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('ios_version')<p style="color:#ef4444; font-size:.8rem; margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div class="form-group" style="margin-bottom:18px;">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:8px; font-size:.9rem;">رابط App Store</label>
                <input type="url" name="ios_store_url"
                    value="{{ old('ios_store_url', $settings->ios_store_url ?? '') }}"
                    placeholder="https://apps.apple.com/..."
                    style="width:100%; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:12px; font-family:inherit; font-size:.85rem; direction:ltr; outline:none; transition:border .2s;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <div class="form-group">
                <label style="display:block; font-weight:600; color:#334155; margin-bottom:10px; font-size:.9rem;">نوع التحديث</label>
                <div style="display:flex; gap:12px;">
                    <label style="flex:1; border:2px solid {{ (old('ios_update_mandatory', $settings->ios_update_mandatory ?? false) == false) ? '#6366f1' : '#e2e8f0' }}; border-radius:12px; padding:14px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:.2s;" id="lbl-ios-optional">
                        <input type="radio" name="ios_update_mandatory" value="0"
                            {{ old('ios_update_mandatory', $settings->ios_update_mandatory ?? false) ? '' : 'checked' }}
                            onchange="toggleBorder('ios','optional')" style="accent-color:#6366f1;">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:.9rem;">اختياري</div>
                            <div style="font-size:.75rem; color:#64748b;">يمكن تجاوزه</div>
                        </div>
                    </label>
                    <label style="flex:1; border:2px solid {{ (old('ios_update_mandatory', $settings->ios_update_mandatory ?? false)) ? '#ef4444' : '#e2e8f0' }}; border-radius:12px; padding:14px; cursor:pointer; display:flex; align-items:center; gap:10px; transition:.2s;" id="lbl-ios-mandatory">
                        <input type="radio" name="ios_update_mandatory" value="1"
                            {{ old('ios_update_mandatory', $settings->ios_update_mandatory ?? false) ? 'checked' : '' }}
                            onchange="toggleBorder('ios','mandatory')" style="accent-color:#ef4444;">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:.9rem;">إجباري</div>
                            <div style="font-size:.75rem; color:#64748b;">يمنع الاستخدام</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Save button --}}
    <div style="margin-top:28px; text-align:left;">
        <button type="submit"
            style="background:linear-gradient(135deg,#0ea5e9,#0284c7); color:white; border:none; padding:14px 40px; border-radius:14px; font-size:1rem; font-weight:700; font-family:inherit; cursor:pointer; box-shadow:0 4px 15px rgba(14,165,233,.35); transition:.2s;"
            onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
            💾 حفظ الإعدادات
        </button>
    </div>
</form>

<script>
function toggleBorder(platform, type) {
    const optColor   = platform === 'android' ? '#0ea5e9' : '#6366f1';
    const mandColor  = '#ef4444';
    const neutral    = '#e2e8f0';

    document.getElementById('lbl-' + platform + '-optional').style.borderColor  = type === 'optional'  ? optColor  : neutral;
    document.getElementById('lbl-' + platform + '-mandatory').style.borderColor = type === 'mandatory' ? mandColor : neutral;
}
</script>
@endsection
