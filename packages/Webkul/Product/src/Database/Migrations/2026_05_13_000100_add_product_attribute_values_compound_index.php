<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            // Speeds up the 3 LEFT JOINs in searchFromDatabase (status, visible_individually, url_key)
            $table->index(['product_id', 'attribute_id'], 'pav_product_attribute_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropIndex('pav_product_attribute_idx');
        });
    }
};
