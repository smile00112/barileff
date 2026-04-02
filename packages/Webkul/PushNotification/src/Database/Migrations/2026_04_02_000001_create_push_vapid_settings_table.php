<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_vapid_settings', function (Blueprint $table) {
            $table->id();
            $table->text('public_key');
            $table->text('private_key');
            $table->string('subject')->default('mailto:admin@example.com');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_vapid_settings');
    }
};
