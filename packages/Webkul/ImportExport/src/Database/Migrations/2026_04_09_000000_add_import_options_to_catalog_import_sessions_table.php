<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->boolean('create_categories')->default(false)->after('inventory_source_id');
            $table->unsignedBigInteger('parent_category_id')->nullable()->default(1)->after('create_categories');
            $table->boolean('allow_insert')->default(true)->after('parent_category_id');
            $table->boolean('allow_update')->default(true)->after('allow_insert');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->dropColumn(['create_categories', 'parent_category_id', 'allow_insert', 'allow_update']);
        });
    }
};
