# Delivery Zones — бейджи статуса активности

**Дата:** 2026-04-21

## Проблема

В DataGrid-таблицах `/admin/settings/delivery-zones` и `/admin/settings/delivery-cities` колонка `is_active` отображает статус как plain-text. Нужно привести к единому стилю с остальными DataGrid-ами (Categories, Orders, Reviews), где используются CSS-классы `label-active` / `label-canceled`.

## Изменяемые файлы

| Файл | Тип |
|------|-----|
| `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryCitiesDataGrid.php` | Правка closure |
| `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryZonesDataGrid.php` | Правка closure |

## Изменение

В каждом DataGrid closure колонки `is_active` оборачивает текст в HTML-тег:

```php
// До
'closure' => fn ($row) => $row->is_active
    ? trans('...active')
    : trans('...inactive'),

// После
'closure' => fn ($row) => $row->is_active
    ? '<p class="label-active">'.trans('...active').'</p>'
    : '<p class="label-canceled">'.trans('...inactive').'</p>',
```

## Не требуется

- Новые ключи переводов (уже есть `active` / `inactive`)
- CSS-правки (`label-active` / `label-canceled` определены в `Admin/src/Resources/assets/css/app.css`)
- Изменения в шаблонах, маршрутах, моделях, тестах
