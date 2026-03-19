<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_inventory_sources', function (Blueprint $table) {
            $table->unsignedInteger('admin_id');
            $table->unsignedInteger('inventory_source_id');

            $table->unique(['admin_id', 'inventory_source_id'], 'admin_inv_src_unique');

            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_inventory_sources');
    }
};
