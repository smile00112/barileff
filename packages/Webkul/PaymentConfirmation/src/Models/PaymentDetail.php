<?php

namespace Webkul\PaymentConfirmation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\InventorySource;

class PaymentDetail extends Model
{
    protected $table = 'payment_confirmation_details';

    protected $fillable = [
        'title',
        'instructions',
        'inventory_source_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'inventory_source_id');
    }
}
