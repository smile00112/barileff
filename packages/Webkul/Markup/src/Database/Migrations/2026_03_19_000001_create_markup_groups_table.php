<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['markup', 'discount']);
            $table->boolean('is_active')->default(true);
            $table->enum('schedule_type', ['daily', 'weekly']);
            $table->boolean('apply_to_all_sources')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_applied')->default(false);
            $table->unsignedInteger('jobs_version')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_groups');
    }
};
