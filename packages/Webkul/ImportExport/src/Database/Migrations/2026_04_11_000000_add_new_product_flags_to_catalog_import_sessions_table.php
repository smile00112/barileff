<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->boolean('new_products_active')->default(true)->after('allow_update');
            $table->boolean('new_products_in_stock')->default(true)->after('new_products_active');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->dropColumn(['new_products_active', 'new_products_in_stock']);
        });
    }
};
