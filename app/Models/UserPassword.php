<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPassword extends Model
{
    // ربط الموديل بالجدول الصحيح
    protected $table = 'user_passwords';

    // السماح بتعبئة هذه الحقول
    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    /**
     * علاقة عكسية: كلمة المرور تنتمي لمستخدم واحد
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}