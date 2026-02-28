<?php

namespace Webkul\DeliveryZones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Models\InventorySource;

class DeliveryZone extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'polygon_json' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<DeliveryCity, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(DeliveryCity::class, 'city_id');
    }

    /**
     * @return HasMany<DeliveryZoneRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryZoneRate::class, 'zone_id');
    }

    /**
     * @return BelongsToMany<InventorySource, $this>
     */
    public function inventory_sources(): BelongsToMany
    {
        return $this->belongsToMany(
            InventorySource::class,
            'delivery_zone_inventory_sources',
            'zone_id',
            'inventory_source_id'
        );
    }
}
