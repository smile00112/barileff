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
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->decimal('polygon_fill_opacity', 3, 2)->default(0.20)->after('polygon_color');
            $table->decimal('polygon_stroke_opacity', 3, 2)->default(1.00)->after('polygon_fill_opacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropColumn([
                'polygon_fill_opacity',
                'polygon_stroke_opacity',
            ]);
        });
    }
};
