<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropUnique(['city_id', 'code']);
            $table->unsignedInteger('city_id')->nullable()->change();
            $table->unique('code');
            $table->foreign('city_id')->references('id')->on('delivery_cities')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropUnique(['code']);
            $table->unsignedInteger('city_id')->nullable(false)->change();
            $table->unique(['city_id', 'code']);
            $table->foreign('city_id')->references('id')->on('delivery_cities')->onDelete('cascade');
        });
    }
};
