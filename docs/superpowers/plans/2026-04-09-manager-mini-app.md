# Manager Mini-App Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a warehouse manager PWA (Vue 3 + Laravel) that shows orders scoped to the manager's inventory source, supports live updates via Reverb WebSockets, WebPush notifications, and is installable as a PWA from `/manager`.

**Architecture:** New package `Webkul\ManagerApp` provides a REST API (Sanctum + custom middleware) consumed by a standalone Vue 3 SPA served at `/manager/{any}`. Orders are scoped to the manager via `orders.inventory_source_id`, populated from the cart on order creation. Reverb broadcasts order events to per-manager private channels; WebPush delivers background notifications.

**Tech Stack:** PHP 8.2 / Laravel 11, Laravel Reverb, Laravel Sanctum, Vue 3, Pinia, Vue Router 4, Laravel Echo, pusher-js, vite-plugin-pwa, Pest 3

**Spec:** `docs/superpowers/specs/2026-04-09-manager-mini-app-design.md`

---

## File Map

**New package: `packages/Webkul/ManagerApp/`**

```
composer.json
package.json
vite.config.js
src/
  Config/acl.php
  Database/Migrations/2026_04_09_000001_add_inventory_source_id_to_orders_table.php
  Events/
    ManagerOrderCreated.php
    ManagerOrderStatusUpdated.php
  Http/
    Controllers/
      AuthController.php
      OrderController.php
      PushController.php
    Middleware/ManagerAuthenticate.php
    Requests/UpdateOrderStatusRequest.php
    Resources/
      OrderResource.php
      OrderDetailResource.php
  Listeners/
    CopyInventorySourceToOrder.php
    BroadcastOrderEvents.php
  Providers/
    ManagerAppServiceProvider.php
    EventServiceProvider.php
  Routes/
    api.php
    web.php
  Services/ManagerOrderService.php
  Resources/views/app.blade.php
resources/js/
  main.js
  api.js
  App.vue
  router/index.js
  stores/auth.js
  stores/orders.js
  composables/useEcho.js
  composables/usePush.js
  pages/LoginPage.vue
  pages/OrdersPage.vue
  components/OrderCard.vue
  components/OrderFilters.vue
  components/StatusSelector.vue
tests/
  ManagerAppTestCase.php
  Feature/AuthTest.php
  Feature/OrderTest.php
  Feature/PushTest.php
```

**Modified existing files:**
- `packages/Webkul/PushNotification/src/Services/WebPushService.php` — add `sendToManagersForInventorySource()`
- `phpunit.xml` — add `ManagerApp Feature Test` suite
- `config/broadcasting.php` — add reverb connection

---

## Task 1: Install Laravel Reverb

**Files:**
- Modify: `config/broadcasting.php`
- Create: `config/reverb.php` (generated)

- [ ] **Step 1: Require Reverb**

```bash
composer require laravel/reverb
```

Expected: package installed successfully.

- [ ] **Step 2: Publish Reverb config**

```bash
php artisan reverb:install
```

This creates `config/reverb.php` and updates `config/broadcasting.php`. If asked about `routes/channels.php`, accept. If it already exists, skip.

- [ ] **Step 3: Add reverb connection to `config/broadcasting.php`**

After `php artisan reverb:install` the file should contain a `reverb` key. Verify the connections array now includes:

```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST'),
        'port' => env('REVERB_PORT', 443),
        'scheme' => env('REVERB_SCHEME', 'https'),
        'useTLS' => env('REVERB_SCHEME', 'https') === 'https',
    ],
    'client_options' => [],
],
```

If not present, add it manually to the `connections` array in `config/broadcasting.php`.

- [ ] **Step 4: Set env defaults for local dev**

Add to `.env` (do NOT commit):
```
BROADCAST_DRIVER=reverb
REVERB_APP_ID=manager-app
REVERB_APP_KEY=manager-key
REVERB_APP_SECRET=manager-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

- [ ] **Step 5: Commit**

```bash
git add config/broadcasting.php config/reverb.php
git commit -m "feat(manager-app): install Laravel Reverb for broadcasting"
```

---

## Task 2: Package Scaffold

**Files:**
- Create: `packages/Webkul/ManagerApp/composer.json`
- Create: `packages/Webkul/ManagerApp/src/Providers/ManagerAppServiceProvider.php`
- Create: `packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Create `packages/Webkul/ManagerApp/composer.json`**

```json
{
    "name": "webkul/manager-app",
    "description": "Manager PWA — warehouse order management",
    "type": "library",
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "Webkul\\ManagerApp\\": "src"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Webkul\\ManagerApp\\Tests\\": "tests"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Webkul\\ManagerApp\\Providers\\ManagerAppServiceProvider"
            ]
        }
    }
}
```

- [ ] **Step 2: Create stub service provider**

`packages/Webkul/ManagerApp/src/Providers/ManagerAppServiceProvider.php`:

```php
<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ManagerAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'manager-app');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        Route::middleware(['api'])->group(__DIR__.'/../Routes/api.php');

        Route::middleware(['web'])->group(__DIR__.'/../Routes/web.php');
    }
}
```

- [ ] **Step 3: Create stub event service provider**

`packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`:

```php
<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Listeners registered in Task 5 and Task 10
    }
}
```

- [ ] **Step 4: Create empty stub routes**

`packages/Webkul/ManagerApp/src/Routes/api.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Routes added in Tasks 6, 8, 9
```

`packages/Webkul/ManagerApp/src/Routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// SPA shell route added in Task 23
```

- [ ] **Step 5: Create stub view**

`packages/Webkul/ManagerApp/src/Resources/views/app.blade.php`:
```html
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Manager App</title></head>
<body><div id="app"></div></body>
</html>
```

- [ ] **Step 6: Add to phpunit.xml**

In `phpunit.xml`, inside `<testsuites>`, add after the last `</testsuite>`:

```xml
<!-- ManagerApp package testsuites. -->
<testsuite name="ManagerApp Feature Test">
    <directory suffix="Test.php">packages/Webkul/ManagerApp/tests/Feature</directory>
</testsuite>
```

- [ ] **Step 7: Update composer autoload and verify package discovered**

```bash
composer dump-autoload
php artisan package:discover
```

Expected output includes `Webkul\ManagerApp\Providers\ManagerAppServiceProvider`.

- [ ] **Step 8: Commit**

```bash
git add packages/Webkul/ManagerApp/ phpunit.xml
git commit -m "feat(manager-app): scaffold ManagerApp package"
```

---

## Task 3: Migration — inventory_source_id on orders

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Database/Migrations/2026_04_09_000001_add_inventory_source_id_to_orders_table.php`

- [ ] **Step 1: Write the failing test**

Create `packages/Webkul/ManagerApp/tests/ManagerAppTestCase.php`:

```php
<?php

namespace Webkul\ManagerApp\Tests;

use Tests\TestCase;

class ManagerAppTestCase extends TestCase
{
}
```

Create `packages/Webkul/ManagerApp/tests/Feature/OrderTest.php`:

```php
<?php

use Webkul\ManagerApp\Tests\ManagerAppTestCase;

uses(ManagerAppTestCase::class);

