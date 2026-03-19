<?php

namespace Webkul\Markup\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Inventory\Models\InventorySourceProxy;
use Webkul\Markup\Contracts\MarkupGroup as MarkupGroupContract;

class MarkupGroup extends Model implements MarkupGroupContract
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_active',
        'schedule_type',
        'apply_to_all_sources',
        'sort_order',
        'is_applied',
        'jobs_version',
    ];

    protected function casts(): array
    {
        return [
            'is_active'            => 'boolean',
            'apply_to_all_sources' => 'boolean',
            'is_applied'           => 'boolean',
            'jobs_version'         => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MarkupGroupScheduleProxy::modelClass());
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(MarkupConditionProxy::modelClass());
    }

    public function inventorySources(): BelongsToMany
    {
        return $this->belongsToMany(
            InventorySourceProxy::modelClass(),
            'markup_group_inventory_sources',
            'markup_group_id',
            'inventory_source_id'
        );
    }

    public function appliedPrices(): HasMany
    {
        return $this->hasMany(MarkupAppliedPriceProxy::modelClass());
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MarkupLogProxy::modelClass());
    }
}
