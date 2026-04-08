<?php

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\User\Support\StoreManagerRolePermissions;

class RolesTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('admins')->delete();

        DB::table('roles')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $now = now();

        DB::table('roles')->insert([
            [
                'id' => 1,
                'name' => trans('installer::app.seeders.user.roles.name', [], $defaultLocale),
                'description' => trans('installer::app.seeders.user.roles.description', [], $defaultLocale),
                'permission_type' => 'all',
                'permissions' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => trans('installer::app.seeders.user.roles.store_manager.name', [], $defaultLocale),
                'description' => trans('installer::app.seeders.user.roles.store_manager.description', [], $defaultLocale),
                'permission_type' => 'custom',
                'permissions' => json_encode(StoreManagerRolePermissions::keys()),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
