<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'vehicle_type',
        'is_available'
    ];

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function routes()
    {
        return $this->hasMany(Route::class);
    }
}
