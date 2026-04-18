<?php

return [
    [
        'key' => 'sales.payment_methods.external_payments',
        'name' => 'external-payments::app.configuration.index.sales.payment-methods.external-payments',
        'info' => 'external-payments::app.configuration.index.sales.payment-methods.external-payments-info',
        'sort' => 6,
        'fields' => [
            [
                'name' => 'title',
                'title' => 'admin::app.configuration.index.sales.payment-methods.title',
                'type' => 'text',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => true,
            ], [
                'name' => 'description',
                'title' => 'admin::app.configuration.index.sales.payment-methods.description',
                'type' => 'textarea',
                'channel_based' => true,
                'locale_based' => true,
            ], [
                'name' => 'api_server_url',
                'title' => 'external-payments::app.configuration.index.sales.payment-methods.api-server-url',
                'type' => 'text',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1|url',
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'api_token',
                'title' => 'external-payments::app.configuration.index.sales.payment-methods.api-token',
                'type' => 'password',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'paid_order_status',
                'title' => 'external-payments::app.configuration.index.sales.payment-methods.paid-order-status',
                'type' => 'select',
                'options' => [
                    [
                        'title' => 'external-payments::app.configuration.index.sales.payment-methods.status-processing',
                        'value' => 'processing',
                    ], [
                        'title' => 'external-payments::app.configuration.index.sales.payment-methods.status-completed',
                        'value' => 'completed',
                    ],
                ],
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'active',
                'title' => 'admin::app.configuration.index.sales.payment-methods.status',
                'type' => 'boolean',
                'channel_based' => true,
                'locale_based' => false,
            ], [
                'name' => 'sort',
                'title' => 'admin::app.configuration.index.sales.payment-methods.sort-order',
                'type' => 'select',
                'options' => [
                    ['title' => '1', 'value' => 1],
                    ['title' => '2', 'value' => 2],
                    ['title' => '3', 'value' => 3],
                    ['title' => '4', 'value' => 4],
                    ['title' => '5', 'value' => 5],
                    ['title' => '6', 'value' => 6],
                ],
            ],
        ],
    ],
];
