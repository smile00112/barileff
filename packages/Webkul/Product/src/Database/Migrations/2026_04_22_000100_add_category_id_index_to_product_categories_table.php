<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            if (! Schema::hasIndex('product_categories', 'product_categories_category_id_idx')) {
                $table->index('category_id', 'product_categories_category_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            if (Schema::hasIndex('product_categories', 'product_categories_category_id_idx')) {
                $table->dropIndex('product_categories_category_id_idx');
            }
        });
    }
};
