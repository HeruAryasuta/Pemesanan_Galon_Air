<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'nik',
        'vehicle_type',
        'service_zone_id',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'nik' => 'encrypted',
            'service_zone_id' => 'integer',
        ];
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function routes()
    {
        return $this->hasMany(Route::class);
    }

    public function serviceZone()
    {
        return $this->belongsTo(ServiceZone::class);
    }
}
