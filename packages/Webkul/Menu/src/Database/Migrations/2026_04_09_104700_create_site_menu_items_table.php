<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('menu_id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->string('title');
            $table->string('type')->default('custom_url');
            $table->unsignedInteger('cms_page_id')->nullable();
            $table->string('url')->nullable();
            $table->string('target')->default('_self');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('site_menus')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('site_menu_items')->onDelete('cascade');
            $table->foreign('cms_page_id')->references('id')->on('cms_pages')->onDelete('set null');

            $table->index(['menu_id', 'parent_id', 'sort_order'], 'site_menu_items_parent_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_menu_items');
    }
};
