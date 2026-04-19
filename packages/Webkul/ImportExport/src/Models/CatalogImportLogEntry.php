<?php

namespace Webkul\ImportExport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogImportLogEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'level',
        'entity_type',
        'action',
        'entity_id',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->created_at) {
                $model->created_at = now();
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CatalogImportSession::class, 'session_id');
    }
}
