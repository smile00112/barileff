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
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('city_id');
            $table->string('code');
            $table->string('name');
            $table->json('polygon_json');
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->unsignedInteger('delivery_time_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_id', 'code']);
            $table->foreign('city_id')->references('id')->on('delivery_cities')->onDelete('cascade');
        });

        Schema::create('delivery_zone_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('zone_id');
            $table->decimal('min_order_total', 12, 4)->default(0);
            $table->decimal('price', 12, 4);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['zone_id', 'min_order_total']);
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->onDelete('cascade');
        });

        Schema::create('delivery_zone_inventory_sources', function (Blueprint $table) {
            $table->unsignedInteger('zone_id');
            $table->unsignedInteger('inventory_source_id');

            $table->primary(['zone_id', 'inventory_source_id']);
            $table->foreign('zone_id')->references('id')->on('delivery_zones')->onDelete('cascade');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zone_inventory_sources');
        Schema::dropIfExists('delivery_zone_rates');
        Schema::dropIfExists('delivery_zones');
    }
};
