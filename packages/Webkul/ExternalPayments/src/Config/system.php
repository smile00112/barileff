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
            ],
            [
                'name' => 'description',
                'title' => 'admin::app.configuration.index.sales.payment-methods.description',
                'type' => 'textarea',
                'channel_based' => true,
                'locale_based' => true,
            ],
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
