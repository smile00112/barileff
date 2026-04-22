<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The existing unique index on product_inventories starts with (product_id, inventory_source_id, vendor_id).
        // When MySQL chooses a hash-join plan for the inventory EXISTS filter
        // (WHERE inventory_source_id = ? AND qty > 0), it cannot use that index for a source-first lookup
        // and falls back to a full table scan — causing 5-6 s per query on large catalogs.
        //
        // A (inventory_source_id, product_id) index lets the planner scan only the rows for a specific warehouse.
        Schema::table('product_inventories', function (Blueprint $table) {
            if (! Schema::hasIndex('product_inventories', 'pi_source_product_in_stock_idx')) {
                $table->index(['inventory_source_id', 'product_id'], 'pi_source_product_in_stock_idx');
            }
        });

        // PostgreSQL: FK constraints do NOT auto-create indexes (unlike MySQL InnoDB), so the
        // variant-inventory EXISTS subquery would do a sequential scan on products(parent_id).
        // MySQL InnoDB already creates this index implicitly — adding it there would be a duplicate.
        //
        // if (DB::getDriverName() === 'pgsql') {
        //     Schema::table('products', function (Blueprint $table) {
        //         if (! Schema::hasIndex('products', 'products_parent_id_idx')) {
        //             $table->index('parent_id', 'products_parent_id_idx');
        //         }
        //     });
        // }
    }

    public function down(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            if (Schema::hasIndex('product_inventories', 'pi_source_product_in_stock_idx')) {
                $table->dropIndex('pi_source_product_in_stock_idx');
            }
        });

        // if (DB::getDriverName() === 'pgsql') {
        //     Schema::table('products', function (Blueprint $table) {
        //         if (Schema::hasIndex('products', 'products_parent_id_idx')) {
        //             $table->dropIndex('products_parent_id_idx');
        //         }
        //     });
        // }
    }
};
