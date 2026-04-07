<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    // protected $primaryKey = 'id'; // Default is 'id'
    
    protected $fillable = [
        'governorate',
        'district'
    ];

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'location_id');
    }
}
