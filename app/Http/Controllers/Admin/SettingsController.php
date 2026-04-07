<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * عرض صفحة إعدادات التطبيق
     */
    public function index()
    {
        $settings = AppSetting::first();

        if (!$settings) {
            $settings = AppSetting::create([
                'android_version'          => '1.0.0',
                'ios_version'              => '1.0.0',
                'android_update_mandatory' => false,
                'ios_update_mandatory'     => false,
            ]);
        }

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * حفظ إعدادات التطبيق
     */
    public function update(Request $request)
    {
        $request->validate([
            'android_version' => 'required|string|max:20',
            'ios_version'     => 'required|string|max:20',
        ], [
            'android_version.required' => 'رقم إصدار Android مطلوب.',
            'ios_version.required'     => 'رقم إصدار iOS مطلوب.',
        ]);

        $settings = AppSetting::first() ?? new AppSetting();

        $settings->android_version = $request->android_version;
        $settings->ios_version     = $request->ios_version;

        // Android Logic
        $androidType = $request->input('android_update_type');
        $settings->android_update_mandatory = ($androidType === 'mandatory');
        $settings->android_update_disabled  = ($androidType === 'disabled');

        // iOS Logic
        $iosType = $request->input('ios_update_type');
        $settings->ios_update_mandatory = ($iosType === 'mandatory');
        $settings->ios_update_disabled  = ($iosType === 'disabled');

        $settings->save();

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم حفظ إعدادات التطبيق بنجاح.');
    }
}
