<?php

namespace Webkul\DeliveryZones\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryCity extends Model
{
    protected $guarded = ['id'];

    /**
     * @return HasMany<DeliveryZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'city_id');
    }
}
