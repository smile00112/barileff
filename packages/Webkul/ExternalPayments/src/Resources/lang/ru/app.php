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

    'admin' => [
        'menu' => [
            'title' => 'Внешние платежи',
        ],
        'inventory-source-configs' => [
            'title' => 'Внешние платежи — настройки по источнику инвентаризации',
            'edit-title' => 'Внешние платежи — :source',
            'save-btn' => 'Сохранить',
            'saved' => 'Настройки сохранены.',
            'no-sources' => 'Источники инвентаризации не найдены.',
            'sections' => [
                'general' => 'Основные настройки',
                'api' => 'Настройки API',
            ],
            'columns' => [
                'source' => 'Источник инвентаризации',
                'title' => 'Название метода оплаты',
                'status' => 'Статус',
                'actions' => 'Действия',
            ],
            'status' => [
                'active' => 'Активен',
                'inactive' => 'Неактивен',
            ],
            'fields' => [
                'active' => 'Включить',
                'title' => 'Название метода оплаты',
                'description' => 'Описание',
                'api-server-url' => 'URL сервера API',
                'api-token' => 'Токен авторизации',
                'paid-order-status' => 'Статус заказа после оплаты',
            ],
            'paid-status' => [
                'processing' => 'В обработке',
                'completed' => 'Завершён',
            ],
            'validation' => [
                'title-required' => 'Название обязательно, когда метод оплаты включён.',
                'api-url-required' => 'URL сервера API обязателен, когда метод оплаты включён.',
                'api-url-invalid' => 'URL сервера API должен быть корректным URL.',
                'api-token-required' => 'Токен авторизации обязателен, когда метод оплаты включён.',
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
