<?php

use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;

it('shows payment instructions and upload form on success page when order uses paymentconfirmation method', function () {
    // Arrange.
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING,
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
        'method' => 'paymentconfirmation',
    ]);

    OrderPaymentReceipt::create([
        'order_id' => $order->id,
        'payment_detail_id' => null,
        'instructions_snapshot' => 'Bank: Test Bank, Account: 40817810000000000001',
    ]);

    // Act and Assert.
    $this->withSession(['order_id' => $order->id])
        ->get(route('shop.checkout.onepage.success'))
        ->assertOk()
        ->assertSee('Bank: Test Bank, Account: 40817810000000000001');
});

it('does not show payment confirmation block on success page for other payment methods', function () {
    // Arrange.
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING,
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
        'method' => 'cashondelivery',
    ]);

    // Act and Assert.
    $this->withSession(['order_id' => $order->id])
        ->get(route('shop.checkout.onepage.success'))
        ->assertOk()
        ->assertDontSee('paymentconfirmation::shop.orders.payment-confirmation');
});
