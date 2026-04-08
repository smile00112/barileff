<?php

use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerAddress;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('stores customer address additional payload through the api', function () {
    $customer = $this->loginAsCustomer();

    $response = postJson(route('shop.api.customers.account.addresses.store'), [
        'customer_id' => $customer->id,
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
        'address' => ['Tverskaya 10'],
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'phone' => '+79990000000',
        'email' => 'ivan@example.com',
        'additional' => [
            'label' => 'Moscow, Tverskaya 10, apt 12',
            'latitude' => 55.7558,
            'longitude' => 37.6176,
            'zone_name' => 'Center',
            'is_private_house' => false,
            'apartment' => '12',
            'entrance' => '3',
            'floor' => '5',
            'intercom' => '120',
        ],
    ]);

    $response->assertOk();

    $addressId = CustomerAddress::query()
        ->where('customer_id', $customer->id)
        ->latest('id')
        ->value('id');

    expect(CustomerAddress::query()->findOrFail($addressId)->additional)
        ->toMatchArray([
            'label' => 'Moscow, Tverskaya 10, apt 12',
            'zone_name' => 'Center',
            'apartment' => '12',
        ]);
});

it('updates and deletes customer address through the api', function () {
    $customer = Customer::factory()->create();

    $address = CustomerAddress::factory()->create([
        'customer_id' => $customer->id,
        'address' => 'Lenina 1',
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'phone' => '+79990000000',
        'email' => 'old@example.com',
        'additional' => [
            'label' => 'Old label',
        ],
    ]);

    $this->loginAsCustomer($customer);

    putJson(route('shop.api.customers.account.addresses.update', $address->id), [
        'customer_id' => $customer->id,
        'first_name' => $address->first_name,
        'last_name' => $address->last_name,
        'address' => ['Lenina 15'],
        'country' => $address->country,
        'state' => $address->state,
        'city' => $address->city,
        'postcode' => $address->postcode,
        'phone' => $address->phone,
        'email' => 'new@example.com',
        'additional' => [
            'label' => 'New label',
            'zone_name' => 'North',
            'is_private_house' => true,
        ],
    ])
        ->assertOk();

    expect($address->fresh()->additional)
        ->toMatchArray([
            'label' => 'New label',
            'zone_name' => 'North',
            'is_private_house' => true,
        ]);

    deleteJson(route('shop.api.customers.account.addresses.delete', $address->id))
        ->assertOk();

    $this->assertDatabaseMissing('addresses', [
        'id' => $address->id,
        'customer_id' => $customer->id,
    ]);
});
