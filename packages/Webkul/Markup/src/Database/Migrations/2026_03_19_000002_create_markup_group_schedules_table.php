<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markup_group_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('markup_group_id')->constrained('markup_groups')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->unsigned()->nullable();
            $table->time('time_from');
            $table->time('time_to');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markup_group_schedules');
    }
};
