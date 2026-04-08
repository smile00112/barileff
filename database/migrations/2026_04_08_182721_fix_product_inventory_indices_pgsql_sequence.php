<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reset the PostgreSQL sequence for product_inventory_indices to avoid
     * primary key conflicts caused by seeder inserting explicit IDs.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            SELECT setval(
                pg_get_serial_sequence('product_inventory_indices', 'id'),
                COALESCE((SELECT MAX(id) FROM product_inventory_indices), 0)
            )
        ");
    }

    public function down(): void
    {
        //
    }
};
