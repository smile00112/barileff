<?php

return [
    'admin' => [
        'common' => [
            'create' => 'Создать',
            'save' => 'Сохранить',
            'back' => 'Назад',
            'edit' => 'Редактировать',
            'delete' => 'Удалить',
            'active' => 'Активно',
            'inactive' => 'Неактивно',
        ],

        'acl' => [
            'menu' => 'Меню сайта',
            'menus' => 'Меню',
            'items' => 'Пункты меню',
            'create' => 'Создание',
            'edit' => 'Редактирование',
            'delete' => 'Удаление',
            'sort' => 'Сортировка',
        ],

        'menus' => [
            'title' => 'Меню',
            'cms-pages' => 'Страницы CMS',
            'create' => 'Создать меню',
            'edit' => 'Редактировать меню',
            'fields' => [
                'name' => 'Название',
                'code' => 'Код',
                'location' => 'Локация',
                'location-select-placeholder' => 'Выберите локацию',
                'status' => 'Статус',
            ],
            'actions' => [
                'items' => 'Пункты',
            ],
            'messages' => [
                'create-success' => 'Меню успешно создано.',
                'update-success' => 'Меню успешно обновлено.',
                'delete-success' => 'Меню успешно удалено.',
            ],
        ],

        'items' => [
            'title' => 'Пункты меню',
            'messages' => [
                'create-success' => 'Пункт меню успешно создан.',
                'update-success' => 'Пункт меню успешно обновлен.',
                'delete-success' => 'Пункт меню успешно удален.',
                'sort-success' => 'Сортировка пунктов меню сохранена.',
            ],
        ],
    ],
];
