<?php

return [
    'configuration' => [
        'index' => [
            'sales' => [
                'payment-methods' => [
                    'external-payments' => 'Внешние платежи',
                    'external-payments-info' => 'Оплата через внешнюю платёжную систему',
                    'api-server-url' => 'URL сервера API',
                    'api-token' => 'Токен авторизации',
                    'paid-order-status' => 'Статус заказа после оплаты',
                    'status-processing' => 'В обработке',
                    'status-completed' => 'Завершён',
                ],
            ],
        ],
    ],

    'payment' => [
        'cart-not-found' => 'Корзина не найдена. Пожалуйста, попробуйте ещё раз.',
        'misconfigured' => 'Метод оплаты не настроен. Обратитесь к администратору магазина.',
        'create-failed' => 'Не удалось создать платёж. Пожалуйста, попробуйте ещё раз.',
        'status-failed' => 'Не удалось проверить статус платежа.',
        'cancelled' => 'Оплата была отменена.',
        'order-label' => 'Заказ',
        'redirecting' => 'Переход к платёжному шлюзу',
    ],
];
