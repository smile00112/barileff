# Руководство по разработке фронтенда: модуль DeliveryZones

Справочник для разработки и поддержки фронтенда модуля DeliveryZones — админ-панель (Cities, Zones, карта) и связанные view в Shop (checkout, оценка доставки).

---

## 1. Обзор модуля

| Параметр | Значение |
|----------|----------|
| Пакет | `packages/Webkul/DeliveryZones` |
| Правила | [.cursor/rules/deliveries/delivery-zones.mdc](../rules/deliveries/delivery-zones.mdc) |
| Admin | Cities, Zones, CRUD, карта полигонов |
| Shop | Checkout, оценка доставки, выбор зоны |

---

## 2. Admin frontend — ключевые пути

| Сущность | Views | DataGrid |
|----------|-------|----------|
| Delivery Cities | `DeliveryZones/src/Resources/views/settings/delivery-cities/` | `DeliveryCitiesDataGrid` |
| Delivery Zones | `DeliveryZones/src/Resources/views/settings/delivery-zones/` | `DeliveryZonesDataGrid` |
| Zones по городу | `delivery-cities/zones.blade.php` | — |

### Файлы

| Файл | Назначение |
|------|------------|
| `index.blade.php` | Список + DataGrid + кнопка Add |
| `create.blade.php` | Форма создания |
| `edit.blade.php` | Форма редактирования |
| `zones.blade.php` | Управление зонами в контексте города (карта + список + inline-форма) |

---

## 3. Компоненты и паттерны

### Layout и формы

- **`x-admin::layouts`** — обёртка страницы
- **`x-admin::form`** — форма: `:action="route(...)"`, `method="PUT"` для edit
- **`x-admin::form.control-group`** — группа полей: `label`, `control`, `error`
- **`x-admin::form.control-group.control`** — типы: `text`, `number`, `select`, `textarea`, `color`, `switch`, `hidden`
- **`x-admin::datagrid`** — `:src="route('admin.settings.delivery_cities.index')"` и т.п.
- **`x-admin::accordion`** — сайдбар Settings (Active/Inactive и т.д.)

### Кнопки

| Класс | Назначение |
|-------|------------|
| `.primary-button` | Save, Add |
| `.secondary-button` | Вспомогательные действия (Clear Polygon, Add Rate) |
| `.transparent-button` | Back |

### Карточки (Tailwind)

```css
/* Основная карточка */
.bg-white.dark:bg-gray-800.rounded-lg.shadow-sm.border.border-gray-200.dark:border-gray-700

/* Контент секции */
.p-6.space-y-6   /* или .p-4 */
```

### Ссылка на полный гайд

См. [.cursor/rules/tailwind-admin-rules.mdc](../rules/tailwind-admin-rules.mdc) — полный справочник по Tailwind в админке.

---

## 4. Yandex Maps — карты и полигоны

### Подключение

- API-ключ: `config('services.yandex_maps.api_key')`
- Скрипт: `https://api-maps.yandex.ru/2.1/?lang=ru_RU` (+ `&apikey=` при наличии ключа)
- Подключение в Blade: `@pushOnce('scripts')` → `<script src="{{ $yandexMapsScriptUrl }}"></script>`

### Формат полигона

- Массив координат: `[[lat, lng], [lat, lng], ...]`
- Минимум 3 вершины
- Закрывающая точка (повтор первой) при сохранении отбрасывается

### Создание и редактирование

```javascript
// Yandex Maps Polygon
polygonObject = new ymaps.Polygon([coordinates], {}, {
    fillColor: '#0077cc33',
    strokeColor: '#0077cc',
    strokeWidth: 3,
});
polygonObject.editor.startEditing();  // включить редактирование
polygonObject.editor.stopEditing();   // выключить
```

### Контейнер карты

| ID | Где используется |
|----|------------------|
| `#zone-map` | Delivery Zone (edit, zones) |
| `#city-map` | Delivery City (edit) |

Высота: `h-[400px]`, бордер: `rounded-md border border-gray-300 dark:border-gray-600`

### Delivery City (edit)

- Поля: `center_lat`, `center_lng` — центр города
- Кнопка «Set Center» — режим установки центра по клику на карту
- Placemark: `ymaps.Placemark(point, {}, { preset: 'islands#redDotIcon' })`
- Режимы: Edit Polygon, Set Center

### Delivery Zone (edit)

- Поля оформления: `polygon_color`, `polygon_fill_opacity`, `polygon_stroke_opacity`
- Кнопки: Clear Polygon, Apply JSON (применить JSON из textarea)

---

## 5. Динамические элементы форм

### Rates (тарифы зоны)

| Элемент | ID/Класс |
|---------|----------|
| Контейнер строк | `#rates-wrapper` |
| Кнопка добавления | `#add-rate-row` |
| Кнопка удаления | `.remove-rate` |

**Структура полей:**

- `rates[index][min_order_total]`
- `rates[index][price]`
- `rates[index][sort_order]`

**Grid:** `grid grid-cols-4 gap-2 max-md:grid-cols-1`

### Добавление строки (Vanilla JS)

В `edit.blade.php` — шаблон через `innerHTML`, инкремент `ratesIndex`. Новые input должны иметь `class="control w-full"` для совместимости с VeeValidate.

---

## 6. Переводы (lang)

- Префикс: `admin::app.settings.delivery_zones.*`
- Группы: `edit.*`, `zones.*`

**Примеры ключей:**

| Ключ | Описание |
|------|----------|
| `edit.title` | Заголовок страницы edit |
| `edit.city` | Город |
| `edit.code` | Код |
| `edit.zone-name` | Название зоны |
| `edit.polygon-json` | Polygon JSON |
| `edit.zone-rates` | Тарифы зоны |
| `zones.manage-title` | Заголовок управления зонами |
| `zones.add-new-zone` | Добавить зону |

Использование: `@lang('admin::app.settings.delivery_zones.edit.title')`

---

## 7. Shop frontend (связанный контекст)

### Checkout / API

- **CartDeliveryZoneManager::applySelection()** принимает: `delivery_point_lat`, `delivery_point_lng`, `delivery_zone_id`
- Onepage: `route('shop.api.checkout.onepage.save_address')` — передача этих полей при сохранении адреса
- Оценка доставки: `estimate-shipping.blade.php` — Vue-компонент `v-estimate-tax-shipping`, маршрут `shop.api.checkout.cart.estimate_shipping`

### View-события

- `bagisto.shop.checkout.onepage.address.before` / `after`
- `bagisto.shop.checkout.cart.summary.estimate_shipping.before` / `after`
- `bagisto.shop.checkout.cart.summary.estimate_shipping.shipping_method.before` / `after`

---

## 8. Чек-лист при изменениях

- [ ] Поддержка dark mode (`dark:`)
- [ ] Использование `@lang()` вместо хардкода
- [ ] `x-admin::form.control-group.error control-name="..."` для вывода ошибок валидации
- [ ] `@pushOnce('scripts')` для inline-скриптов
- [ ] Обновление тестов: `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php`
