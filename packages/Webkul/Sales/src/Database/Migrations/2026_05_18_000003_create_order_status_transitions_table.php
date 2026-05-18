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
        Schema::create('order_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_status_code', 50);
            $table->string('to_status_code', 50);
            $table->string('delivery_type', 100)->nullable();
            $table->string('payment_type', 100)->nullable();
            $table->string('channel', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100);
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index(
                ['from_status_code', 'delivery_type', 'payment_type', 'channel', 'is_active'],
                'ost_lookup_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_transitions');
    }
};
