<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_group_inventory_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_group_id')->constrained('markup_groups')->cascadeOnDelete();
            $table->unsignedInteger('inventory_source_id');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_group_inventory_sources');
    }
};
