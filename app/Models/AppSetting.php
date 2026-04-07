<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'android_version',
        'ios_version',
        'android_update_mandatory',
        'ios_update_mandatory',
        'android_update_disabled',
        'ios_update_disabled',
        'android_store_url',
        'ios_store_url',
        'ai_assistant_enabled',
    ];

    protected $casts = [
        'android_update_mandatory'  => 'boolean',
        'ios_update_mandatory'      => 'boolean',
        'android_update_disabled'   => 'boolean',
        'ios_update_disabled'       => 'boolean',
        'ai_assistant_enabled'      => 'boolean',
    ];
}
