<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmation_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('instructions');
            $table->unsignedInteger('inventory_source_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('inventory_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmation_details');
    }
};
