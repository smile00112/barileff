<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reset the product_images primary key sequence to max(id) so that
     * new inserts in PostgreSQL do not collide with existing rows.
     * This migration is a no-op for non-PostgreSQL drivers.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('product_images', 'id'),
                COALESCE((SELECT MAX(id) FROM product_images), 0) + 1,
                false
            )
        ");
    }

    public function down(): void {}
};
