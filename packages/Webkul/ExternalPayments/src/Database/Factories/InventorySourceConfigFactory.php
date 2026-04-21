<?php

namespace Webkul\ExternalPayments\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\ExternalPayments\Models\InventorySourceConfig;
use Webkul\Inventory\Models\InventorySource;

class InventorySourceConfigFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InventorySourceConfig::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'inventory_source_id' => InventorySource::factory(),
            'active' => true,
            'title' => 'External Payments',
            'description' => null,
            'api_server_url' => 'https://payment.example.com',
            'api_token' => 'test-secret-token',
            'paid_order_status' => 'processing',
        ];
    }

    /**
     * Set the config as inactive.
     */
    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
