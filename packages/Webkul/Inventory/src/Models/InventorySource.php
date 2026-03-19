<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Inventory\Contracts\InventorySource as InventorySourceContract;
use Webkul\Inventory\Database\Factories\InventorySourceFactory;
use Webkul\User\Models\AdminProxy;

class InventorySource extends Model implements InventorySourceContract
{
    use HasFactory;

    protected $guarded = ['_token'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return InventorySourceFactory::new();
    }

    /**
     * Get the admins assigned to this inventory source.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminProxy::modelClass(),
            'admin_inventory_sources',
            'inventory_source_id',
            'admin_id'
        );
    }
}
