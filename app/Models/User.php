<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Default status and activity
            if (empty($user->approval_status)) {
                $user->approval_status = self::STATUS_PENDING;
            }
            $user->is_active = true;

            // Notify Admin for provider/seller requests (identified by existing related data or request context)
            // Note: Since we set user_type=0 in register, we might need to check if provider/seller records are being created
        });

        static::deleting(function ($user) {
            // 1. Delete associated Profile/Entity
            if ($user->seller) {
                $user->seller->delete();
            }
            if ($user->serviceProvider) {
                $user->serviceProvider->delete();
            }

            // 2. Delete personal history/data
            $user->passwordData()->delete();
            $user->maintenanceRequests()->delete();
            $user->reviewsReceived()->delete();
            $user->reviewsGiven()->delete();
            $user->notifications()->delete();

            // 3. Delete access tokens (Sanctum)
            $user->tokens()->delete();
        });
    }

    // تعطيل الـ timestamps لأن الجدول لا يحتوي على created_at و updated_at
    public $timestamps = true;

    // المفتاح الأساسي في قاعدة البيانات
    protected $primaryKey = 'user_id';

    // إخبار Eloquent أن المفتاح الأساسي هو رقم تلقائي متزايد لضمان استرجاعه بعد الحفظ
    public $incrementing = true;
    protected $keyType = 'int';

    // الأعمدة التي يمكن تعبئتها
    const TYPE_CUSTOMER = 0;
    const TYPE_PROVIDER = 1;
    const TYPE_SELLER = 2;
    const TYPE_ADMIN = 9; // رقم مميز للإدارة

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'full_name',
        'profile_image',
        'email',
        'phone',
        'user_type',
        'is_active',
        'approval_status',
        'rejection_reason',
        'location_id',
        'address_description',
        'fcm_token',
        'user_token'
    ];

    // دالة للتحقق مما إذا كان المستخدم مديراً
    public function isAdmin(): bool
    {
        return $this->user_type === self::TYPE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->user_type === self::TYPE_CUSTOMER;
    }

    public function isProvider(): bool
    {
        return $this->user_type === self::TYPE_PROVIDER;
    }

    public function isSeller(): bool
    {
        return $this->user_type === self::TYPE_SELLER;
    }

    public function getUserTypeLabelAttribute()
    {
        return match ($this->user_type) {
            self::TYPE_ADMIN => 'مدير',
            self::TYPE_PROVIDER => 'مقدم خدمة',
            self::TYPE_SELLER => 'تاجر',
            default => 'عميل',
        };
    }
    // إخفاء بعض الحقول في جيسون API
    protected $hidden = [
        // 'password', // Removed because handled by UserPassword model
    ];

    // نوع التحويل (Casting)
    protected $casts = [
        // 'password' => 'hashed', // Removed because handled by UserPassword model
        'user_type' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['profile_image_url', 'user_type_label'];

    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image ? asset('media/' . $this->profile_image) : null;
    }


    public function passwordData()
    {
        return $this->hasOne(UserPassword::class, 'user_id', 'user_id');
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->passwordData ? $this->passwordData->password_hash : null;
    }

    // --- العلاقات (Relationships) ---

    public function provider()
    {
        return $this->hasOne(ServiceProvider::class, 'user_id', 'user_id');
    }

    public function serviceProvider()
    {
        return $this->hasOne(ServiceProvider::class, 'user_id', 'user_id');
    }

    // تم التعديل من vendor إلى seller ليتوافق مع طلبك
    public function seller()
    {
        return $this->hasOne(Seller::class, 'user_id', 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'rated_id', 'user_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'rater_id', 'user_id');
    }

    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'customer_id', 'user_id');
    }
}