<?php

namespace Webkul\PushNotification\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationSetting extends Model
{
    protected $fillable = [
        'event',
        'target',
        'title',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
