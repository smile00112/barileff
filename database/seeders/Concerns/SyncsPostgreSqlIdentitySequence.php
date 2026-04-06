<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait SyncsPostgreSqlIdentitySequence
{
    /**
     * Align the PostgreSQL sequence for a table's serial `id` with MAX(id) after explicit-key inserts.
     *
     * @param  non-empty-string  $table
     */
    protected function syncPostgreSqlIdentitySequenceIfNeeded(string $table): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Invalid PostgreSQL table name.');
        }

        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('{$table}', 'id')::regclass,
                COALESCE((SELECT MAX(id) FROM {$table}), 1),
                true
            )
        ");
    }
}
