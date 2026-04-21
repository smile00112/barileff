<?php

namespace Webkul\ExternalPayments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\ExternalPayments\Database\Factories\InventorySourceConfigFactory;
use Webkul\Inventory\Models\InventorySource;

class InventorySourceConfig extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'external_payment_inventory_source_configs';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get the inventory source this config belongs to.
     */
    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'inventory_source_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InventorySourceConfigFactory
    {
        return InventorySourceConfigFactory::new();
    }
}
