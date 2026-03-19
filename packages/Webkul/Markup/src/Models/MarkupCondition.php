<?php

namespace Webkul\Markup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Markup\Contracts\MarkupCondition as MarkupConditionContract;
use Webkul\Product\Models\ProductProxy;

class MarkupCondition extends Model implements MarkupConditionContract
{
    protected $fillable = [
        'markup_group_id',
        'cost_from',
        'cost_to',
        'adjustment_type',
        'adjustment_value',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'cost_from'        => 'decimal:4',
            'cost_to'          => 'decimal:4',
            'adjustment_value' => 'decimal:4',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarkupGroupProxy::modelClass(), 'markup_group_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            CategoryProxy::modelClass(),
            'markup_condition_categories',
            'markup_condition_id',
            'category_id'
        );
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductProxy::modelClass(),
            'markup_condition_products',
            'markup_condition_id',
            'product_id'
        );
    }
}
