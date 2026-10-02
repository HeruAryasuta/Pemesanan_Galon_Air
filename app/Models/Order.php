<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'address_id',
        'status',
        'total_price',
        'delivery_fee',
        'payment_method',
        'payment_status',
        'payment_proof_path',
        'payment_review_note',
        'payment_submitted_at',
        'payment_reviewed_at',
        'notes',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'integer',
            'delivery_fee' => 'integer',
            'ordered_at' => 'datetime',
            'payment_submitted_at' => 'datetime',
            'payment_reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
}
