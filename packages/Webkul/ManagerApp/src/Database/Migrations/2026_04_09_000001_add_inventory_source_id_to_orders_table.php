<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'inventory_source_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('inventory_source_id')->nullable()->after('id');
            $table->foreign('inventory_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['inventory_source_id']);
            $table->dropColumn('inventory_source_id');
        });
    }
};
