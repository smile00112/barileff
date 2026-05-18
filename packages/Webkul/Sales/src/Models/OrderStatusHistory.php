<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Contracts\OrderStatusHistory as OrderStatusHistoryContract;

class OrderStatusHistory extends Model implements OrderStatusHistoryContract
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'user_type',
        'user_id',
        'user_name',
        'source',
    ];

    /**
     * The order this history entry belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}
