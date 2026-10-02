<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'courier_id',
        'route_date',
        'status',
        'total_distance_meters',
        'total_duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'route_date' => 'date',
            'total_distance_meters' => 'integer',
            'total_duration_seconds' => 'integer',
        ];
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class)->orderBy('visit_order');
    }
}
