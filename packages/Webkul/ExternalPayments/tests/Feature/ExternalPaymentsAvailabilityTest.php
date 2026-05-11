<?php

use Illuminate\Support\Facades\Session;
use Webkul\Core\Models\CoreConfig;
use Webkul\ExternalPayments\Models\InventorySourceConfig;
use Webkul\ExternalPayments\Payment\ExternalPayments;
use Webkul\Inventory\Models\InventorySource;

afterEach(function (): void {
    Session::flush();
});

it('is available when warehouse is configured and global active is not saved yet', function () {
    $source = InventorySource::factory()->create();
    InventorySourceConfig::factory()->create([
        'inventory_source_id' => $source->id,
        'active' => true,
        'api_server_url' => 'https://payment.example.com/api',
        'api_token' => 'secret',
    ]);

    Session::put('selected_inventory_source_id', $source->id);

    /** @var ExternalPayments $payment */
    $payment = app(ExternalPayments::class);

    expect($payment->isAvailable())->toBeTrue();
});

it('is not available when global active is disabled in core config', function () {
    $source = InventorySource::factory()->create();
    InventorySourceConfig::factory()->create([
        'inventory_source_id' => $source->id,
        'active' => true,
        'api_server_url' => 'https://payment.example.com/api',
        'api_token' => 'secret',
    ]);

    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.external_payments.active',
        'value' => '0',
        'channel_code' => core()->getCurrentChannelCode(),
    ]);

    Session::put('selected_inventory_source_id', $source->id);

    /** @var ExternalPayments $payment */
    $payment = app(ExternalPayments::class);

    expect($payment->isAvailable())->toBeFalse();
});

it('is available when global active is enabled and warehouse is configured', function () {
    $source = InventorySource::factory()->create();
    InventorySourceConfig::factory()->create([
        'inventory_source_id' => $source->id,
        'active' => true,
        'api_server_url' => 'https://payment.example.com/api',
        'api_token' => 'secret',
    ]);

    CoreConfig::factory()->create([
        'code' => 'sales.payment_methods.external_payments.active',
        'value' => '1',
        'channel_code' => core()->getCurrentChannelCode(),
    ]);

    Session::put('selected_inventory_source_id', $source->id);

    /** @var ExternalPayments $payment */
    $payment = app(ExternalPayments::class);

    expect($payment->isAvailable())->toBeTrue();
});

it('exposes non-empty title and description via config or translations', function () {
    /** @var ExternalPayments $payment */
    $payment = app(ExternalPayments::class);

    expect(strlen($payment->getTitle()))->toBeGreaterThan(0)
        ->and(strlen($payment->getDescription()))->toBeGreaterThan(0);
});
