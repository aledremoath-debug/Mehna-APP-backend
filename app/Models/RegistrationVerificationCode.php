<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationVerificationCode extends Model
{
    protected $fillable = ['email', 'code', 'expires_at'];
}
