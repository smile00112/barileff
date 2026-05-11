<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Speeds up the common getAll() filter: WHERE parent_id IN (?) AND status = ?
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasIndex('categories', 'categories_parent_id_status_idx')) {
                $table->index(['parent_id', 'status'], 'categories_parent_id_status_idx');
            }
        });

        // The JOIN in getAll() is on category_id and the WHERE is on locale.
        // The existing unique index starts with (category_id, slug, locale) — MySQL can use
        // it for the category_id part but must scan to filter on locale.
        // A two-column index lets the planner do a single-step lookup for the JOIN+WHERE.
        Schema::table('category_translations', function (Blueprint $table) {
            if (! Schema::hasIndex('category_translations', 'ct_category_locale_idx')) {
                $table->index(['category_id', 'locale'], 'ct_category_locale_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasIndex('categories', 'categories_parent_id_status_idx')) {
                $table->dropIndex('categories_parent_id_status_idx');
            }
        });

        Schema::table('category_translations', function (Blueprint $table) {
            if (Schema::hasIndex('category_translations', 'ct_category_locale_idx')) {
                $table->dropIndex('ct_category_locale_idx');
            }
        });
    }
};
