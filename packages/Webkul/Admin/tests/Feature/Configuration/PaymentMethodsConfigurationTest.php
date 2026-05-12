<?php

use Webkul\ExternalPayments\Payment\ExternalPayments;
use Webkul\PaymentConfirmation\Payment\PaymentConfirmation;

use function Pest\Laravel\get;

it('exposes payment confirmation title and active fields in system configuration', function () {
    $activeField = system_config()->getConfigField('sales.payment_methods.paymentconfirmation.active');

    expect($activeField)->toBeArray()
        ->and($activeField['channel_based'] ?? null)->toBeTrue()
        ->and($activeField['locale_based'] ?? null)->toBeFalse();

    $titleField = system_config()->getConfigField('sales.payment_methods.paymentconfirmation.title');

    expect($titleField)->toBeArray()
        ->and($titleField['channel_based'] ?? null)->toBeTrue()
        ->and($titleField['locale_based'] ?? null)->toBeTrue();
});

it('exposes logo image field for payment confirmation and external payments', function () {
    $paymentConfirmationImage = system_config()->getConfigField('sales.payment_methods.paymentconfirmation.image');

    expect($paymentConfirmationImage)->toBeArray()
        ->and($paymentConfirmationImage['type'] ?? null)->toBe('image')
        ->and($paymentConfirmationImage['channel_based'] ?? null)->toBeTrue();

    $externalPaymentsImage = system_config()->getConfigField('sales.payment_methods.external_payments.image');

    expect($externalPaymentsImage)->toBeArray()
        ->and($externalPaymentsImage['type'] ?? null)->toBe('image')
        ->and($externalPaymentsImage['channel_based'] ?? null)->toBeTrue();
});

it('shows payment confirmation on the admin payment methods configuration page', function () {
    $this->loginAsAdmin();

    get(route('admin.configuration.index', [
        'slug' => 'sales',
        'slug2' => 'payment_methods',
    ]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.index.sales.payment-methods.payment-with-confirmation'));
});

it('evaluates payment confirmation availability from active config', function () {
    $payment = app(PaymentConfirmation::class);

    expect($payment->isAvailable())->toBeTrue();
});

it('returns the placeholder logo when payment confirmation has no uploaded image', function () {
    $payment = app(PaymentConfirmation::class);

    expect($payment->getImage())->toContain('payment-method-placeholder.svg');
});

it('returns the placeholder logo when external payments has no uploaded image', function () {
    $payment = app(ExternalPayments::class);

    expect($payment->getImage())->toContain('payment-method-placeholder.svg');
});
