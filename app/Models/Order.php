<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'provider_id',
        'buyer_type',
        'seller_id',
        'status',
        'total_price',
        'location',
        'latitude',
        'longitude',
        'cancel_reason',
        'pending_at',
        'processing_at',
        'completed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'pending_at' => 'datetime',
        'processing_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!$order->pending_at && $order->status === 'pending') {
                $order->pending_at = now();
            }
        });

        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                $status = $order->status;
                $timestampField = $status . '_at';
                
                // If the field exists in the table, update it
                if (in_array($timestampField, ['pending_at', 'processing_at', 'completed_at', 'cancelled_at'])) {
                    $order->{$timestampField} = now();
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'order_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'order_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'order_id');
    }
}
