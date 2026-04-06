<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Custom product attribute rows were moved to {@see \Database\Seeders\CustomProductAttributesSeeder}
 * so they run after Bagisto core seeders (attribute family id 1 exists).
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
