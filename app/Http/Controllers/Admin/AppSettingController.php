<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    /**
     * Show the admin settings page.
     */
    public function showPage()
    {
        $settings = AppSetting::latest()->first();
        return view('admin.settings.app_settings', compact('settings'));
    }

    /**
     * Public API – Flutter checks current required version (no auth needed).
     */
    public function publicCheck()
    {
        $s = AppSetting::latest()->first();

        if (!$s) {
            return response()->json([
                'status'                    => true,
                'android_version'           => '1.0.0',
                'ios_version'               => '1.0.0',
                'android_update_mandatory'  => false,
                'ios_update_mandatory'      => false,
                'android_store_url'         => '',
                'ios_store_url'             => '',
                'ai_assistant_enabled'      => true,
            ]);
        }

        return response()->json([
            'status'                    => true,
            'android_version'           => $s->android_version,
            'ios_version'               => $s->ios_version,
            'android_update_mandatory'  => $s->android_update_mandatory,
            'ios_update_mandatory'      => $s->ios_update_mandatory,
            'android_update_disabled'   => $s->android_update_disabled ?? false,
            'ios_update_disabled'       => $s->ios_update_disabled ?? false,
            'android_store_url'         => $s->android_store_url ?? '',
            'ios_store_url'             => $s->ios_store_url ?? '',
            'ai_assistant_enabled'      => (bool) ($s->ai_assistant_enabled ?? true),
        ]);
    }

    /**
     * Public API – Return all settings for general app use.
     */
    public function index()
    {
        $settings = AppSetting::latest()->first();
        return response()->json([
            'status' => true,
            'data'   => $settings
        ]);
    }

    /**
     * Admin updates settings via web form.
     */
    public function saveFromForm(Request $request)
    {
        $request->validate([
            'android_version'           => 'required|string|max:20',
            'ios_version'               => 'required|string|max:20',
            'android_update_type'       => 'required|in:mandatory,optional,disabled',
            'ios_update_type'           => 'required|in:mandatory,optional,disabled',
            'android_store_url'         => 'nullable|url',
            'ios_store_url'             => 'nullable|url',
        ]);

        $data = [
            'android_version'           => $request->android_version,
            'ios_version'               => $request->ios_version,
            'android_update_mandatory'  => ($request->android_update_type === 'mandatory'),
            'android_update_disabled'   => ($request->android_update_type === 'disabled'),
            'ios_update_mandatory'      => ($request->ios_update_type === 'mandatory'),
            'ios_update_disabled'       => ($request->ios_update_type === 'disabled'),
            'android_store_url'         => $request->android_store_url,
            'ios_store_url'             => $request->ios_store_url,
        ];

        $settings = AppSetting::latest()->first();
        if ($settings) {
            $settings->update($data);
        } else {
            AppSetting::create($data);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم تحديث إعدادات التطبيق بنجاح.');
    }

    /**
     * Legacy API update (admin token, JSON).
     */
    public function update(Request $request)
    {
        $request->validate([
            'android_version'          => 'required|string',
            'ios_version'              => 'required|string',
            'android_update_mandatory' => 'required|boolean',
            'ios_update_mandatory'     => 'required|boolean',
            'android_store_url'        => 'nullable|string',
            'ios_store_url'            => 'nullable|string',
        ]);

        $settings = AppSetting::latest()->first();
        if ($settings) {
            $settings->update($request->all());
        } else {
            $settings = AppSetting::create($request->all());
        }

        return response()->json([
            'message' => 'تم تحديث إعدادات التطبيق بنجاح.',
            'data'    => $settings,
        ]);
    }
}
