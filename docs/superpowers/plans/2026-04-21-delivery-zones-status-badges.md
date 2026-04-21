# Delivery Zones Status Badges Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Заменить plain-text статус активности на HTML-бейджи `label-active` / `label-canceled` в DataGrid-таблицах городов и зон доставки.

**Architecture:** Только правка `closure` в двух DataGrid-классах в воркдереве `.worktrees/payment-confirmation/`. CSS-классы уже определены в Admin-ассетах, переводы уже есть.

**Tech Stack:** PHP 8.2, Laravel 11, Bagisto DataGrid

---

### Task 1: Бейдж статуса в DeliveryCitiesDataGrid

**Files:**
- Modify: `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryCitiesDataGrid.php:60-69`

- [ ] **Step 1: Заменить closure колонки `is_active`**

Открыть файл `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryCitiesDataGrid.php`.

Найти блок (строки 60–69):
```php
$this->addColumn([
    'index' => 'is_active',
    'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.status'),
    'type' => 'boolean',
    'sortable' => true,
    'filterable' => true,
    'closure' => fn ($row) => $row->is_active
        ? trans('admin::app.settings.delivery_zones.datagrid.cities.active')
        : trans('admin::app.settings.delivery_zones.datagrid.cities.inactive'),
]);
```

Заменить на:
```php
$this->addColumn([
    'index' => 'is_active',
    'label' => trans('admin::app.settings.delivery_zones.datagrid.cities.status'),
    'type' => 'boolean',
    'sortable' => true,
    'filterable' => true,
    'closure' => fn ($row) => $row->is_active
        ? '<p class="label-active">'.trans('admin::app.settings.delivery_zones.datagrid.cities.active').'</p>'
        : '<p class="label-canceled">'.trans('admin::app.settings.delivery_zones.datagrid.cities.inactive').'</p>',
]);
```

- [ ] **Step 2: Форматирование**

```bash
vendor/bin/pint .worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryCitiesDataGrid.php
```

- [ ] **Step 3: Запустить тесты (regression check)**

```bash
php artisan test --compact --filter=DeliveryZonesTest
```

Ожидаемый вывод: все тесты PASSED.

- [ ] **Step 4: Коммит**

```bash
git -C .worktrees/payment-confirmation add packages/Webkul/DeliveryZones/src/DataGrids/DeliveryCitiesDataGrid.php
git -C .worktrees/payment-confirmation commit -m "feat(delivery-zones): add status badge to cities datagrid"
```

---

### Task 2: Бейдж статуса в DeliveryZonesDataGrid

**Files:**
- Modify: `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryZonesDataGrid.php:68-77`

- [ ] **Step 1: Заменить closure колонки `is_active`**

Открыть файл `.worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryZonesDataGrid.php`.

Найти блок (строки 68–77):
```php
$this->addColumn([
    'index' => 'is_active',
    'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.status'),
    'type' => 'boolean',
    'sortable' => true,
    'filterable' => true,
    'closure' => fn ($row) => $row->is_active
        ? trans('admin::app.settings.delivery_zones.datagrid.zones.active')
        : trans('admin::app.settings.delivery_zones.datagrid.zones.inactive'),
]);
```

Заменить на:
```php
$this->addColumn([
    'index' => 'is_active',
    'label' => trans('admin::app.settings.delivery_zones.datagrid.zones.status'),
    'type' => 'boolean',
    'sortable' => true,
    'filterable' => true,
    'closure' => fn ($row) => $row->is_active
        ? '<p class="label-active">'.trans('admin::app.settings.delivery_zones.datagrid.zones.active').'</p>'
        : '<p class="label-canceled">'.trans('admin::app.settings.delivery_zones.datagrid.zones.inactive').'</p>',
]);
```

- [ ] **Step 2: Форматирование**

```bash
vendor/bin/pint .worktrees/payment-confirmation/packages/Webkul/DeliveryZones/src/DataGrids/DeliveryZonesDataGrid.php
```

- [ ] **Step 3: Запустить тесты (regression check)**

```bash
php artisan test --compact --filter=DeliveryZonesTest
```

Ожидаемый вывод: все тесты PASSED.

- [ ] **Step 4: Коммит**

```bash
git -C .worktrees/payment-confirmation add packages/Webkul/DeliveryZones/src/DataGrids/DeliveryZonesDataGrid.php
git -C .worktrees/payment-confirmation commit -m "feat(delivery-zones): add status badge to zones datagrid"
```
