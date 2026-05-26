<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Customer\Models\Customer;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Shop\Mail\Customer\AccountCreatedNotification;

beforeEach(function () {
    Mail::fake();
});

it('creates a customer account and logs in when guest visits success with order in session', function () {
    // Arrange: guest order with billing address.
    $order = Order::factory()->create([
        'is_guest' => 1,
        'customer_id' => null,
        'customer_type' => null,
        'customer_email' => 'guest-external@example.com',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    // Act.
    $this->withSession(['external_payment_order_id' => $order->id])
        ->get(route('external-payments.success'))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    // Assert customer created.
    $this->assertDatabaseHas('customers', [
        'email' => 'guest-external@example.com',
        'is_verified' => 1,
    ]);

    // Assert order linked to customer.
    $customer = Customer::where('email', 'guest-external@example.com')->first();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'is_guest' => 0,
        'customer_id' => $customer->id,
    ]);

    // Assert email queued.
    Mail::assertQueued(AccountCreatedNotification::class, fn ($mail) => $mail->customer->email === 'guest-external@example.com');
});

it('does not create a customer when a customer with that email already exists', function () {
    // Arrange: existing customer.
    Customer::factory()->create(['email' => 'existing-external@example.com']);

    $order = Order::factory()->create([
        'is_guest' => 1,
        'customer_id' => null,
        'customer_type' => null,
        'customer_email' => 'existing-external@example.com',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    $countBefore = Customer::where('email', 'existing-external@example.com')->count();

    // Act.
    $this->withSession(['external_payment_order_id' => $order->id])
        ->get(route('external-payments.success'))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    // Assert no new customer created.
    expect(Customer::where('email', 'existing-external@example.com')->count())->toBe($countBefore);

    Mail::assertNothingQueued();
});

it('does not create a customer when authenticated user visits success', function () {
    // Arrange.
    $customer = Customer::factory()->create();

    $order = Order::factory()->create([
        'is_guest' => 0,
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
    ]);

    $countBefore = Customer::count();

    // Act.
    $this->actingAs($customer, 'customer')
        ->withSession(['external_payment_order_id' => $order->id])
        ->get(route('external-payments.success'))
        ->assertRedirect(route('shop.checkout.onepage.success'));

    // Assert no new customer created.
    expect(Customer::count())->toBe($countBefore);

    Mail::assertNothingQueued();
});
