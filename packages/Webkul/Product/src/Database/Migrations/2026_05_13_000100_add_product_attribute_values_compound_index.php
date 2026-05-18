<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            // (product_id, attribute_id) already exists as pav_product_attribute_idx.
            // Add an (attribute_id, boolean_value, product_id) covering index for the
            // status/visible_individually WHERE conditions in searchFromDatabase.
            if (! Schema::hasIndex('product_attribute_values', 'pav_attr_bool_product_idx')) {
                $table->index(['attribute_id', 'boolean_value', 'product_id'], 'pav_attr_bool_product_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            if (Schema::hasIndex('product_attribute_values', 'pav_attr_bool_product_idx')) {
                $table->dropIndex('pav_attr_bool_product_idx');
            }
        });
    }
};
