<?php

namespace Webkul\PushNotification\Models;

use Illuminate\Database\Eloquent\Model;

class InPageNotification extends Model
{
    protected $fillable = [
        'customer_id',
        'title',
        'body',
        'url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
