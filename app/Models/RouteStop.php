<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $fillable = [
        'route_id',
        'delivery_id',
        'visit_order',
        'eta',
        'distance_from_previous_meters',
    ];

    protected function casts(): array
    {
        return [
            'visit_order' => 'integer',
            'eta' => 'datetime',
            'distance_from_previous_meters' => 'integer',
        ];
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
