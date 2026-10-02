<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'vehicle_type',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
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
}
