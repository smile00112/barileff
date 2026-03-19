<?php

return [
    'admin' => [
        'groups' => [
            'created' => 'Группа наценки успешно создана.',
            'updated' => 'Группа наценки успешно обновлена.',
            'deleted' => 'Группа наценки успешно удалена.',

            'index' => [
                'title'      => 'Группы наценок',
                'create-btn' => 'Создать группу',
            ],

            'create' => [
                'title'    => 'Создать группу наценки',
                'save-btn' => 'Сохранить',
            ],

            'edit' => [
                'title'    => 'Редактировать группу наценки',
                'save-btn' => 'Сохранить',
            ],

            'form' => [
                'general'              => 'Основная информация',
                'name'                 => 'Название',
                'description'          => 'Описание',
                'type'                 => 'Тип',
                'type-markup'          => 'Наценка',
                'type-discount'        => 'Скидка',
                'schedule-type'        => 'Тип расписания',
                'daily'                => 'Ежедневно',
                'weekly'               => 'Еженедельно',
                'status'               => 'Статус',
                'active'               => 'Активна',
                'inactive'             => 'Неактивна',
                'sort-order'           => 'Порядок сортировки',
                'apply-to-all-sources' => 'Применять ко всем складам',
                'yes'                  => 'Да',
                'no'                   => 'Нет',

                'schedules'   => 'Расписание',
                'day-of-week' => 'День недели',
                'every-day'   => 'Каждый день',
                'time-from'   => 'Время с',
                'time-to'     => 'Время до',
                'add-schedule' => 'Добавить расписание',
                'sunday'      => 'Воскресенье',
                'monday'      => 'Понедельник',
                'tuesday'     => 'Вторник',
                'wednesday'   => 'Среда',
                'thursday'    => 'Четверг',
                'friday'      => 'Пятница',
                'saturday'    => 'Суббота',

                'conditions'              => 'Условия',
                'condition'               => 'Условие',
                'cost-from'               => 'Себестоимость от',
                'cost-to'                 => 'Себестоимость до',
                'adjustment-type'         => 'Тип корректировки',
                'adjustment-value'        => 'Значение корректировки',
                'percent'                 => 'Процент',
                'fixed'                   => 'Фиксированная',
                'categories'              => 'Категории (ID)',
                'products'                => 'Товары (ID)',
                'categories-placeholder'  => 'напр. 1, 2, 3',
                'products-placeholder'    => 'напр. 10, 20, 30',
                'add-condition'           => 'Добавить условие',

                'logs'         => 'Журнал действий',
                'log-action'   => 'Действие',
                'log-products' => 'Товаров',
                'log-message'  => 'Сообщение',
                'log-date'     => 'Дата',
            ],
        ],

        'datagrid' => [
            'id'             => 'ID',
            'name'           => 'Название',
            'type'           => 'Тип',
            'schedule-type'  => 'Расписание',
            'status'         => 'Статус',
            'applied'        => 'Применено',
            'active'         => 'Активна',
            'inactive'       => 'Неактивна',
            'yes'            => 'Да',
            'no'             => 'Нет',
            'edit'           => 'Редактировать',
            'delete'         => 'Удалить',

            'types' => [
                'markup'   => 'Наценка',
                'discount' => 'Скидка',
            ],

            'schedule-types' => [
                'daily'  => 'Ежедневно',
                'weekly' => 'Еженедельно',
            ],
        ],
    ],
];
