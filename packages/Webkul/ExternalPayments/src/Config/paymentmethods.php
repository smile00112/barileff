<?php

use Webkul\ExternalPayments\Payment\ExternalPayments;

return [
    'external_payments' => [
        'code' => 'external_payments',
        'title' => 'External Payments',
        'description' => 'Pay via external payment system',
        'class' => ExternalPayments::class,
        'active' => true,
        'sort' => 6,
    ],
];
