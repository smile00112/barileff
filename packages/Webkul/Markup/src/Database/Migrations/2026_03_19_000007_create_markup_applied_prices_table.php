<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_applied_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_group_id')->constrained('markup_groups')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->decimal('original_price', 12, 4)->nullable();
            $table->decimal('original_old_price', 12, 4)->nullable();
            $table->decimal('original_special_price', 12, 4)->nullable();
            $table->decimal('applied_price', 12, 4)->nullable();
            $table->decimal('applied_old_price', 12, 4)->nullable();
            $table->decimal('applied_special_price', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['markup_group_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_applied_prices');
    }
};
