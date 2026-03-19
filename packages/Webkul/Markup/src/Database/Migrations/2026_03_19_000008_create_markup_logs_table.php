<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_group_id')->constrained('markup_groups')->cascadeOnDelete();
            $table->string('action');
            $table->unsignedInteger('products_affected')->default(0);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_logs');
    }
};
