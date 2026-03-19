<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->unsignedInteger('min_stock')->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            $table->dropColumn('min_stock');
        });
    }
};
