<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_log_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('level', 16)->default('info');
            $table->string('entity_type', 32);
            $table->string('action', 32);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['session_id', 'id']);

            $table->foreign('session_id')
                ->references('id')
                ->on('catalog_import_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_log_entries');
    }
};