it('orders table has inventory_source_id column', function () {
    expect(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'inventory_source_id'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: FAIL — column does not exist.

- [ ] **Step 3: Create the migration**

`packages/Webkul/ManagerApp/src/Database/Migrations/2026_04_09_000001_add_inventory_source_id_to_orders_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('inventory_source_id')->nullable()->after('cart_id');
            $table->foreign('inventory_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['inventory_source_id']);
            $table->dropColumn('inventory_source_id');
        });
    }
};
```

- [ ] **Step 4: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Database/ packages/Webkul/ManagerApp/tests/
git commit -m "feat(manager-app): add inventory_source_id to orders table"
```

---

## Task 4: ACL Config + ManagerAuthenticate Middleware

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Config/acl.php`
- Create: `packages/Webkul/ManagerApp/src/Http/Middleware/ManagerAuthenticate.php`

- [ ] **Step 1: Create ACL config**

`packages/Webkul/ManagerApp/src/Config/acl.php`:

```php
<?php

return [
    [
        'key' => 'manager.app',
        'name' => 'manager-app::app.acl.manager-app',
        'route' => null,
        'sort' => 1,
    ],
    [
        'key' => 'manager.app.access',
        'name' => 'manager-app::app.acl.access',
        'route' => null,
        'sort' => 1,
    ],
];
```

- [ ] **Step 2: Write the failing test for middleware**

Add to `packages/Webkul/ManagerApp/tests/Feature/AuthTest.php`:

```php
<?php

use Webkul\ManagerApp\Tests\ManagerAppTestCase;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ManagerAppTestCase::class);

it('returns 401 when not authenticated on protected routes', function () {
    $this->getJson('/api/manager/auth/me')
        ->assertStatus(401);
});

it('returns 403 when admin lacks manager.app.access permission', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => [],
    ]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/auth/me')
        ->assertStatus(403);
});

it('returns 403 when admin has permission but no inventory source', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['manager.app.access'],
    ]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/auth/me')
        ->assertStatus(403);
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/AuthTest.php
```

Expected: FAIL — routes don't exist yet (404).

- [ ] **Step 4: Create the middleware**

`packages/Webkul/ManagerApp/src/Http/Middleware/ManagerAuthenticate.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (! $admin) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $admin->hasPermission('manager.app.access')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $admin->isInventorySourceRestricted()) {
            return response()->json(['message' => 'No warehouse assigned.'], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 5: Commit placeholder — routes come in Task 6**

```bash
git add packages/Webkul/ManagerApp/src/Config/ packages/Webkul/ManagerApp/src/Http/Middleware/ packages/Webkul/ManagerApp/tests/Feature/AuthTest.php
git commit -m "feat(manager-app): add ACL config and ManagerAuthenticate middleware"
```

---

## Task 5: CopyInventorySourceToOrder Listener

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Listeners/CopyInventorySourceToOrder.php`
- Modify: `packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`

- [ ] **Step 1: Write the failing test**

Add to `packages/Webkul/ManagerApp/tests/Feature/OrderTest.php`:

```php
use Webkul\Checkout\Models\Cart;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;

it('copies inventory_source_id from cart to order on creation', function () {
    $source = InventorySource::factory()->create();

    $cart = Cart::factory()->create(['inventory_source_id' => $source->id]);

    $order = Order::factory()->create(['cart_id' => $cart->id, 'inventory_source_id' => null]);

    event('checkout.order.save.after', $order);

    expect($order->fresh()->inventory_source_id)->toBe($source->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="copies inventory_source_id" packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: FAIL.

- [ ] **Step 3: Create the listener**

`packages/Webkul/ManagerApp/src/Listeners/CopyInventorySourceToOrder.php`:

```php
<?php

namespace Webkul\ManagerApp\Listeners;

use Webkul\Sales\Models\Order;

class CopyInventorySourceToOrder
{
    public function handle(Order $order): void
    {
        if ($order->inventory_source_id !== null) {
            return;
        }

        $cart = $order->cart;

        if (! $cart || ! $cart->inventory_source_id) {
            return;
        }

        $order->inventory_source_id = $cart->inventory_source_id;
        $order->saveQuietly();
    }
}
```

- [ ] **Step 4: Register the listener**

Update `packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`:

```php
<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\ManagerApp\Listeners\BroadcastOrderEvents;
use Webkul\ManagerApp\Listeners\CopyInventorySourceToOrder;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen('checkout.order.save.after', CopyInventorySourceToOrder::class);
        // BroadcastOrderEvents registered in Task 10
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact --filter="copies inventory_source_id" packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Listeners/CopyInventorySourceToOrder.php packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
git commit -m "feat(manager-app): copy inventory_source_id from cart to order"
```

---

## Task 6: Auth API

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Http/Controllers/AuthController.php`
- Modify: `packages/Webkul/ManagerApp/src/Routes/api.php`

- [ ] **Step 1: Add more auth tests**

Add to `packages/Webkul/ManagerApp/tests/Feature/AuthTest.php`:

```php
use Webkul\Inventory\Models\InventorySource;

it('returns token on successful login', function () {
    $source = InventorySource::factory()->create();
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['manager.app.access'],
    ]);
    $admin = Admin::factory()->create([
        'role_id' => $role->id,
        'password' => bcrypt('secret'),
    ]);
    $admin->inventorySources()->attach($source->id);

    $this->postJson('/api/manager/auth/login', [
        'email' => $admin->email,
        'password' => 'secret',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
});

it('returns 422 for invalid credentials', function () {
    $this->postJson('/api/manager/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong',
    ])->assertStatus(422);
});

it('returns current user on /me', function () {
    $source = InventorySource::factory()->create();
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['manager.app.access'],
    ]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $admin->inventorySources()->attach($source->id);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/auth/me')
        ->assertOk()
        ->assertJsonPath('user.id', $admin->id)
        ->assertJsonStructure(['user', 'inventory_sources']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/AuthTest.php
```

Expected: FAIL — routes 404.

- [ ] **Step 3: Create AuthController**

`packages/Webkul/ManagerApp/src/Http/Controllers/AuthController.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Webkul\User\Models\Admin;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('admin')->attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $token = $admin->createToken('manager-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
            'inventory_sources' => $admin->inventorySources()->pluck('name', 'id'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'user' => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
            'inventory_sources' => $admin->inventorySources()->pluck('name', 'id'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
```

- [ ] **Step 4: Register middleware alias and routes**

Update `packages/Webkul/ManagerApp/src/Providers/ManagerAppServiceProvider.php` — add middleware alias registration in `register()`:

```php
public function register(): void
{
    $this->app->register(EventServiceProvider::class);

    $this->app['router']->aliasMiddleware(
        'manager.authenticate',
        \Webkul\ManagerApp\Http\Middleware\ManagerAuthenticate::class
    );
}
```

Update `packages/Webkul/ManagerApp/src/Routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\ManagerApp\Http\Controllers\AuthController;
use Webkul\ManagerApp\Http\Controllers\OrderController;
use Webkul\ManagerApp\Http\Controllers\PushController;

Route::prefix('api/manager')->name('manager.api.')->group(function () {

    // Public: login
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected
    Route::middleware(['auth:sanctum', 'manager.authenticate'])->group(function () {
        Route::delete('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Orders — /statuses must be before /{id}
        Route::get('orders/statuses', [OrderController::class, 'statuses'])->name('orders.statuses');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.update_status');

        // Push
        Route::post('push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
        Route::delete('push/subscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    });
});
```

- [ ] **Step 5: Run auth tests**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/AuthTest.php
```

Expected: PASS on all auth tests (some middleware tests may still fail — that's fine).

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Http/Controllers/AuthController.php packages/Webkul/ManagerApp/src/Routes/api.php packages/Webkul/ManagerApp/src/Providers/ManagerAppServiceProvider.php packages/Webkul/ManagerApp/tests/Feature/AuthTest.php
git commit -m "feat(manager-app): auth API (login, logout, me)"
```

---

## Task 7: ManagerOrderService + API Resources

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Services/ManagerOrderService.php`
- Create: `packages/Webkul/ManagerApp/src/Http/Resources/OrderResource.php`
- Create: `packages/Webkul/ManagerApp/src/Http/Resources/OrderDetailResource.php`
- Create: `packages/Webkul/ManagerApp/src/Http/Requests/UpdateOrderStatusRequest.php`

- [ ] **Step 1: Create OrderResource (list)**

`packages/Webkul/ManagerApp/src/Http/Resources/OrderResource.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'increment_id'        => $this->increment_id,
            'status'              => $this->status,
            'status_label'        => $this->status_label,
            'created_at'          => $this->created_at->toIso8601String(),
            'customer_full_name'  => $this->customer_full_name,
            'customer_email'      => $this->customer_email,
            'grand_total'         => $this->grand_total,
            'order_currency_code' => $this->order_currency_code,
        ];
    }
}
```

- [ ] **Step 2: Create OrderDetailResource**

`packages/Webkul/ManagerApp/src/Http/Resources/OrderDetailResource.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $shippingAddress = $this->shipping_address;
        $billingAddress  = $this->billing_address;

        return [
            'id'                  => $this->id,
            'increment_id'        => $this->increment_id,
            'status'              => $this->status,
            'status_label'        => $this->status_label,
            'created_at'          => $this->created_at->toIso8601String(),
            'customer_full_name'  => $this->customer_full_name,
            'customer_email'      => $this->customer_email,
            'customer_phone'      => $shippingAddress?->phone,
            'grand_total'         => $this->grand_total,
            'sub_total'           => $this->sub_total,
            'shipping_amount'     => $this->shipping_amount,
            'order_currency_code' => $this->order_currency_code,
            'shipping_title'      => $this->shipping_title,
            'payment_method'      => $this->payment?->method,
            'shipping_address'    => $shippingAddress ? [
                'name'    => $shippingAddress->name,
                'address' => $shippingAddress->address1,
                'city'    => $shippingAddress->city,
                'phone'   => $shippingAddress->phone,
            ] : null,
            'billing_address'     => $billingAddress ? [
                'name'    => $billingAddress->name,
                'address' => $billingAddress->address1,
                'city'    => $billingAddress->city,
            ] : null,
            'items'               => $this->items->map(fn ($item) => [
                'name'     => $item->name,
                'sku'      => $item->sku,
                'qty'      => $item->qty_ordered,
                'price'    => $item->price,
                'total'    => $item->total,
            ]),
        ];
    }
}
```

- [ ] **Step 3: Create UpdateOrderStatusRequest**

`packages/Webkul/ManagerApp/src/Http/Requests/UpdateOrderStatusRequest.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Webkul\Sales\Models\Order;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // getStatusLabels() is added to Order model in Step 4 of this task
        $validStatuses = array_keys((new Order)->getStatusLabels());

        return [
            'status' => ['required', 'string', 'in:'.implode(',', $validStatuses)],
        ];
    }
}
```

- [ ] **Step 4: Add `getStatusLabels()` to Order model**

Edit `packages/Webkul/Sales/src/Models/Order.php` — add this public method after `getStatusLabelAttribute()`:

```php
/**
 * Get all status labels keyed by status code.
 */
