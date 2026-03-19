<?php

namespace Webkul\Markup\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Markup\Contracts\MarkupGroupSchedule as MarkupGroupScheduleContract;

class MarkupGroupSchedule extends Model implements MarkupGroupScheduleContract
{
    protected $fillable = [
        'markup_group_id',
        'day_of_week',
        'time_from',
        'time_to',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MarkupGroupProxy::modelClass(), 'markup_group_id');
    }
}
