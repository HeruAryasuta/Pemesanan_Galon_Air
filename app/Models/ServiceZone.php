<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceZone extends Model
{
    protected $fillable = [
        'code',
        'name',
        'area_description',
        'slot_count',
    ];

    protected function casts(): array
    {
        return [
            'slot_count' => 'integer',
        ];
    }

    public function couriers()
    {
        return $this->hasMany(Courier::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
