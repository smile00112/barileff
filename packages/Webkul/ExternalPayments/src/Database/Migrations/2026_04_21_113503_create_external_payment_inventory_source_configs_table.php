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
        Schema::create('external_payment_inventory_source_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('inventory_source_id');
            $table->unique('inventory_source_id', 'ep_inv_src_cfg_unique');
            $table->boolean('active')->default(false);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('api_server_url')->nullable();
            $table->string('api_token')->nullable();
            $table->string('paid_order_status')->default('processing');
            $table->timestamps();

            $table->foreign('inventory_source_id', 'ep_inv_src_cfg_inv_src_fk')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_payment_inventory_source_configs');
    }
};
