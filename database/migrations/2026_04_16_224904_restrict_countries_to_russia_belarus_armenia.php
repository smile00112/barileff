<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove all countries except Armenia, Belarus, and Russia.
     * Related country_states and country_translations rows are removed via ON DELETE CASCADE.
     */
    public function up(): void
    {
        DB::table('countries')->whereNotIn('code', ['AM', 'BY', 'RU'])->delete();
    }

    /**
     * Irreversible: deleted country rows are not restored.
     */
    public function down(): void
    {
        //
    }
};
