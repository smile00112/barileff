<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Checkout\Models\CartPayment;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Customer\Models\Customer;
use Webkul\ExternalPayments\Models\InventorySourceConfig;
use Webkul\Faker\Helpers\Product as ProductFaker;
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

    $this->assertAuthenticatedAs($customer, 'customer');

    // Assert email queued.
    Mail::assertQueued(AccountCreatedNotification::class, fn ($mail) => $mail->customer->email === 'guest-external@example.com');
});

it('creates and authenticates a customer when payment creation fails after order creation', function () {
    Http::fake([
        'https://payment.example.com/api/external-payments/create' => Http::response([
            'message' => 'Gateway unavailable',
        ], 500),
    ]);

    $sourceConfig = InventorySourceConfig::factory()->create([
        'active' => true,
        'api_server_url' => 'https://payment.example.com',
        'api_token' => 'secret',
    ]);

    Session::put('selected_inventory_source_id', $sourceConfig->inventory_source_id);

    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
            26 => 'guest_checkout',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],

            'guest_checkout' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $cart = Cart::factory()->create([
        'shipping_method' => 'free_free',
        'inventory_source_id' => $sourceConfig->inventory_source_id,
        'customer_email' => 'failed-external@example.com',
        'customer_first_name' => 'Ivan',
        'customer_last_name' => 'Petrov',
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating' => '0',
        'is_buy_now' => '0',
        'quantity' => '1',
    ];

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => $additional['quantity'],
        'name' => $product->name,
        'price' => $convertedPrice = core()->convertPrice($price = $product->price),
        'price_incl_tax' => $convertedPrice,
        'base_price' => $price,
        'base_price_incl_tax' => $price,
        'total' => $total = $convertedPrice * $additional['quantity'],
        'total_incl_tax' => $total,
        'base_total' => $price * $additional['quantity'],
        'weight' => $product->weight ?? 0,
        'total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type' => $product->type,
        'additional' => $additional,
    ]);

    CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'address_type' => CartAddress::ADDRESS_TYPE_BILLING,
        'email' => 'failed-external@example.com',
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    $shippingAddress = CartAddress::factory()->create([
        'cart_id' => $cart->id,
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
        'email' => 'failed-external@example.com',
        'first_name' => 'Ivan',
        'last_name' => 'Petrov',
    ]);

    CartPayment::factory()->create([
        'cart_id' => $cart->id,
        'method' => 'external_payments',
        'method_title' => 'External Payments',
    ]);

    CartShippingRate::factory()->create([
        'carrier' => 'free',
        'carrier_title' => 'Free shipping',
        'method' => 'free_free',
        'method_title' => 'Free Shipping',
        'method_description' => 'Free Shipping',
        'cart_address_id' => $shippingAddress->id,
        'cart_id' => $cart->id,
    ]);

    cart()->setCart($cart);
    cart()->collectTotals();

    $this->get(route('external-payments.redirect'))
        ->assertRedirect(route('shop.checkout.cart.index'));

    $customer = Customer::where('email', 'failed-external@example.com')->first();

    $this->assertAuthenticatedAs($customer, 'customer');
    $this->assertDatabaseHas('orders', [
        'customer_email' => 'failed-external@example.com',
        'is_guest' => 0,
        'customer_id' => $customer->id,
    ]);

    Mail::assertQueued(AccountCreatedNotification::class, fn ($mail) => $mail->customer->email === 'failed-external@example.com');
    Http::assertSent(fn ($request) => $request->url() === 'https://payment.example.com/api/external-payments/create');
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
