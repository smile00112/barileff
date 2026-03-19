<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_flat', function (Blueprint $table) {
            $table->decimal('old_price', 12, 4)->nullable()->after('special_price_to');
            $table->string('barcode')->nullable()->after('product_number');
            $table->string('badge')->nullable()->after('featured');
            $table->datetime('published_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('product_flat', function (Blueprint $table) {
            $table->dropColumn(['old_price', 'barcode', 'badge', 'published_at']);
        });
    }
};
