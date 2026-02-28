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
        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->decimal('center_lat', 10, 7)->nullable()->after('state');
            $table->decimal('center_lng', 10, 7)->nullable()->after('center_lat');
            $table->json('polygon_json')->nullable()->after('center_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->dropColumn(['center_lat', 'center_lng', 'polygon_json']);
        });
    }
};
