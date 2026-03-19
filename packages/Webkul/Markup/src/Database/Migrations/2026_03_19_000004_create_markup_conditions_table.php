<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_group_id')->constrained('markup_groups')->cascadeOnDelete();
            $table->decimal('cost_from', 12, 4)->nullable();
            $table->decimal('cost_to', 12, 4)->nullable();
            $table->enum('adjustment_type', ['percent', 'fixed']);
            $table->decimal('adjustment_value', 12, 4);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_conditions');
    }
};