public function getStatusLabels(): array
{
    return $this->statusLabel;
}
```

- [ ] **Step 5: Create ManagerOrderService**

`packages/Webkul/ManagerApp/src/Services/ManagerOrderService.php`:

```php
<?php

namespace Webkul\ManagerApp\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;

class ManagerOrderService
{
    /**
     * Return paginated orders scoped to the manager's inventory sources.
     */
    public function getOrders(Admin $admin, array $filters = []): LengthAwarePaginator
    {
        $sourceIds = $admin->getRestrictedInventorySourceIds();

        $query = Order::query()
            ->whereIn('inventory_source_id', $sourceIds)
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer'])) {
            $search = '%'.$filters['customer'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('customer_first_name', 'like', $search)
                  ->orWhere('customer_last_name', 'like', $search)
                  ->orWhere('customer_email', 'like', $search);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate(15);
    }

    /**
     * Find a single order belonging to the manager's inventory sources.
     */
    public function getOrder(Admin $admin, int $orderId): Order
    {
        $sourceIds = $admin->getRestrictedInventorySourceIds();

        return Order::query()
            ->whereIn('inventory_source_id', $sourceIds)
            ->with(['items', 'addresses', 'payment'])
            ->findOrFail($orderId);
    }

    /**
     * Update order status and fire the Bagisto event.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $order->status = $status;
        $order->save();

        Event::dispatch('sales.order.update-status.after', $order);

        return $order;
    }

    /**
     * Return all allowed order statuses.
     */
    public function getAllStatuses(): array
    {
        return (new Order)->getStatusLabels();
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Services/ packages/Webkul/ManagerApp/src/Http/Resources/ packages/Webkul/ManagerApp/src/Http/Requests/ packages/Webkul/Sales/src/Models/Order.php
git commit -m "feat(manager-app): OrderService, resources, status request"
```

---

## Task 8: OrderController + Tests

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Http/Controllers/OrderController.php`

- [ ] **Step 1: Write the failing tests**

Add to `packages/Webkul/ManagerApp/tests/Feature/OrderTest.php`:

```php
use Webkul\ManagerApp\Tests\ManagerAppTestCase;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function makeManager(): array
{
    $source = InventorySource::factory()->create();
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['manager.app.access'],
    ]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $admin->inventorySources()->attach($source->id);

    return [$admin, $source];
}

it('lists orders scoped to manager warehouse', function () {
    [$admin, $source] = makeManager();

    $myOrder    = Order::factory()->create(['inventory_source_id' => $source->id]);
    $otherOrder = Order::factory()->create(['inventory_source_id' => null]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/orders')
        ->assertOk()
        ->assertJsonFragment(['id' => $myOrder->id])
        ->assertJsonMissing(['id' => $otherOrder->id]);
});

it('filters orders by status', function () {
    [$admin, $source] = makeManager();

    $pending    = Order::factory()->create(['inventory_source_id' => $source->id, 'status' => 'pending']);
    $processing = Order::factory()->create(['inventory_source_id' => $source->id, 'status' => 'processing']);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/orders?status=pending')
        ->assertOk()
        ->assertJsonFragment(['id' => $pending->id])
        ->assertJsonMissing(['id' => $processing->id]);
});

it('returns 404 for order outside manager warehouse', function () {
    [$admin] = makeManager();

    $other = Order::factory()->create(['inventory_source_id' => null]);

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/manager/orders/{$other->id}")
        ->assertNotFound();
});

it('updates order status', function () {
    [$admin, $source] = makeManager();

    $order = Order::factory()->create([
        'inventory_source_id' => $source->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->patchJson("/api/manager/orders/{$order->id}/status", ['status' => 'processing'])
        ->assertOk()
        ->assertJsonPath('data.status', 'processing');

    expect($order->fresh()->status)->toBe('processing');
});

it('returns all available statuses', function () {
    [$admin] = makeManager();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/manager/orders/statuses')
        ->assertOk()
        ->assertJsonStructure(['data' => ['pending', 'processing']]);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: FAIL.

- [ ] **Step 3: Create OrderController**

`packages/Webkul/ManagerApp/src/Http/Controllers/OrderController.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\ManagerApp\Http\Requests\UpdateOrderStatusRequest;
use Webkul\ManagerApp\Http\Resources\OrderDetailResource;
use Webkul\ManagerApp\Http\Resources\OrderResource;
use Webkul\ManagerApp\Services\ManagerOrderService;

class OrderController extends Controller
{
    public function __construct(private readonly ManagerOrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrders(
            $request->user(),
            $request->only(['status', 'customer', 'date_from', 'date_to'])
        );

        return response()->json([
            'data'  => OrderResource::collection($orders),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($request->user(), $id);

        return response()->json(['data' => new OrderDetailResource($order)]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrder($request->user(), $id);

        $updated = $this->orderService->updateStatus($order, $request->input('status'));

        return response()->json(['data' => new OrderResource($updated)]);
    }

    public function statuses(): JsonResponse
    {
        return response()->json(['data' => $this->orderService->getAllStatuses()]);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Http/Controllers/OrderController.php packages/Webkul/ManagerApp/tests/Feature/OrderTest.php
git commit -m "feat(manager-app): orders API (index, show, status update)"
```

---

## Task 9: Push API

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Http/Controllers/PushController.php`
- Modify: `packages/Webkul/PushNotification/src/Services/WebPushService.php`

- [ ] **Step 1: Write failing push tests**

`packages/Webkul/ManagerApp/tests/Feature/PushTest.php`:

```php
<?php

use Webkul\ManagerApp\Tests\ManagerAppTestCase;
use Webkul\Inventory\Models\InventorySource;
use Webkul\PushNotification\Models\PushSubscription;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(ManagerAppTestCase::class);

function makeManagerForPush(): Admin
{
    $source = InventorySource::factory()->create();
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['manager.app.access'],
    ]);
    $admin = Admin::factory()->create(['role_id' => $role->id]);
    $admin->inventorySources()->attach($source->id);

    return $admin;
}

it('subscribes manager to push notifications', function () {
    $admin = makeManagerForPush();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/manager/push/subscribe', [
            'endpoint'   => 'https://push.example.com/sub/abc',
            'public_key' => 'test-public-key',
            'auth_token' => 'test-auth-token',
        ])
        ->assertOk();

    expect(
        PushSubscription::where('subscribable_type', 'admin')
            ->where('subscribable_id', $admin->id)
            ->where('endpoint', 'https://push.example.com/sub/abc')
            ->exists()
    )->toBeTrue();
});

it('deletes push subscription', function () {
    $admin = makeManagerForPush();

    PushSubscription::create([
        'subscribable_type' => 'admin',
        'subscribable_id'   => $admin->id,
        'endpoint'          => 'https://push.example.com/sub/abc',
        'public_key'        => 'key',
        'auth_token'        => 'token',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->deleteJson('/api/manager/push/subscribe', [
            'endpoint' => 'https://push.example.com/sub/abc',
        ])
        ->assertOk();

    expect(
        PushSubscription::where('endpoint', 'https://push.example.com/sub/abc')->exists()
    )->toBeFalse();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/PushTest.php
```

Expected: FAIL.

- [ ] **Step 3: Create PushController**

`packages/Webkul/ManagerApp/src/Http/Controllers/PushController.php`:

```php
<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\PushNotification\Repositories\PushSubscriptionRepository;

class PushController extends Controller
{
    public function __construct(
        private readonly PushSubscriptionRepository $subscriptionRepository
    ) {}

    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => ['required', 'string', 'url'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
        ]);

        $admin = $request->user();

        $this->subscriptionRepository->upsertForSubscribable(
            'admin',
            $admin->id,
            $request->input('endpoint'),
            $request->input('public_key'),
            $request->input('auth_token')
        );

        return response()->json(['message' => 'Subscribed.']);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $this->subscriptionRepository->deleteByEndpoint($request->input('endpoint'));

        return response()->json(['message' => 'Unsubscribed.']);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact packages/Webkul/ManagerApp/tests/Feature/PushTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Http/Controllers/PushController.php packages/Webkul/ManagerApp/tests/Feature/PushTest.php
git commit -m "feat(manager-app): push subscription API"
```

---

## Task 10: Broadcast Events + BroadcastOrderEvents Listener

**Files:**
- Create: `packages/Webkul/ManagerApp/src/Events/ManagerOrderCreated.php`
- Create: `packages/Webkul/ManagerApp/src/Events/ManagerOrderStatusUpdated.php`
- Create: `packages/Webkul/ManagerApp/src/Listeners/BroadcastOrderEvents.php`
- Modify: `packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`

- [ ] **Step 1: Create ManagerOrderCreated event**

`packages/Webkul/ManagerApp/src/Events/ManagerOrderCreated.php`:

```php
<?php

namespace Webkul\ManagerApp\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Webkul\Sales\Models\Order;

class ManagerOrderCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly int $adminId
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('manager.'.$this->adminId);
    }

    public function broadcastAs(): string
    {
        return 'ManagerOrderCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                  => $this->order->id,
            'increment_id'        => $this->order->increment_id,
            'status'              => $this->order->status,
            'status_label'        => $this->order->status_label,
            'created_at'          => $this->order->created_at->toIso8601String(),
            'customer_full_name'  => $this->order->customer_full_name,
            'customer_email'      => $this->order->customer_email,
            'grand_total'         => $this->order->grand_total,
            'order_currency_code' => $this->order->order_currency_code,
        ];
    }
}
```

- [ ] **Step 2: Create ManagerOrderStatusUpdated event**

`packages/Webkul/ManagerApp/src/Events/ManagerOrderStatusUpdated.php`:

```php
<?php

namespace Webkul\ManagerApp\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Webkul\Sales\Models\Order;

class ManagerOrderStatusUpdated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly int $adminId
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('manager.'.$this->adminId);
    }

    public function broadcastAs(): string
    {
        return 'ManagerOrderStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->order->id,
            'status'       => $this->order->status,
            'status_label' => $this->order->status_label,
        ];
    }
}
```

- [ ] **Step 3: Create BroadcastOrderEvents listener**

`packages/Webkul/ManagerApp/src/Listeners/BroadcastOrderEvents.php`:

```php
<?php

namespace Webkul\ManagerApp\Listeners;

use Webkul\ManagerApp\Events\ManagerOrderCreated;
use Webkul\ManagerApp\Events\ManagerOrderStatusUpdated;
use Webkul\PushNotification\Services\WebPushService;
use Webkul\Sales\Models\Order;
use Webkul\User\Models\Admin;

class BroadcastOrderEvents
{
    public function __construct(private readonly WebPushService $webPushService) {}

    public function handleNewOrder(Order $order): void
    {
        if (! $order->inventory_source_id) {
            return;
        }

        $managers = Admin::whereHas('inventorySources', function ($q) use ($order) {
            $q->where('inventory_sources.id', $order->inventory_source_id);
        })->get();

        foreach ($managers as $manager) {
            broadcast(new ManagerOrderCreated($order, $manager->id));
        }

        $this->webPushService->sendToManagersForInventorySource(
            $order->inventory_source_id,
            'New Order #'.$order->increment_id,
            $order->customer_full_name.' · '.$order->grand_total.' '.$order->order_currency_code,
        );
    }

    public function handleStatusUpdated(Order $order): void
    {
        if (! $order->inventory_source_id) {
            return;
        }

        $managers = Admin::whereHas('inventorySources', function ($q) use ($order) {
            $q->where('inventory_sources.id', $order->inventory_source_id);
        })->get();

        foreach ($managers as $manager) {
            broadcast(new ManagerOrderStatusUpdated($order, $manager->id));
        }

        $this->webPushService->sendToManagersForInventorySource(
            $order->inventory_source_id,
            'Order #'.$order->increment_id.' updated',
            'Status: '.$order->status_label,
        );
    }
}
```

- [ ] **Step 4: Update EventServiceProvider to register listener**

`packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php`:

```php
<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\ManagerApp\Listeners\BroadcastOrderEvents;
use Webkul\ManagerApp\Listeners\CopyInventorySourceToOrder;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen('checkout.order.save.after', CopyInventorySourceToOrder::class);

        Event::listen('checkout.order.save.after', function ($order) {
            app(BroadcastOrderEvents::class)->handleNewOrder($order);
        });

        Event::listen('sales.order.update-status.after', function ($order) {
            app(BroadcastOrderEvents::class)->handleStatusUpdated($order);
        });

        Broadcast::channel('manager.{adminId}', function ($user, $adminId) {
            return (int) $user->id === (int) $adminId;
        });
    }
}
```

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Events/ packages/Webkul/ManagerApp/src/Listeners/BroadcastOrderEvents.php packages/Webkul/ManagerApp/src/Providers/EventServiceProvider.php
git commit -m "feat(manager-app): broadcast events for new orders and status changes"
```

---

## Task 11: WebPushService Extension

**Files:**
- Modify: `packages/Webkul/PushNotification/src/Services/WebPushService.php`

- [ ] **Step 1: Add `sendToManagersForInventorySource()` to WebPushService**

Edit `packages/Webkul/PushNotification/src/Services/WebPushService.php` — add after the `sendToCustomer()` method:

```php
/**
 * Send a push notification to all managers of a specific inventory source.
 */
public function sendToManagersForInventorySource(int $inventorySourceId, string $title, string $body, ?string $url = null): void
{
    $managerIds = \Webkul\User\Models\Admin::whereHas('inventorySources', function ($q) use ($inventorySourceId) {
        $q->where('inventory_sources.id', $inventorySourceId);
    })->pluck('id');

    if ($managerIds->isEmpty()) {
        return;
    }

    $subscriptions = $this->pushSubscriptionRepository->model
        ->where('subscribable_type', 'admin')
        ->whereIn('subscribable_id', $managerIds)
        ->get();

    $this->sendToSubscriptions($subscriptions, $title, $body, $url);
}
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/PushNotification/src/Services/WebPushService.php
git commit -m "feat(manager-app): add sendToManagersForInventorySource to WebPushService"
```

---

## Task 12: Run Full Backend Test Suite

- [ ] **Step 1: Run all manager app tests**

```bash
php artisan test --compact --testsuite="ManagerApp Feature Test"
```

Expected: all tests PASS.

- [ ] **Step 2: Run existing admin tests to check no regressions**

```bash
php artisan test --compact --testsuite="Admin Feature Test"
```

Expected: all tests PASS (or same failures as before this work).

---

## Task 13: Vue 3 Project Setup

**Files:**
- Create: `packages/Webkul/ManagerApp/package.json`
- Create: `packages/Webkul/ManagerApp/vite.config.js`

- [ ] **Step 1: Create `package.json`**

`packages/Webkul/ManagerApp/package.json`:

```json
{
    "name": "@webkul/manager-app",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "dependencies": {
        "@vueuse/core": "^10.0.0",
        "axios": "^1.7.0",
        "laravel-echo": "^1.16.0",
        "pinia": "^2.2.0",
        "pusher-js": "^8.4.0",
        "vue": "^3.4.0",
        "vue-router": "^4.3.0"
    },
    "devDependencies": {
        "@vitejs/plugin-vue": "^5.1.0",
        "vite": "^5.4.0",
        "vite-plugin-pwa": "^0.20.0"
    }
}
```

- [ ] **Step 2: Create `vite.config.js`**

`packages/Webkul/ManagerApp/vite.config.js`:

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    base: '/manager/',

    plugins: [
        vue(),
        VitePWA({
            base: '/manager/',
            scope: '/manager/',
            registerType: 'autoUpdate',
            manifest: {
                name: 'Manager App',
                short_name: 'Orders',
                description: 'Warehouse order management',
                start_url: '/manager/',
                scope: '/manager/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#4f46e5',
                icons: [
                    { src: '/manager/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/manager/icon-512.png', sizes: '512x512', type: 'image/png' },
                ],
            },
            workbox: {
                navigateFallback: '/manager/',
                navigateFallbackDenylist: [/^\/api/],
                runtimeCaching: [
                    {
                        urlPattern: /^\/api\/manager\//,
                        handler: 'NetworkFirst',
                        options: { cacheName: 'manager-api-cache' },
                    },
                ],
            },
        }),
    ],

    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },

    build: {
        outDir: fileURLToPath(new URL('../../public/manager', import.meta.url)),
        emptyOutDir: true,
    },
})
```

- [ ] **Step 3: Install dependencies**

```bash
cd packages/Webkul/ManagerApp
npm install
```

Expected: `node_modules/` created, no errors.

- [ ] **Step 4: Commit**

```bash
cd ../../../   # back to project root
git add packages/Webkul/ManagerApp/package.json packages/Webkul/ManagerApp/vite.config.js
git commit -m "feat(manager-app): Vue 3 + Vite + PWA setup"
```

---

## Task 14: API Client + Pinia Stores

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/api.js`
- Create: `packages/Webkul/ManagerApp/resources/js/stores/auth.js`
- Create: `packages/Webkul/ManagerApp/resources/js/stores/orders.js`

- [ ] **Step 1: Create API client**

`packages/Webkul/ManagerApp/resources/js/api.js`:

```js
import axios from 'axios'

const api = axios.create({ baseURL: '/api/manager' })

api.interceptors.request.use(config => {
    const token = localStorage.getItem('manager_token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

api.interceptors.response.use(
    r => r,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('manager_token')
            window.location.href = '/manager/login'
        }
        return Promise.reject(error)
    }
)

export default api
```

- [ ] **Step 2: Create auth store**

`packages/Webkul/ManagerApp/resources/js/stores/auth.js`:

```js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('manager_token') ?? null)
    const user = ref(null)
    const inventorySources = ref({})

    const isLoggedIn = () => !!token.value

    async function login(email, password) {
        const { data } = await api.post('/auth/login', { email, password })
        token.value = data.token
        user.value = data.user
        inventorySources.value = data.inventory_sources
        localStorage.setItem('manager_token', data.token)
    }

    async function fetchMe() {
        const { data } = await api.get('/auth/me')
        user.value = data.user
        inventorySources.value = data.inventory_sources
    }

    async function logout() {
        await api.delete('/auth/logout').catch(() => {})
        token.value = null
        user.value = null
        inventorySources.value = {}
        localStorage.removeItem('manager_token')
    }

    return { token, user, inventorySources, isLoggedIn, login, fetchMe, logout }
})
```

- [ ] **Step 3: Create orders store**

`packages/Webkul/ManagerApp/resources/js/stores/orders.js`:

```js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useOrdersStore = defineStore('orders', () => {
    const orders = ref([])
    const statuses = ref({})
    const filters = ref({ status: '', customer: '', date_from: '', date_to: '' })
    const meta = ref({ current_page: 1, last_page: 1, total: 0 })
    const loading = ref(false)

    async function fetchStatuses() {
        const { data } = await api.get('/orders/statuses')
        statuses.value = data.data
    }

    async function fetch(page = 1) {
        loading.value = true
        try {
            const params = { page, ...filters.value }
            const { data } = await api.get('/orders', { params })
            if (page === 1) {
                orders.value = data.data
            } else {
                orders.value.push(...data.data)
            }
            meta.value = data.meta
        } finally {
            loading.value = false
        }
    }

    async function fetchMore() {
        if (meta.value.current_page < meta.value.last_page) {
            await fetch(meta.value.current_page + 1)
        }
    }

    function prependOrder(order) {
        orders.value.unshift(order)
    }

    function updateOrderInPlace(id, patch) {
        const idx = orders.value.findIndex(o => o.id === id)
        if (idx !== -1) Object.assign(orders.value[idx], patch)
    }

    async function updateStatus(orderId, status) {
        const { data } = await api.patch(`/orders/${orderId}/status`, { status })
        updateOrderInPlace(orderId, data.data)
        return data.data
    }

    return { orders, statuses, filters, meta, loading, fetchStatuses, fetch, fetchMore, prependOrder, updateOrderInPlace, updateStatus }
})
```

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/api.js packages/Webkul/ManagerApp/resources/js/stores/
git commit -m "feat(manager-app): API client and Pinia stores"
```

---

## Task 15: Vue Router + App Bootstrap

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/router/index.js`
- Create: `packages/Webkul/ManagerApp/resources/js/App.vue`
- Create: `packages/Webkul/ManagerApp/resources/js/main.js`

- [ ] **Step 1: Create router**

`packages/Webkul/ManagerApp/resources/js/router/index.js`:

```js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    {
        path: '/manager/login',
        name: 'login',
        component: () => import('@/pages/LoginPage.vue'),
        meta: { public: true },
    },
    {
        path: '/manager',
        name: 'orders',
        component: () => import('@/pages/OrdersPage.vue'),
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/manager',
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (!to.meta.public && !auth.isLoggedIn()) {
        return { name: 'login' }
    }

    if (to.meta.public && auth.isLoggedIn()) {
        return { name: 'orders' }
    }
})

export default router
```

- [ ] **Step 2: Create App.vue**

`packages/Webkul/ManagerApp/resources/js/App.vue`:

```vue
<template>
    <RouterView />
</template>

<script setup>
import { RouterView } from 'vue-router'
</script>

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #334155; }
</style>
```

- [ ] **Step 3: Create main.js**

`packages/Webkul/ManagerApp/resources/js/main.js`:

```js
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
```

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/
git commit -m "feat(manager-app): Vue Router and app bootstrap"
```

---

## Task 16: LoginPage

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/pages/LoginPage.vue`

- [ ] **Step 1: Create LoginPage**

`packages/Webkul/ManagerApp/resources/js/pages/LoginPage.vue`:

```vue
<template>
    <div class="login-wrap">
        <div class="login-card">
            <h1 class="login-title">Manager App</h1>
            <p class="login-sub">Sign in to your warehouse account</p>

            <form @submit.prevent="submit">
                <div class="field">
                    <label>Email</label>
                    <input v-model="form.email" type="email" autocomplete="email" required />
                </div>
                <div class="field">
                    <label>Password</label>
                    <input v-model="form.password" type="password" autocomplete="current-password" required />
                </div>

                <p v-if="error" class="error">{{ error }}</p>

                <button type="submit" :disabled="loading" class="btn-primary">
                    {{ loading ? 'Signing in…' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function submit() {
    error.value = ''
    loading.value = true
    try {
        await auth.login(form.value.email, form.value.password)
        router.push({ name: 'orders' })
    } catch (e) {
        error.value = e.response?.data?.errors?.email?.[0] ?? 'Invalid credentials.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.login-wrap {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
}
.login-card {
    background: white;
    border-radius: 12px;
    padding: 2.5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
}
.login-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; }
.login-sub { color: #94a3b8; margin: .25rem 0 1.5rem; font-size: .875rem; }
.field { margin-bottom: 1rem; }
.field label { display: block; font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
.field input {
    width: 100%;
    padding: .6rem .85rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: .95rem;
    transition: border-color .15s;
}
.field input:focus { outline: none; border-color: #4f46e5; }
.error { color: #ef4444; font-size: .85rem; margin-bottom: .75rem; }
.btn-primary {
    width: 100%;
    padding: .7rem;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.btn-primary:hover:not(:disabled) { background: #4338ca; }
.btn-primary:disabled { opacity: .6; cursor: not-allowed; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/pages/LoginPage.vue
git commit -m "feat(manager-app): login page"
```

---

## Task 17: Filter + Status Components

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/components/StatusSelector.vue`
- Create: `packages/Webkul/ManagerApp/resources/js/components/OrderFilters.vue`

- [ ] **Step 1: Create StatusSelector**

`packages/Webkul/ManagerApp/resources/js/components/StatusSelector.vue`:

```vue
<template>
    <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" class="status-select">
        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
    </select>
</template>

<script setup>
defineProps({ modelValue: String, statuses: Object })
defineEmits(['update:modelValue'])
</script>

<style scoped>
.status-select {
    padding: .4rem .7rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: .875rem;
    background: white;
    cursor: pointer;
}
.status-select:focus { outline: none; border-color: #4f46e5; }
</style>
```

- [ ] **Step 2: Create OrderFilters**

`packages/Webkul/ManagerApp/resources/js/components/OrderFilters.vue`:

```vue
<template>
    <div class="filters">
        <input
            v-model="local.customer"
            type="text"
            placeholder="Customer name or email"
            class="filter-input"
            @input="emit"
        />
        <input v-model="local.date_from" type="date" class="filter-input" @change="emit" />
        <input v-model="local.date_to" type="date" class="filter-input" @change="emit" />
        <select v-model="local.status" class="filter-input" @change="emit">
            <option value="">All statuses</option>
            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
        </select>
    </div>
</template>

<script setup>
import { reactive } from 'vue'

const props = defineProps({ statuses: Object })
const emits = defineEmits(['change'])

const local = reactive({ customer: '', date_from: '', date_to: '', status: '' })

function emit() {
    emits('change', { ...local })
}
</script>

<style scoped>
.filters {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .75rem 1rem;
    background: white;
    border-bottom: 1px solid #f1f5f9;
}
.filter-input {
    flex: 1 1 160px;
    padding: .45rem .75rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: .875rem;
}
.filter-input:focus { outline: none; border-color: #4f46e5; }
</style>
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/components/
git commit -m "feat(manager-app): OrderFilters and StatusSelector components"
```

---

## Task 18: OrderCard Component

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/components/OrderCard.vue`

- [ ] **Step 1: Create OrderCard**

`packages/Webkul/ManagerApp/resources/js/components/OrderCard.vue`:

```vue
<template>
    <div class="card" :class="{ expanded }">
        <!-- Collapsed header -->
        <div class="card-header" @click="toggle">
            <span class="order-id">#{{ order.increment_id }}</span>
            <span class="order-date">{{ formatDate(order.created_at) }}</span>
            <span class="order-customer">{{ order.customer_full_name }}</span>
            <span class="status-badge" :class="statusClass(order.status)">{{ order.status_label }}</span>
            <span class="chevron">{{ expanded ? '▲' : '▼' }}</span>
        </div>

        <!-- Expanded detail -->
        <div v-if="expanded" class="card-body">
            <div v-if="loading" class="loading">Loading…</div>
            <template v-else-if="detail">
                <div class="detail-row">
                    <strong>Customer</strong>
                    <span>{{ detail.customer_full_name }} · {{ detail.customer_email }}</span>
                    <span v-if="detail.customer_phone">· {{ detail.customer_phone }}</span>
                </div>
                <div v-if="detail.shipping_address" class="detail-row">
                    <strong>Shipping</strong>
                    <span>{{ detail.shipping_address.address }}, {{ detail.shipping_address.city }}</span>
                </div>
                <div class="items-table">
                    <div v-for="item in detail.items" :key="item.sku" class="item-row">
                        <span>{{ item.qty }}× {{ item.name }}</span>
                        <span>{{ formatMoney(item.total, detail.order_currency_code) }}</span>
                    </div>
                    <div class="item-row subtotal">
                        <span>Subtotal</span><span>{{ formatMoney(detail.sub_total, detail.order_currency_code) }}</span>
                    </div>
                    <div class="item-row subtotal">
                        <span>Shipping</span><span>{{ formatMoney(detail.shipping_amount, detail.order_currency_code) }}</span>
                    </div>
                    <div class="item-row total">
                        <span>Total</span><span>{{ formatMoney(detail.grand_total, detail.order_currency_code) }}</span>
                    </div>
                </div>
                <div class="detail-row">
                    <strong>Payment</strong> <span>{{ detail.payment_method }}</span>
                </div>
                <div class="status-row">
                    <StatusSelector v-model="selectedStatus" :statuses="ordersStore.statuses" />
                    <button class="btn-save" :disabled="saving || selectedStatus === order.status" @click="saveStatus">
                        {{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '@/api'
import { useOrdersStore } from '@/stores/orders'
import StatusSelector from './StatusSelector.vue'

const props = defineProps({ order: Object })
const ordersStore = useOrdersStore()

const expanded = ref(false)
const detail = ref(null)
const loading = ref(false)
const saving = ref(false)
const selectedStatus = ref(props.order.status)

watch(() => props.order.status, v => { selectedStatus.value = v })

async function toggle() {
    expanded.value = !expanded.value
    if (expanded.value && !detail.value) {
        loading.value = true
        try {
            const { data } = await api.get(`/orders/${props.order.id}`)
            detail.value = data.data
        } finally {
            loading.value = false
        }
    }
}

async function saveStatus() {
    saving.value = true
    try {
        await ordersStore.updateStatus(props.order.id, selectedStatus.value)
    } finally {
        saving.value = false
    }
}

function formatDate(iso) {
    return new Date(iso).toLocaleString('ru-RU', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function formatMoney(amount, currency) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: currency ?? 'RUB' }).format(amount)
}

function statusClass(status) {
    return {
        pending: 'badge-amber',
        pending_payment: 'badge-amber',
        awaiting_confirmation: 'badge-amber',
        processing: 'badge-blue',
        completed: 'badge-green',
        canceled: 'badge-red',
        closed: 'badge-gray',
        fraud: 'badge-red',
    }[status] ?? 'badge-gray'
}
</script>

<style scoped>
.card { background: white; border-radius: 12px; margin-bottom: .5rem; box-shadow: 0 1px 6px rgba(0,0,0,.06); overflow: hidden; }
.card-header { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; cursor: pointer; user-select: none; }
.card-header:hover { background: #f8fafc; }
.order-id { font-weight: 700; color: #1e293b; min-width: 80px; }
.order-date { color: #64748b; font-size: .85rem; min-width: 130px; }
.order-customer { flex: 1; font-weight: 500; }
.chevron { color: #94a3b8; }
.status-badge { padding: .2rem .6rem; border-radius: 99px; font-size: .75rem; font-weight: 600; }
.badge-amber { background: #fef3c7; color: #92400e; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-red { background: #fee2e2; color: #991b1b; }
.badge-gray { background: #f1f5f9; color: #475569; }
.card-body { padding: 1rem; border-top: 1px solid #f1f5f9; }
.loading { color: #94a3b8; text-align: center; padding: 1rem; }
.detail-row { display: flex; gap: .5rem; margin-bottom: .6rem; font-size: .875rem; }
.detail-row strong { min-width: 90px; color: #475569; }
.items-table { margin: .75rem 0; border-top: 1px solid #f1f5f9; }
.item-row { display: flex; justify-content: space-between; padding: .35rem 0; font-size: .875rem; border-bottom: 1px solid #f8fafc; }
.subtotal { color: #64748b; }
.total { font-weight: 700; color: #1e293b; }
.status-row { display: flex; gap: .75rem; align-items: center; margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #f1f5f9; }
.btn-save { padding: .45rem 1.2rem; background: #4f46e5; color: white; border: none; border-radius: 8px; font-size: .875rem; font-weight: 600; cursor: pointer; }
.btn-save:hover:not(:disabled) { background: #4338ca; }
.btn-save:disabled { opacity: .5; cursor: not-allowed; }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/components/OrderCard.vue
git commit -m "feat(manager-app): OrderCard component with collapse and status update"
```

---

## Task 19: OrdersPage

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/pages/OrdersPage.vue`

- [ ] **Step 1: Create OrdersPage**

`packages/Webkul/ManagerApp/resources/js/pages/OrdersPage.vue`:

```vue
<template>
    <div class="page">
        <!-- Top bar -->
        <header class="topbar">
            <span class="app-name">Manager App</span>
            <span class="manager-name">{{ auth.user?.name }}</span>
            <button class="btn-logout" @click="handleLogout">Logout</button>
        </header>

        <!-- Filters -->
        <OrderFilters :statuses="orders.statuses" @change="onFilterChange" />

        <!-- Order list -->
        <main class="content">
            <div v-if="orders.loading && orders.orders.length === 0" class="empty">Loading orders…</div>
            <div v-else-if="orders.orders.length === 0" class="empty">No orders found.</div>

            <template v-else>
                <OrderCard v-for="order in orders.orders" :key="order.id" :order="order" />

                <button
                    v-if="orders.meta.current_page < orders.meta.last_page"
                    class="btn-more"
                    :disabled="orders.loading"
                    @click="orders.fetchMore()"
                >
                    {{ orders.loading ? 'Loading…' : 'Load more' }}
                </button>
            </template>
        </main>

        <!-- Push notification button -->
        <PushPrompt />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, defineAsyncComponent } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useOrdersStore } from '@/stores/orders'
import { useEcho } from '@/composables/useEcho'
import OrderCard from '@/components/OrderCard.vue'
import OrderFilters from '@/components/OrderFilters.vue'

const PushPrompt = defineAsyncComponent(() => import('@/components/PushPrompt.vue'))

const auth = useAuthStore()
const orders = useOrdersStore()
const router = useRouter()
const { subscribe, unsubscribe } = useEcho()

onMounted(async () => {
    await auth.fetchMe()
    await orders.fetchStatuses()
    await orders.fetch()
    subscribe(auth.user.id)
})

onUnmounted(() => {
    unsubscribe()
})

function onFilterChange(newFilters) {
    Object.assign(orders.filters, newFilters)
    orders.fetch()
}

async function handleLogout() {
    unsubscribe()
    await auth.logout()
    router.push({ name: 'login' })
}
</script>

<style scoped>
.page { max-width: 720px; margin: 0 auto; min-height: 100vh; display: flex; flex-direction: column; }
.topbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    background: white;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}
.app-name { font-weight: 700; color: #4f46e5; }
.manager-name { flex: 1; color: #64748b; font-size: .875rem; }
.btn-logout { padding: .35rem .85rem; border: 1.5px solid #e2e8f0; border-radius: 8px; background: white; color: #475569; cursor: pointer; font-size: .85rem; }
.btn-logout:hover { border-color: #ef4444; color: #ef4444; }
.content { flex: 1; padding: .75rem; }
.empty { text-align: center; color: #94a3b8; padding: 3rem; }
.btn-more { width: 100%; padding: .7rem; background: white; border: 1.5px solid #e2e8f0; border-radius: 8px; color: #475569; cursor: pointer; margin-top: .5rem; }
.btn-more:hover:not(:disabled) { border-color: #4f46e5; color: #4f46e5; }
</style>
```

- [ ] **Step 2: Create PushPrompt component**

`packages/Webkul/ManagerApp/resources/js/components/PushPrompt.vue`:

```vue
<template>
    <div v-if="showPrompt" class="push-banner">
        <span>Enable push notifications for new orders?</span>
        <button @click="enable" class="btn-enable">Enable</button>
        <button @click="dismiss" class="btn-dismiss">Later</button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePush } from '@/composables/usePush'

const { subscribe: subscribePush, isSupported } = usePush()
const showPrompt = ref(false)

onMounted(() => {
    if (isSupported() && Notification.permission === 'default' && !localStorage.getItem('push_dismissed')) {
        showPrompt.value = true
    }
})

async function enable() {
    showPrompt.value = false
    await subscribePush()
}

function dismiss() {
    showPrompt.value = false
    localStorage.setItem('push_dismissed', '1')
}
</script>

<style scoped>
.push-banner {
    position: fixed;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: white;
    padding: .75rem 1.25rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: .75rem;
    font-size: .875rem;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
    z-index: 100;
}
.btn-enable { background: #4f46e5; color: white; border: none; padding: .35rem .85rem; border-radius: 8px; cursor: pointer; }
.btn-dismiss { background: transparent; color: #94a3b8; border: none; cursor: pointer; }
</style>
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/pages/OrdersPage.vue packages/Webkul/ManagerApp/resources/js/components/PushPrompt.vue
git commit -m "feat(manager-app): OrdersPage with filters and live updates"
```

---

## Task 20: useEcho + usePush Composables

**Files:**
- Create: `packages/Webkul/ManagerApp/resources/js/composables/useEcho.js`
- Create: `packages/Webkul/ManagerApp/resources/js/composables/usePush.js`

- [ ] **Step 1: Create useEcho**

`packages/Webkul/ManagerApp/resources/js/composables/useEcho.js`:

```js
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { useOrdersStore } from '@/stores/orders'

let echo = null
let channel = null

export function useEcho() {
    function getEcho() {
        if (echo) return echo

        const token = localStorage.getItem('manager_token')

        window.Pusher = Pusher
        echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: { Authorization: `Bearer ${token}` },
            },
        })

        return echo
    }

    function subscribe(adminId) {
        const orders = useOrdersStore()
        const echoInstance = getEcho()

        channel = echoInstance.private(`manager.${adminId}`)

        channel.listen('.ManagerOrderCreated', (event) => {
            orders.prependOrder(event)
        })

        channel.listen('.ManagerOrderStatusUpdated', (event) => {
            orders.updateOrderInPlace(event.id, {
                status: event.status,
                status_label: event.status_label,
            })
        })
    }

    function unsubscribe() {
        if (echo && channel) {
            echo.leaveAllChannels()
            channel = null
        }
    }

    return { subscribe, unsubscribe }
}
```

- [ ] **Step 2: Create usePush**

`packages/Webkul/ManagerApp/resources/js/composables/usePush.js`:

```js
import api from '@/api'

const VAPID_PUBLIC_KEY_ENDPOINT = '/api/manager/push/vapid-key'

export function usePush() {
    function isSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window
    }

    async function getVapidKey() {
        const { data } = await api.get('/push/vapid-key')
        return data.public_key
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
        const rawData = window.atob(base64)
        return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)))
    }

    async function subscribe() {
        if (!isSupported()) return

        const permission = await Notification.requestPermission()
        if (permission !== 'granted') return

        const registration = await navigator.serviceWorker.ready
        const vapidKey = await getVapidKey()

        const pushSubscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        })

        const subJson = pushSubscription.toJSON()

        await api.post('/push/subscribe', {
            endpoint:   pushSubscription.endpoint,
            public_key: subJson.keys?.p256dh,
            auth_token: subJson.keys?.auth,
        })
    }

    return { isSupported, subscribe }
}
```

- [ ] **Step 3: Add VAPID key endpoint to AuthController**

Add this method to `packages/Webkul/ManagerApp/src/Http/Controllers/AuthController.php`:

```php
public function vapidKey(): JsonResponse
{
    $vapid = app(\Webkul\PushNotification\Repositories\PushVapidSettingRepository::class)->getCurrent();

    return response()->json(['public_key' => $vapid?->public_key]);
}
```

Add to `packages/Webkul/ManagerApp/src/Routes/api.php` inside the protected group:

```php
Route::get('push/vapid-key', [AuthController::class, 'vapidKey'])->name('push.vapid_key');
```

- [ ] **Step 4: Add Reverb env vars to vite.config.js environment**

No changes needed — `import.meta.env.VITE_*` reads from `.env` automatically in Vite. Add to `.env`:

```
VITE_REVERB_APP_KEY=manager-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/ManagerApp/resources/js/composables/ packages/Webkul/ManagerApp/src/Http/Controllers/AuthController.php packages/Webkul/ManagerApp/src/Routes/api.php
git commit -m "feat(manager-app): useEcho and usePush composables"
```

---

## Task 21: Blade Shell + Web Routes

**Files:**
- Modify: `packages/Webkul/ManagerApp/src/Resources/views/app.blade.php`
- Modify: `packages/Webkul/ManagerApp/src/Routes/web.php`

- [ ] **Step 1: Update Blade shell**

`packages/Webkul/ManagerApp/src/Resources/views/app.blade.php`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manager App</title>
    <link rel="manifest" href="/manager/manifest.webmanifest" />
    <meta name="theme-color" content="#4f46e5" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script type="module" src="/manager/assets/main.js"></script>
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

> **Note:** After `npm run build`, Vite generates hashed filenames. Replace `assets/main.js` with the actual path from `public/manager/.vite/manifest.json`. Alternatively, use a Blade helper that reads the manifest:

Replace the `<script>` tag with a PHP snippet:

```php
<?php
$manifest = json_decode(file_get_contents(public_path('manager/.vite/manifest.json')), true);
$entry = $manifest['resources/js/main.js'] ?? null;
?>
@if($entry)
    @foreach($entry['css'] ?? [] as $css)
        <link rel="stylesheet" href="/manager/{{ $css }}" />
    @endforeach
    <script type="module" src="/manager/{{ $entry['file'] }}"></script>
@endif
```

Full final file:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manager App</title>
    <link rel="manifest" href="/manager/manifest.webmanifest" />
    <meta name="theme-color" content="#4f46e5" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @php
        $manifest = json_decode(file_get_contents(public_path('manager/.vite/manifest.json')), true);
        $entry = $manifest['resources/js/main.js'] ?? null;
    @endphp
    @if($entry)
        @foreach($entry['css'] ?? [] as $css)
            <link rel="stylesheet" href="/manager/{{ $css }}" />
        @endforeach
        <script type="module" src="/manager/{{ $entry['file'] }}"></script>
    @endif
</head>
<body>
    <div id="app"></div>
</body>
</html>
```

- [ ] **Step 2: Update web routes**

`packages/Webkul/ManagerApp/src/Routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/manager/{any?}', function () {
    return view('manager-app::app');
})->where('any', '.*')->name('manager.app');
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/ManagerApp/src/Resources/views/app.blade.php packages/Webkul/ManagerApp/src/Routes/web.php
git commit -m "feat(manager-app): Blade SPA shell and web route"
```

---

## Task 22: Build Frontend + Add PWA Icons

- [ ] **Step 1: Add placeholder PWA icons**

Create two placeholder PNG files in `public/manager/`:
- `public/manager/icon-192.png` (192×192)
- `public/manager/icon-512.png` (512×512)

You can generate these with any image editor, or use a placeholder service during development. They must exist for the PWA manifest to validate.

- [ ] **Step 2: Build frontend**

```bash
cd packages/Webkul/ManagerApp
npm run build
```

Expected: `public/manager/` directory populated with `index.html` (not used), `assets/`, `manifest.webmanifest`, `sw.js`.

- [ ] **Step 3: Verify the SPA shell loads**

```bash
php artisan serve
```

Open `http://localhost:8000/manager` — should see the login page. Open browser DevTools → Application → Manifest — should show the PWA manifest. Application → Service Workers — should show sw.js registered.

- [ ] **Step 4: Commit**

```bash
cd ../../../
git add public/manager/ packages/Webkul/ManagerApp/
git commit -m "feat(manager-app): built frontend assets"
```

---

## Task 23: End-to-End Verification

- [ ] **Step 1: Start Reverb server**

```bash
php artisan reverb:start
```

- [ ] **Step 2: Start queue worker** (for broadcast jobs if QUEUE_CONNECTION != sync)

```bash
php artisan queue:work
```

- [ ] **Step 3: Run all manager tests**

```bash
php artisan test --compact --testsuite="ManagerApp Feature Test"
```

Expected: all PASS.

- [ ] **Step 4: Auth flow**

1. Open `http://localhost:8000/manager/login`
2. Log in with a manager admin account (one with `manager.app.access` permission + inventory source assigned)
3. Should redirect to orders list.
4. Log in with a non-manager account — should see 403.

- [ ] **Step 5: Order scoping**

1. Place a test order for inventory source A
2. Log in as manager of source A — order should appear
3. Log in as manager of source B — order should NOT appear

- [ ] **Step 6: Status update**

1. Expand an order card
2. Change status dropdown, click Save
3. Card updates in-place without reload

- [ ] **Step 7: Real-time update**

1. Open manager app in browser tab 1 (logged in as manager)
2. In a second tab or via API, place a new order for the manager's inventory source
3. Tab 1 should show the new order appear without refresh

- [ ] **Step 8: Push notification**

1. Click "Enable" on the push notification prompt
2. Grant browser permission
3. Place a test order
4. Should receive a browser notification (even if the tab is in background)

- [ ] **Step 9: PWA install**

1. Open `http://localhost:8000/manager` in Chrome
2. Address bar should show install icon
3. Install the app → opens standalone without browser chrome

- [ ] **Step 10: Final commit**

```bash
git add -A
git commit -m "feat(manager-app): complete manager PWA implementation"
```
