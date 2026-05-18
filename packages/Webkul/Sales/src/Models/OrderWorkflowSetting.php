<?php

namespace Webkul\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Sales\Contracts\OrderWorkflowSetting as OrderWorkflowSettingContract;

class OrderWorkflowSetting extends Model implements OrderWorkflowSettingContract
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
