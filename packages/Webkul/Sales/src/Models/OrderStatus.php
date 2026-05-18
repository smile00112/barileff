<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Sales\Contracts\OrderStatus as OrderStatusContract;

class OrderStatus extends Model implements OrderStatusContract
{
    protected $fillable = [
        'code',
        'name',
        'icon',
        'color',
        'sort_order',
        'is_system',
        'is_active',
        'is_terminal',
        'is_cancel_state',
        'is_payment_required',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_system'           => 'boolean',
            'is_active'           => 'boolean',
            'is_terminal'         => 'boolean',
            'is_cancel_state'     => 'boolean',
            'is_payment_required' => 'boolean',
            'meta'                => 'array',
        ];
    }

    /**
     * Transitions where this status is the source.
     */
    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(OrderStatusTransitionProxy::modelClass(), 'from_status_code', 'code');
    }

    /**
     * Transitions where this status is the target.
     */
    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(OrderStatusTransitionProxy::modelClass(), 'to_status_code', 'code');
    }
}
