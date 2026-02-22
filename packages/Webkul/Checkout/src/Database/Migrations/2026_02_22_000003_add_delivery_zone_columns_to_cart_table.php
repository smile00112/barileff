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
        Schema::table('cart', function (Blueprint $table) {
            $table->unsignedInteger('delivery_zone_id')->nullable()->after('shipping_method');
            $table->string('delivery_zone_mode')->nullable()->after('delivery_zone_id');
            $table->decimal('delivery_point_lat', 10, 7)->nullable()->after('delivery_zone_mode');
            $table->decimal('delivery_point_lng', 10, 7)->nullable()->after('delivery_point_lat');

            $table->foreign('delivery_zone_id')->references('id')->on('delivery_zones')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart', function (Blueprint $table) {
            $table->dropForeign(['delivery_zone_id']);
            $table->dropColumn([
                'delivery_zone_id',
                'delivery_zone_mode',
                'delivery_point_lat',
                'delivery_point_lng',
            ]);
        });
    }
};
