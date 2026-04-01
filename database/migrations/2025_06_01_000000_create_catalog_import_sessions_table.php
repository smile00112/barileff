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
        Schema::create('catalog_import_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('state', 20)->default('pending');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('delimiter', 5)->default(',');
            $table->string('locale', 20)->default('en');
            $table->json('headers')->nullable();
            $table->json('column_mapping')->nullable();
            $table->unsignedBigInteger('import_ref_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_import_sessions');
    }
};
