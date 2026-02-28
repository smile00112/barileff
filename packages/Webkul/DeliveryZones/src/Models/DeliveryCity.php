<?php

namespace Webkul\DeliveryZones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCity extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'polygon_json' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<DeliveryZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'city_id');
    }
}
