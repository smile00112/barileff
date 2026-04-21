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

    'admin' => [
        'menu' => [
            'title' => 'External Payments',
        ],
        'inventory-source-configs' => [
            'title' => 'External Payments — Configuration by Inventory Source',
            'edit-title' => 'External Payments — :source',
            'save-btn' => 'Save',
            'saved' => 'Settings saved.',
            'no-sources' => 'No inventory sources found.',
            'sections' => [
                'general' => 'General Settings',
                'api' => 'API Settings',
            ],
            'columns' => [
                'source' => 'Inventory Source',
                'title' => 'Payment Title',
                'status' => 'Status',
                'actions' => 'Actions',
            ],
            'actions' => [
                'edit' => 'Edit',
            ],
            'status' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'fields' => [
                'active' => 'Enable',
                'title' => 'Payment Method Title',
                'description' => 'Description',
                'api-server-url' => 'API Server URL',
                'api-token' => 'Authorization Token',
                'paid-order-status' => 'Paid Order Status',
            ],
            'paid-status' => [
                'processing' => 'Processing',
                'completed' => 'Completed',
            ],
            'validation' => [
                'title-required' => 'Title is required when the payment method is enabled.',
                'api-url-required' => 'API Server URL is required when the payment method is enabled.',
                'api-url-invalid' => 'API Server URL must be a valid URL.',
                'api-token-required' => 'Authorization Token is required when the payment method is enabled.',
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
