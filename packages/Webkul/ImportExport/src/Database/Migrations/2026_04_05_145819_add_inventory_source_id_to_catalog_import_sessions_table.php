<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_source_id')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_import_sessions', function (Blueprint $table) {
            $table->dropColumn('inventory_source_id');
        });
    }
};
