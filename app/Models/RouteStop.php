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

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }
}
