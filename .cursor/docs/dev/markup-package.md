# Пакет Markup (Webkul\Markup)

Массовое управление наценками и скидками на товары по расписанию. Изменяет реальные значения price/old_price/special_price в product_attribute_values и переиндексирует продукты.

---

## Структура

| Слой | Путь |
|------|------|
| Пакет | `packages/Webkul/Markup/` |
| Namespace | `Webkul\Markup\` |
| Provider | `MarkupServiceProvider` |
| Модели | MarkupGroup, MarkupGroupSchedule, MarkupCondition, MarkupAppliedPrice, MarkupLog |
| Контроллер | `Admin\MarkupGroupController` |
| Form Request | `MarkupGroupRequest` |
| DataGrid | `MarkupGroupDataGrid` |
| Сервисы | `MarkupPriceService`, `MarkupScheduleService` |
| Jobs | `ApplyMarkupJob`, `RevertMarkupJob` |
| Тесты | `tests/Feature/MarkupGroupTest.php` |

---

## Концепция

### Группа наценки (MarkupGroup)

Центральная сущность. Тип: `markup` (наценка) или `discount` (скидка). Содержит:
- Расписания (schedules) — когда применять
- Условия (conditions) — к каким товарам и с какой формулой
- Привязку к складам (inventory_sources)
- Флаг `is_applied` — применена ли сейчас
- `jobs_version` — версионирование для отмены устаревших джобов

### Алгоритм применения (MarkupPriceService::apply)

1. Загрузить conditions по sort_order
2. Для каждого condition найти продукты (по категориям, конкретным product_id, диапазону cost)
3. Исключить уже обработанные продукты (по processedProductIds)
4. Рассчитать adjustment (percent или fixed от cost)
5. **markup**: `price = cost + adjustment`, `old_price = original_price`
6. **discount**: `price = cost`, `special_price = cost - adjustment`
7. Сохранить оригиналы в `markup_applied_prices` для отката
8. Переиндексировать (Price + Flat indexers)
9. Записать лог

### Алгоритм отката (MarkupPriceService::revert)

1. Загрузить applied_prices для группы
2. Восстановить оригинальные значения price, old_price, special_price
3. Удалить записи applied_prices
4. Переиндексировать
5. Записать лог

### Расписание (MarkupScheduleService)

- `daily` — каждый день в указанное time_from..time_to
- `weekly` — по дням недели (day_of_week 0-6)
- Методы: `isInScheduleWindow()`, `secondsUntilApply()`, `secondsUntilRevert()`
- Jobs цепочка: ApplyMarkupJob → (delay) → RevertMarkupJob → (delay) → ApplyMarkupJob

### Версионирование джобов

`jobs_version` инкрементируется при обновлении группы. Устаревшие джобы проверяют версию и пропускают выполнение, если она не совпадает.

---

## Таблицы БД

### `markup_groups`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| name | string | Название группы |
| description | text nullable | Описание |
| type | enum(markup, discount) | Тип операции |
| is_active | boolean default true | Активна ли |
| schedule_type | enum(daily, weekly) | Тип расписания |
| apply_to_all_sources | boolean default true | Все склады |
| sort_order | int default 0 | Порядок |
| is_applied | boolean default false | Применена ли |
| jobs_version | uint default 0 | Версия джобов |
| timestamps | | |

### `markup_group_schedules`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| markup_group_id | FK → markup_groups | |
| day_of_week | tinyint unsigned nullable | 0=Вс, 1=Пн...6=Сб |
| time_from | time | Начало |
| time_to | time | Конец |
| timestamps | | |

### `markup_group_inventory_sources` (pivot)

| Поле | Тип |
|------|-----|
| id | bigint PK |
| markup_group_id | FK → markup_groups |
| inventory_source_id | FK → inventory_sources |

### `markup_conditions`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| markup_group_id | FK → markup_groups | |
| cost_from | decimal(12,4) nullable | Мин. себестоимость |
| cost_to | decimal(12,4) nullable | Макс. себестоимость |
| adjustment_type | enum(percent, fixed) | Тип корректировки |
| adjustment_value | decimal(12,4) | Значение |
| sort_order | int default 0 | Приоритет |
| timestamps | | |

### `markup_condition_categories` (pivot)

markup_condition_id → markup_conditions, category_id → categories

### `markup_condition_products` (pivot)

markup_condition_id → markup_conditions, product_id → products

### `markup_applied_prices`

Хранит оригинальные и применённые цены для отката.

| Поле | Тип |
|------|-----|
| id | bigint PK |
| markup_group_id | FK → markup_groups |
| product_id | FK → products |
| original_price, original_old_price, original_special_price | decimal(12,4) nullable |
| applied_price, applied_old_price, applied_special_price | decimal(12,4) nullable |
| timestamps | |
| UNIQUE(markup_group_id, product_id) | |

### `markup_logs`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| markup_group_id | FK → markup_groups | |
| action | string | applied / reverted |
| products_affected | uint default 0 | Кол-во затронутых |
| message | text nullable | Сообщение |
| metadata | json nullable | Доп. данные |
| timestamps | | |

---

## Маршруты

Префикс: `{admin_url}/markup/groups`, middleware: `web`, `admin`.

| Метод | URI | Action | Name |
|-------|-----|--------|------|
| GET | `/` | index | admin.markup.groups.index |
| GET | `/create` | create | admin.markup.groups.create |
| POST | `/create` | store | admin.markup.groups.store |
| GET | `/edit/{id}` | edit | admin.markup.groups.edit |
| PUT | `/edit/{id}` | update | admin.markup.groups.update |
| DELETE | `/edit/{id}` | destroy | admin.markup.groups.destroy |

---

## Валидация (MarkupGroupRequest)

| Поле | Правила |
|------|---------|
| name | required, string, max:255 |
| description | nullable, string, max:2000 |
| type | required, in:markup,discount |
| is_active | required, boolean |
| schedule_type | required, in:daily,weekly |
| apply_to_all_sources | required, boolean |
| sort_order | nullable, integer, min:0 |
| inventory_sources | nullable, array of existing inventory_sources.id |
| schedules | required, array, min:1 |
| schedules.*.day_of_week | nullable, int 0-6 |
| schedules.*.time_from | required, H:i |
| schedules.*.time_to | required, H:i, after time_from |
| conditions | required, array, min:1 |
| conditions.*.cost_from | nullable, numeric, min:0 |
| conditions.*.cost_to | nullable, numeric, min:0 |
| conditions.*.adjustment_type | required, in:percent,fixed |
| conditions.*.adjustment_value | required, numeric, min:0.0001 |
| conditions.*.categories | nullable, array of existing categories.id |
| conditions.*.products | nullable, array of existing products.id |

---

## ACL

Ключи ACL под `catalog.markup`:
- `catalog.markup` — просмотр списка
- `catalog.markup.create` — создание
- `catalog.markup.edit` — редактирование
- `catalog.markup.delete` — удаление

---

## Меню админки

Пункт `catalog.markup` → route `admin.markup.groups.index`, sort: 5.

---

## Views

Namespace: `markup::admin.groups.*`

| Файл | Назначение |
|------|------------|
| `groups/index.blade.php` | Список (DataGrid) |
| `groups/create.blade.php` | Форма создания |
| `groups/edit.blade.php` | Форма редактирования |
| `groups/_form.blade.php` | Переиспользуемая форма |

---

## Тесты

Файл: `packages/Webkul/Markup/tests/Feature/MarkupGroupTest.php`

Покрытие:
- Отображение index/create/edit страниц
- Создание markup-группы (daily + percent)
- Создание discount-группы (weekly + fixed + cost range)
- Обновление группы
- Удаление группы
- Валидация обязательных полей
