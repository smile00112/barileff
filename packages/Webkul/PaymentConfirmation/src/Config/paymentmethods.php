<?php

return [
    'paymentconfirmation' => [
        'code'        => 'paymentconfirmation',
        'title'       => 'Payment with Confirmation',
        'description' => 'Transfer payment and upload your receipt for confirmation.',
        'class'       => \Webkul\PaymentConfirmation\Payment\PaymentConfirmation::class,
        'active'      => true,
        'sort'        => 5,
    ],
];
