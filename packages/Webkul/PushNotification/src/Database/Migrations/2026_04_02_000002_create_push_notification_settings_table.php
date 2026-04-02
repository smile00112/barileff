<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->enum('target', ['admin', 'customer', 'both'])->default('both');
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event', 'target']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_settings');
    }
};
