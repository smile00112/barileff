<?php

return [
    'flatrate' => [
        'code' => 'flatrate',
        'title' => 'Flat Rate',
        'description' => 'Flat Rate Shipping',
        'active' => true,
        'default_rate' => '10',
        'type' => 'per_unit',
        'class' => 'Webkul\Shipping\Carriers\FlatRate',
    ],

    'free' => [
        'code' => 'free',
        'title' => 'Free Shipping',
        'description' => 'Free Shipping',
        'active' => true,
        'default_rate' => '0',
        'class' => 'Webkul\Shipping\Carriers\Free',
    ],

    'delivery_zones' => [
        'code' => 'delivery_zones',
        'title' => 'Delivery By Zone',
        'description' => 'Delivery by city zones',
        'active' => true,
        'class' => 'Webkul\Shipping\Carriers\DeliveryZones',
    ],
];
