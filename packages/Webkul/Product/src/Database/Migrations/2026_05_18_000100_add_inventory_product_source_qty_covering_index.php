<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The existing pi_source_product_in_stock_idx (inventory_source_id, product_id) does not
        // include qty, so the EXISTS subquery in searchFromDatabase must read the heap to check
        // qty > 0 for every matched row. A (product_id, inventory_source_id, qty) index lets MySQL
        // satisfy both correlated EXISTS checks entirely from the index (covering index scan).
        Schema::table('product_inventories', function (Blueprint $table) {
            if (! Schema::hasIndex('product_inventories', 'pi_product_source_qty_idx')) {
                $table->index(['product_id', 'inventory_source_id', 'qty'], 'pi_product_source_qty_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            if (Schema::hasIndex('product_inventories', 'pi_product_source_qty_idx')) {
                $table->dropIndex('pi_product_source_qty_idx');
            }
        });
    }
};
