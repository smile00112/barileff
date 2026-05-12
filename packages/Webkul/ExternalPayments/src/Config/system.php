<?php

return [
    [
        'key' => 'sales.payment_methods.external_payments',
        'name' => 'external-payments::app.configuration.index.sales.payment-methods.external-payments',
        'info' => 'external-payments::app.configuration.index.sales.payment-methods.external-payments-info',
        'sort' => 6,
        'fields' => [
            [
                'name' => 'image',
                'title' => 'admin::app.configuration.index.sales.payment-methods.logo',
                'type' => 'image',
                'info' => 'admin::app.configuration.index.sales.payment-methods.logo-information',
                'channel_based' => true,
                'locale_based' => false,
                'validation' => 'mimes:bmp,jpeg,jpg,png,webp',
            ],
            [
                'name' => 'active',
                'title' => 'admin::app.configuration.index.sales.payment-methods.status',
                'type' => 'boolean',
                'channel_based' => true,
                'locale_based' => false,
            ],
            [
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
