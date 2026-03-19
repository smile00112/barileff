<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_condition_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_condition_id')->constrained('markup_conditions')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_condition_products');
    }
};
