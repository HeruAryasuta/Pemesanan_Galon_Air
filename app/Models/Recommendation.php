<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'score',
        'reason',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'generated_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
