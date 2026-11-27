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
        'discount_amount',
        'order_address_label',
        'order_address_text',
        'order_latitude',
        'order_longitude',
        'order_service_zone_id',
        'order_service_zone_name',
        'order_service_zone_area',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'stock_restored_at',
        'refund_status',
        'refund_reference',
        'refund_note',
        'refunded_at',
        'refund_processed_by',
        'delivery_fee',
        'payment_method',
        'payment_status',
        'payment_deadline_at',
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
            'discount_amount' => 'integer',
            'delivery_fee' => 'integer',
            'order_latitude' => 'decimal:7',
            'order_longitude' => 'decimal:7',
            'cancelled_at' => 'datetime',
            'stock_restored_at' => 'datetime',
            'refunded_at' => 'datetime',
            'ordered_at' => 'datetime',
            'payment_deadline_at' => 'datetime',
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

    public function getDeliveryAddressLabelAttribute(): ?string
    {
        return $this->order_address_label ?? $this->address?->label;
    }

    public function getDeliveryAddressAttribute(): ?string
    {
        return $this->order_address_text ?? $this->address?->full_address;
    }

    public function getDeliveryLatitudeAttribute(): ?string
    {
        return $this->order_latitude ?? $this->address?->latitude;
    }

    public function getDeliveryLongitudeAttribute(): ?string
    {
        return $this->order_longitude ?? $this->address?->longitude;
    }

    public function getDeliveryServiceZoneIdAttribute(): ?int
    {
        return $this->order_service_zone_id ?? $this->address?->service_zone_id;
    }

    public function getDeliveryServiceZoneNameAttribute(): ?string
    {
        return $this->order_service_zone_name ?? $this->address?->serviceZone?->name;
    }

    public function getDeliveryServiceZoneAreaAttribute(): ?string
    {
        return $this->order_service_zone_area ?? $this->address?->serviceZone?->area_description;
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
