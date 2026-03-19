<?php

namespace Webkul\Markup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Markup\Contracts\MarkupLog as MarkupLogContract;

class MarkupLog extends Model implements MarkupLogContract
{
    protected $fillable = [
        'markup_group_id',
        'action',
        'products_affected',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarkupGroupProxy::modelClass(), 'markup_group_id');
    }
}
