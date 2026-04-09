<?php

namespace Webkul\ManagerApp\Tests;

use Tests\TestCase;
use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

class ManagerAppTestCase extends TestCase
{
    /**
     * Create an admin with manager access and at least one inventory source assigned.
     */
    protected function createManager(?InventorySource $source = null): Admin
    {
        $admin = Admin::factory()->create([
            'role_id' => 1,
            'status'  => 1,
        ]);

        $source ??= InventorySource::factory()->create();

        $admin->inventorySources()->attach($source->id);

        return $admin;
    }

    /**
     * Authenticate as a manager via Sanctum and return the token.
     */
    protected function loginAsManager(?Admin $admin = null): array
    {
        $admin ??= $this->createManager();

        $token = $admin->createToken('manager-app')->plainTextToken;

        return [$admin, $token];
    }
}
