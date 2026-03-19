<?php

namespace Webkul\Markup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Markup\Contracts\MarkupAppliedPrice as MarkupAppliedPriceContract;
use Webkul\Product\Models\ProductProxy;

class MarkupAppliedPrice extends Model implements MarkupAppliedPriceContract
{
    protected $fillable = [
        'markup_group_id',
        'product_id',
        'original_price',
        'original_old_price',
        'original_special_price',
        'applied_price',
        'applied_old_price',
        'applied_special_price',
    ];

    protected function casts(): array
    {
        return [
            'original_price'         => 'decimal:4',
            'original_old_price'     => 'decimal:4',
            'original_special_price' => 'decimal:4',
            'applied_price'          => 'decimal:4',
            'applied_old_price'      => 'decimal:4',
            'applied_special_price'  => 'decimal:4',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarkupGroupProxy::modelClass(), 'markup_group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }
}
