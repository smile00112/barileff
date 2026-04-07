# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

This is a **customized fork of Bagisto** — an open-source Laravel e-commerce platform. The fork (branch `add_product_import_add_fcm`) adds custom modules on top of upstream Bagisto 2.3: delivery zones, supplier management, push notifications (FCM), and various store-specific features.

- PHP 8.2+, Laravel 11, Vue.js (frontend components embedded in Blade)
- Supports both **PostgreSQL** (primary via Docker) and MySQL
- Pest 3 for testing, Laravel Pint for formatting

---

## Commands

### Development

```bash
# Install dependencies
composer install
npm install

# Start dev server (Vite)
npm run dev

# Build frontend assets
npm run build

# Run via Docker (PostgreSQL + Redis + Elasticsearch)
docker compose up -d
```

### Artisan

```bash
php artisan serve
php artisan migrate
php artisan db:seed
php artisan bagisto:install   # Full installer with seed data
```

### Testing

```bash
# Run all tests
php artisan test --compact

# Run a specific test file
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/ProductTest.php

# Filter by test name
php artisan test --compact --filter=testProductCreate

# Test suites by package
php artisan test --compact --testsuite="Admin Feature Test"
php artisan test --compact --testsuite="Shop Feature Test"
```

Test suites are defined in `phpunit.xml`. Each package with tests has its own suite:
- `Admin Feature Test` → `packages/Webkul/Admin/tests/Feature/`
- `Shop Feature Test` → `packages/Webkul/Shop/tests/Feature/`
- `Core Unit Test`, `DataGrid Unit Test`, `Installer Feature Test`

### Code Style

```bash
# Format changed files
vendor/bin/pint --dirty

# Format all files
vendor/bin/pint
```

Pint uses the `laravel` preset (`pint.json`).

---

## Architecture

### Package-based Monorepo

All business logic lives in `packages/Webkul/`. Each package is a Laravel service provider that auto-registers via `composer.json` path repositories and PSR-4 autoload entries. Packages follow this internal structure:

```
packages/Webkul/<Package>/src/
  Config/         # menu.php, acl.php, system.php configs merged into app
  Database/       # Migrations, Seeders, Factories
  Http/
    Controllers/
    Requests/     # Form Request validation classes
    Resources/    # API Resources
  Models/
  Repositories/   # Extend Webkul\Core\Eloquent\Repository (l5-repository)
  Resources/
    views/        # Blade views
    lang/         # Translations
  Providers/      # XxxServiceProvider.php — registers routes, views, events
  DataGrids/      # Admin data grid definitions
  Routes/         # web.php, rest-routes.php, etc.
```

### Key Packages

| Package | Purpose |
|---|---|
| `Core` | Base infrastructure: `Repository`, helpers, channels, locales, currencies, system config, ACL, menus |
| `Admin` | Admin panel — controllers, views, routes, DataGrids |
| `Shop` | Storefront — controllers, views, routes |
| `Product` | Product types (Simple, Configurable, Bundle, Grouped, Downloadable, Virtual, Booking) |
| `DataTransfer` | Import/export system — CSV importers for products, customers, tax rates |
| `Attribute` | EAV attribute system used by products |
| `Category` | Category tree (nested set via kalnoy/nestedset) |
| `Checkout` | Cart, shipping, payment flow |
| `Sales` | Orders, invoices, shipments, refunds |
| `Customer` | Customer accounts, addresses, groups |
| `DeliveryZones` | Custom: zone-based delivery with map/distance support |
| `Supplier` | Custom: supplier management |
| `PushNotification` | Custom: FCM push notifications |
| `MagicAI` | AI integration (OpenAI/GigaChat) |
| `Theme` | `ThemeViewFinder` overrides Laravel's view finder for theme cascading. `@bagistoVite` Blade directive. `ViewRenderEventManager` for layout injection points. |
| `Installer` | Web-based installer and onboarding |

### Repository Pattern

All data access goes through repository classes extending `Webkul\Core\Eloquent\Repository` (which extends `prettus/l5-repository`). Direct `Model::` calls are acceptable inside repositories; prefer repositories over raw DB queries elsewhere.

### Routing

- **Admin routes**: prefix from `config('app.admin_url')`, protected by `admin` middleware, defined in `packages/Webkul/Admin/src/Routes/`
- **Shop routes**: defined in `packages/Webkul/Shop/src/Routes/`
- **REST API routes**: `rest-routes.php` within each package

### View System

The `Theme` package's `ThemeViewFinder` cascades view resolution — child themes can override any package view. Views are namespaced: `admin::catalog.products.index`, `shop::checkout.cart`, etc. Layout injection uses `ViewRenderEventManager` events like `bagisto.admin.layout.head`.

### DataGrid

Admin list views use `packages/Webkul/DataGrid/` — each grid class defines columns, filters, and actions. Located in each package's `DataGrids/` directory.

### EAV (Attribute System)

Products use EAV via `Webkul\Attribute`. Product attribute values are stored separately from product base data. The `ProductRepository` handles this complexity — prefer it over direct model queries for product data.

### Frontend

Vue.js components are embedded within Blade views using `<v-*>` components registered per-page. Vite bundles assets from `resources/`. Use `@bagistoVite` directive for asset inclusion.

---

## Custom Additions in This Fork

The `add_product_import_add_fcm` branch adds:

1. **FCM Push Notifications** (`PushNotification` package) — Firebase Cloud Messaging integration
2. **Product Import enhancements** (`DataTransfer` package)
3. **Delivery Zones** (`DeliveryZones` package) — zone-based delivery with distance/map support
4. **Supplier module** (`Supplier` package)
5. **PostgreSQL compatibility** — migrations and seeds adjusted for both MySQL and PostgreSQL

### Docker Setup (PostgreSQL)

Primary development uses PostgreSQL via Docker Compose:
- App: port 80, Vite: 5173
- PostgreSQL 16: port 5432 (db: `bagisto_pg`, user: `sail`, pass: `secret`)
- Redis: port 6379
- Elasticsearch 7.17: ports 9200/9300
- Adminer: port 2280
- Mailpit: ports 1025/8025

---

## Conventions

- Follow the **Repository pattern** — no direct Eloquent calls in controllers
- Form Request classes for all validation (see `packages/Webkul/Admin/src/Http/Requests/`)
- New packages must have a `ServiceProvider` that registers migrations, translations, views, and routes
- Translations use package namespacing: `trans('admin::app.catalog.products.title')`
- ACL entries defined in `Config/acl.php`, menu entries in `Config/menu.php`
- System configuration in `Config/system.php` (merges into `config('core')`)
- Run `vendor/bin/pint --dirty` before finalizing any PHP changes
