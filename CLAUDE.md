# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

This is a **customized fork of Bagisto** — an open-source Laravel e-commerce platform. The fork (branch `add_product_import_add_fcm`) adds custom modules on top of upstream Bagisto 2.3: delivery zones, supplier management, push notifications (FCM), markup scheduling, product tags, and various store-specific features.

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

Package tests go in `packages/Webkul/<Domain>/tests/Feature/` or `tests/Unit/`. Use Pest style, factories, and behavior-focused test names.

### Code Style

```bash
# Format changed files
vendor/bin/pint --dirty

# Format all files
vendor/bin/pint
```

Pint uses the `laravel` preset (`pint.json`). Always run before finalizing PHP changes.

---

## Architecture

### Package-based Monorepo

All business logic lives in `packages/Webkul/`. Each package is a Laravel service provider that auto-registers via `composer.json` path repositories and PSR-4 autoload entries. Packages follow this internal structure:

```
packages/Webkul/<Package>/src/
  Config/         # menu.php, acl.php, system.php configs merged into app
  Contracts/      # Interfaces (e.g. Supplier.php)
  Database/       # Migrations, Seeders, Factories
  Http/
    Controllers/
    Requests/     # Form Request validation classes
    Resources/    # API Resources
  Models/         # Model.php + ModelProxy.php
  Repositories/   # Extend Webkul\Core\Eloquent\Repository (l5-repository)
  Resources/
    views/        # Blade views
    lang/         # Translations
  Providers/      # XxxServiceProvider.php + ModuleServiceProvider.php
  DataGrids/      # Admin data grid definitions
  Routes/         # admin-routes.php, rest-routes.php, etc.
tests/Feature/    # Pest feature tests
```

Documentation for custom packages lives in `.cursor/docs/dev/<package>-package.md`.

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
| `DeliveryZones` | Custom: zone-based delivery with Yandex Maps polygons, rates, warehouse binding |
| `Supplier` | Custom: supplier management, `supplier_id` on products |
| `Markup` | Custom: bulk markups/discounts on schedule — Apply→Revert job chain |
| `ProductTag` | Custom: AI tag generation via GigaChat, product_tag pivot |
| `PushNotification` | Custom: FCM push notifications |
| `MagicAI` | AI integration (OpenAI/GigaChat) |
| `Theme` | `ThemeViewFinder` overrides Laravel's view finder for theme cascading. `@bagistoVite` Blade directive. `ViewRenderEventManager` for layout injection points. |
| `Installer` | Web-based installer and onboarding |

### External Integrations

- **GigaChat** (`tigusigalpa/gigachat-php`) — AI tag generation (`ProductTag`)
- **OpenAI** (`openai-php/laravel`) — MagicAI features
- **Yandex Maps API** — delivery zone polygon maps
- **Elasticsearch 7.17** — product search

### Repository Pattern

All data access goes through repository classes extending `Webkul\Core\Eloquent\Repository` (which extends `prettus/l5-repository`). Direct `Model::` calls are acceptable inside repositories; prefer repositories over raw DB queries elsewhere.

Keep controllers thin — delegate domain logic to repositories, services, or action classes. Use Eloquent relations and eager loading to avoid N+1 queries.

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

Vue.js components are embedded within Blade views using `<v-*>` components registered per-page. Vite bundles assets from `resources/`. Use `@bagistoVite` directive for asset inclusion. Keep business logic out of Blade templates; move it to Vue methods.

---

## Custom Additions in This Fork

The `add_product_import_add_fcm` branch adds:

1. **FCM Push Notifications** (`PushNotification` package) — Firebase Cloud Messaging integration
2. **Product Import enhancements** (`DataTransfer` package)
3. **Delivery Zones** (`DeliveryZones` package) — zone-based delivery with Yandex Maps polygon support
4. **Supplier module** (`Supplier` package)
5. **Markup module** (`Markup` package) — scheduled bulk price adjustments
6. **ProductTag module** (`ProductTag` package) — AI-assisted tagging via GigaChat
7. **PostgreSQL compatibility** — migrations and seeds adjusted for both MySQL and PostgreSQL

### Docker Setup

Two Docker Compose configs:
- `docker-compose.yml` — local dev via Laravel Sail (MySQL + Redis + Elasticsearch + Mailpit)
- `docker-compose.prod.yml` — production (RoadRunner/Octane, Nginx, MySQL, PostgreSQL 16, Redis, Elasticsearch, Reverb, queue worker, Certbot)

Development (Sail) services:
- App: port 80, Vite: 5173
- PostgreSQL 16: port 5432 (db: `bagisto_pg`, user: `sail`, pass: `secret`)
- Redis: port 6379
- Elasticsearch 7.17: ports 9200/9300
- Adminer: port 2280
- Mailpit: ports 1025/8025

Production notes:
- No `Dockerfile` exists at root — must be provided externally before running `docker-compose.prod.yml`
- `RUN_MIGRATIONS=false` by default; set `true` only for intentional migration runs
- After deploying code changes, restart the `app` container — `opcache.validate_timestamps=0` means PHP won't auto-detect file changes
- Elasticsearch requires `vm.max_map_count=262144` on the Docker host

---

## Conventions

### PHP Style

- Use explicit parameter and return types
- Use constructor property promotion: `public function __construct(private readonly FooRepository $fooRepo) {}`
- Use descriptive method names (`isEligibleForDiscount`, not `discount`)
- Never swallow exceptions silently; log with actionable context (`entity_id`, etc.)
- Log levels: `debug` (local diagnostics), `info` (business events), `warning` (recoverable), `error` (failed ops), `critical` (service-threatening)

### Architecture Rules

- Scope changes to the requested package/domain — do not refactor unrelated code
- **Do not add new dependencies without explicit approval** — prefer built-in Laravel/PHP features
- Form Request classes for all validation — no `$request->validate()` inline in controllers
- New packages: `ServiceProvider` + `ModuleServiceProvider`, Contracts interface, Model+Proxy pair, Repository extending `Webkul\Core\Eloquent\Repository`
- Translations use package namespacing: `trans('admin::app.catalog.products.title')`
- ACL entries in `Config/acl.php`, menu entries in `Config/menu.php`
- System configuration in `Config/system.php` (merges into `config('core')`)

### Git Workflow

- Branch names should be traceable to an issue: `issue-1234`
- Commit format: `Fixed #1234 - <subject>` for bug fixes; `feat(scope): short summary` / `fix(scope): short summary` also acceptable
- Every behavioral code change must include or update a test
