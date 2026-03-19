<?php

namespace Webkul\ProductTag\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductTag\Contracts\Tag as TagContract;

class Tag extends Model implements TagContract
{
    protected $fillable = [
        'name',
        'locale',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductProxy::modelClass(), 'product_tag', 'tag_id', 'product_id');
    }
}
