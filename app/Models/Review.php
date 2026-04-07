<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'rater_id',
        'rated_id',
        'order_id',
        'maintenance_request_id',
        'rating',
        'comment'
    ];

    /**
     * العميل الذي قام بالتقييم
     */
    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id', 'user_id');
    }

    /**
     * الشخص الذي تم تقييمه (فني أو تاجر)
     */
    public function rated()
    {
        return $this->belongsTo(User::class, 'rated_id', 'user_id');
    }

    /**
     * الطلب المرتبط بالتقييم (في حالة المنتجات)
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * طلب الصيانة المرتبط بالتقييم (في حالة الخدمات)
     */
    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }
}
