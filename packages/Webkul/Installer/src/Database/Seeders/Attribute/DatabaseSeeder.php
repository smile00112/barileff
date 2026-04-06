<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Attributes are seeded before groups so attribute_group_mappings FKs resolve (PostgreSQL).
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        $this->call(AttributeFamilyTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeGroupTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeOptionTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
