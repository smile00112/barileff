<?php

return [
    'configuration' => [
        'index' => [
            'sales' => [
                'payment-methods' => [
                    'external-payments' => 'External Payments',
                    'external-payments-info' => 'Pay via an external payment system',
                    'api-server-url' => 'API Server URL',
                    'api-token' => 'Authorization Token',
                    'paid-order-status' => 'Paid Order Status',
                    'status-processing' => 'Processing',
                    'status-completed' => 'Completed',
                ],
            ],
        ],
    ],

    'payment' => [
        'cart-not-found' => 'Your cart was not found. Please try again.',
        'misconfigured' => 'Payment method is not configured. Please contact the store administrator.',
        'create-failed' => 'Failed to create payment. Please try again.',
        'status-failed' => 'Failed to check payment status.',
        'cancelled' => 'Payment was cancelled.',
        'order-label' => 'Order',
        'redirecting' => 'Redirecting to payment gateway',
    ],
];
