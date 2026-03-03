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
        Schema::table('cart', function (Blueprint $table) {
            $table->unsignedInteger('inventory_source_id')->nullable()->after('delivery_point_lng');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart', function (Blueprint $table) {
            $table->dropForeign(['inventory_source_id']);
            $table->dropColumn('inventory_source_id');
        });
    }
};
