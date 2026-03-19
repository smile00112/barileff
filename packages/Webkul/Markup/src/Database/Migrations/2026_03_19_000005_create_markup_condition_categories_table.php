<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_condition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_condition_id')->constrained('markup_conditions')->cascadeOnDelete();
            $table->unsignedInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_condition_categories');
    }
};
