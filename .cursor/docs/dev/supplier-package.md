# Пакет Supplier (Webkul\Supplier)

Управление поставщиками товаров. Поставщик привязывается к продукту через `supplier_id`.

---

## Структура

| Слой | Путь |
|------|------|
| Пакет | `packages/Webkul/Supplier/` |
| Namespace | `Webkul\Supplier\` |
| Provider | `SupplierServiceProvider` |
| Модель | `Supplier` |
| Контроллер | `Admin\SupplierController` |
| Form Request | `SupplierRequest` |
| DataGrid | `SupplierDataGrid` |
| Репозиторий | `SupplierRepository` |

---

## Таблицы БД

### `suppliers`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| name | string | Название поставщика |
| contact_name | string nullable | Контактное лицо |
| contact_email | string nullable | Email |
| contact_phone | string(50) nullable | Телефон |
| address | text nullable | Адрес |
| notes | text nullable | Заметки |
| status | boolean default true | Активен/неактивен |
| timestamps | | |

### `products` (миграция)

Добавлен `supplier_id` (nullable, FK → suppliers.id, nullOnDelete) в таблицы `products` и `product_flat`.

---

## Маршруты

Префикс: `{admin_url}/suppliers`, middleware: `web`, `admin`.

| Метод | URI | Action | Name |
|-------|-----|--------|------|
| GET | `/` | index | admin.suppliers.index |
| GET | `/create` | create | admin.suppliers.create |
| POST | `/create` | store | admin.suppliers.store |
| GET | `/edit/{id}` | edit | admin.suppliers.edit |
| PUT | `/edit/{id}` | update | admin.suppliers.update |
| DELETE | `/edit/{id}` | destroy | admin.suppliers.destroy |

---

## Модель Supplier

- Реализует `Webkul\Supplier\Contracts\Supplier`
- Связь: `products(): HasMany` → `ProductProxy` через `supplier_id`
- Casts: `status` → boolean
- Fillable: name, contact_name, contact_email, contact_phone, address, notes, status

---

## Валидация (SupplierRequest)

| Поле | Правила |
|------|---------|
| name | required, string, max:255 |
| contact_name | nullable, string, max:255 |
| contact_email | nullable, email, max:255 |
| contact_phone | nullable, string, max:50 |
| address | nullable, string, max:1000 |
| notes | nullable, string, max:2000 |
| status | required, boolean |

---

## Views

| Файл | Назначение |
|------|------------|
| `admin/index.blade.php` | Список поставщиков (DataGrid) |
| `admin/create.blade.php` | Форма создания |
| `admin/edit.blade.php` | Форма редактирования |

View namespace: `supplier::admin.*`

---

## Локализация

- `en/app.php`, `ru/app.php`
- Ключ: `supplier::app.admin.*`
