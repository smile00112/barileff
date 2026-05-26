<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderStatusTransition as OrderStatusTransitionContract;

class OrderStatusTransition extends Model implements OrderStatusTransitionContract
{
    protected $fillable = [
        'from_status_code',
        'to_status_code',
        'delivery_type',
        'payment_type',
        'channel',
        'is_active',
        'priority',
        'conditions',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'conditions' => 'array',
        ];
    }

    public function setDeliveryTypeAttribute(?string $value): void
    {
        $this->attributes['delivery_type'] = $value !== '' ? $value : null;
    }

    public function setPaymentTypeAttribute(?string $value): void
    {
        $this->attributes['payment_type'] = $value !== '' ? $value : null;
    }

    public function setChannelAttribute(?string $value): void
    {
        $this->attributes['channel'] = $value !== '' ? $value : null;
    }
}
