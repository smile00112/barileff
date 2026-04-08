<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\User\Support\StoreManagerRolePermissions;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        if (DB::table('roles')->where('id', 2)->exists()) {
            return;
        }

        $now = now();

        DB::table('roles')->insert([
            'id' => 2,
            'name' => 'Store Manager',
            'description' => 'Operational access without user/role administration or system configuration',
            'permission_type' => 'custom',
            'permissions' => json_encode(StoreManagerRolePermissions::keys()),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')->where('id', 2)->delete();
    }
};
