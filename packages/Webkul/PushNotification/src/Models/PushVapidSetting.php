<?php

namespace Webkul\PushNotification\Models;

use Illuminate\Database\Eloquent\Model;

class PushVapidSetting extends Model
{
    protected $fillable = [
        'public_key',
        'private_key',
        'subject',
    ];

    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
        ];
    }
}
