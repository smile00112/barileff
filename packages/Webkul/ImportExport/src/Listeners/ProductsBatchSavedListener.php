<?php

namespace Webkul\ImportExport\Listeners;

use Webkul\ImportExport\Models\CatalogImportLogEntry;
use Webkul\ImportExport\Models\CatalogImportSession;

class ProductsBatchSavedListener
{
    public function handle(array $payload): void
    {
        $session = CatalogImportSession::where('import_ref_id', $payload['import_id'])->first();

        if (! $session) {
            return;
        }

        $rows = [];

        foreach ($payload['created_ids'] as $id) {
            $rows[] = [
                'session_id' => $session->id,
                'level' => 'info',
                'entity_type' => 'product',
                'action' => 'created',
                'entity_id' => $id,
                'message' => null,
            ];
        }

        foreach ($payload['updated_ids'] as $id) {
            $rows[] = [
                'session_id' => $session->id,
                'level' => 'info',
                'entity_type' => 'product',
                'action' => 'updated',
                'entity_id' => $id,
                'message' => null,
            ];
        }

        if (empty($rows)) {
            return;
        }

        // Chunk to avoid hitting DB parameter limits on large batches.
        foreach (array_chunk($rows, 500) as $chunk) {
            CatalogImportLogEntry::insert($chunk);
        }
    }
}
