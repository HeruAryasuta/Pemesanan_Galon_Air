<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierApplication extends Model
{
    protected $hidden = [
        'password_hash',
    ];

    protected $fillable = [
        'name',
        'phone',
        'nik',
        'email',
        'vehicle_type',
        'has_vehicle',
        'password_hash',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'has_vehicle' => 'boolean',
            'reviewed_at' => 'datetime',
            'nik' => 'encrypted',
        ];
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
