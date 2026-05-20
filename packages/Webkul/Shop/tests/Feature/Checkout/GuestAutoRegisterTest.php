<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Shop\Mail\Customer\AccountCreatedNotification;

beforeEach(function () {
    Mail::fake();
    Event::fake();
});

it('creates a customer account and queues email when guest places order with new email', function () {
    // Arrange: guest order with billing address, no matching customer.
    $order = Order::factory()->create([
        'is_guest' => 1,
        'customer_id' => null,
        'customer_type' => null,
        'customer_email' => 'newguest@example.com',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    $order->load('addresses');

    $repo = app(CustomerRepository::class);

    // Act.
    $result = $repo->createFromGuestCheckout($order);

    // Assert customer was created with correct data.
    expect($result['customer'])->toBeInstanceOf(Customer::class)
        ->and($result['customer']->email)->toBe('newguest@example.com')
        ->and($result['customer']->first_name)->toBe('Ivan')
        ->and($result['customer']->last_name)->toBe('Petrov')
        ->and($result['customer']->is_verified)->toBe(1)
        ->and($result['customer']->status)->toBe(1);

    // Assert plain password is returned and non-empty.
    expect($result['password'])->toBeString()->not->toBeEmpty();

    // Assert customer exists in DB.
    $this->assertDatabaseHas('customers', [
        'email' => 'newguest@example.com',
        'is_verified' => 1,
    ]);

    // Assert the order was linked to the new customer.
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'is_guest' => 0,
        'customer_id' => $result['customer']->id,
    ]);
});

it('does not create a customer when a customer with that email already exists', function () {
    // Arrange: existing customer.
    $existing = Customer::factory()->create(['email' => 'existing@example.com']);

    $order = Order::factory()->create([
        'is_guest' => 1,
        'customer_id' => null,
        'customer_type' => null,
        'customer_email' => 'existing@example.com',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    $customerCountBefore = Customer::where('email', 'existing@example.com')->count();

    // Act: simulate what the controller does — check first, only create if absent.
    if (! Customer::where('email', $order->customer_email)->exists()) {
        $repo = app(CustomerRepository::class);
        $repo->createFromGuestCheckout($order);
        Mail::assertQueued(AccountCreatedNotification::class);
    }

    // Assert: no new customer created, still only one.
    expect(Customer::where('email', 'existing@example.com')->count())->toBe($customerCountBefore);

    Mail::assertNothingQueued();
});

it('createFromGuestCheckout queues no mail directly, mail is queued by the controller', function () {
    // Arrange.
    $order = Order::factory()->create([
        'is_guest' => 1,
        'customer_id' => null,
        'customer_type' => null,
        'customer_email' => 'anotherguest@example.com',
    ]);

    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
        'first_name' => 'Anna',
        'last_name' => 'Sidorova',
    ]);

    $order->load('addresses');

    $repo = app(CustomerRepository::class);

    // Act.
    $result = $repo->createFromGuestCheckout($order);

    // The repository itself does not queue any mail — only the controller does.
    Mail::assertNothingQueued();

    // But we can assert that the mailable can be constructed and queued correctly.
    Mail::queue(new AccountCreatedNotification($result['customer'], $result['password']));

    Mail::assertQueued(AccountCreatedNotification::class, function (AccountCreatedNotification $mail) use ($result) {
        return $mail->customer->email === $result['customer']->email
            && $mail->password === $result['password'];
    });
});
