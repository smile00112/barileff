<?php

namespace Webkul\ImportExport\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogImportSession extends Model
{
    public const STATE_PENDING = 'pending';

    public const STATE_READY = 'ready';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    protected $fillable = [
        'state',
        'file_name',
        'file_path',
        'delimiter',
        'locale',
        'inventory_source_id',
        'create_categories',
        'parent_category_id',
        'allow_insert',
        'allow_update',
        'headers',
        'column_mapping',
        'import_ref_id',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'column_mapping' => 'array',
            'create_categories' => 'boolean',
            'parent_category_id' => 'integer',
            'allow_insert' => 'boolean',
            'allow_update' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
