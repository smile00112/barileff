# Payment with Confirmation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Payment with Confirmation" method where a customer transfers money offline, uploads a receipt, and an admin approves it to move the order to processing.

**Architecture:** New standalone package `Webkul/PaymentConfirmation` following the `Webkul/Payment` pattern. The package owns its own payment class, models, migrations, controllers, views, and routes. Minimal changes to existing core files: add one status constant to `Order.php`, and add one `@include` to the admin and shop order detail views.

**Tech Stack:** PHP 8.x, Laravel, Bagisto 2.x, Blade, Pest (for tests), Laravel Storage (file uploads).

**Spec:** `docs/superpowers/specs/2026-04-04-payment-with-confirmation-design.md`

---

## File Map

### New files (package)
| File | Responsibility |
|---|---|
| `packages/Webkul/PaymentConfirmation/composer.json` | Package autoload & provider declaration |
| `src/Providers/PaymentConfirmationServiceProvider.php` | Boots routes, views, config, menu, event listener |
| `src/Config/paymentmethods.php` | Registers `paymentconfirmation` in global payment_methods config |
| `src/Config/menu.php` | Admin sidebar entry for Payment Details |
| `src/Database/Migrations/*_create_payment_confirmation_details_table.php` | `payment_confirmation_details` schema |
| `src/Database/Migrations/*_create_order_payment_confirmation_receipts_table.php` | `order_payment_confirmation_receipts` schema |
| `src/Models/PaymentDetail.php` | Eloquent model for payment instructions |
| `src/Models/OrderPaymentReceipt.php` | Eloquent model for per-order receipt |
| `src/Repositories/PaymentDetailRepository.php` | Thin repo wrapping PaymentDetail |
| `src/Repositories/OrderPaymentReceiptRepository.php` | Thin repo wrapping OrderPaymentReceipt |
| `src/Payment/PaymentConfirmation.php` | Payment method class (extends base Payment) |
| `src/Listeners/CreatePaymentConfirmationRecord.php` | Fires on checkout.order.save.after |
| `src/Http/Controllers/Admin/PaymentDetailController.php` | CRUD for payment details |
| `src/Http/Controllers/Admin/OrderReceiptController.php` | Approve receipt action |
| `src/Http/Controllers/Shop/ReceiptController.php` | Customer receipt upload |
| `src/Http/Requests/PaymentDetailRequest.php` | Validation for payment detail form |
| `src/Routes/admin-web.php` | Admin routes |
| `src/Routes/shop-web.php` | Shop routes |
| `src/Resources/views/admin/payment-details/index.blade.php` | List payment details |
| `src/Resources/views/admin/payment-details/create.blade.php` | Create form |
| `src/Resources/views/admin/payment-details/edit.blade.php` | Edit form |
| `src/Resources/views/admin/orders/payment-confirmation.blade.php` | Injected into admin order view |
| `src/Resources/views/shop/orders/payment-confirmation.blade.php` | Injected into shop order view |

### Modified existing files
| File | Change |
|---|---|
| `packages/Webkul/Sales/src/Models/Order.php` | Add `STATUS_AWAITING_CONFIRMATION` constant + label |
| `packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php` | Add `@include` for admin partial |
| `packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php` | Add `@include` for shop partial |
| Root `composer.json` | Add PSR-4 autoload path |

---

## Task 1: Package Scaffolding & Registration

**Files:**
- Create: `packages/Webkul/PaymentConfirmation/composer.json`
- Create: `packages/Webkul/PaymentConfirmation/src/Providers/PaymentConfirmationServiceProvider.php`
- Modify: root `composer.json`

- [ ] **Step 1.1: Create the package directory structure**

```bash
mkdir -p packages/Webkul/PaymentConfirmation/src/Config
mkdir -p packages/Webkul/PaymentConfirmation/src/Database/Migrations
mkdir -p packages/Webkul/PaymentConfirmation/src/Http/Controllers/Admin
mkdir -p packages/Webkul/PaymentConfirmation/src/Http/Controllers/Shop
mkdir -p packages/Webkul/PaymentConfirmation/src/Http/Requests
mkdir -p packages/Webkul/PaymentConfirmation/src/Listeners
mkdir -p packages/Webkul/PaymentConfirmation/src/Models
mkdir -p packages/Webkul/PaymentConfirmation/src/Payment
mkdir -p packages/Webkul/PaymentConfirmation/src/Repositories
mkdir -p packages/Webkul/PaymentConfirmation/src/Routes
mkdir -p packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details
mkdir -p packages/Webkul/PaymentConfirmation/src/Resources/views/admin/orders
mkdir -p packages/Webkul/PaymentConfirmation/src/Resources/views/shop/orders
```

- [ ] **Step 1.2: Create `packages/Webkul/PaymentConfirmation/composer.json`**

```json
{
    "name": "bagisto/laravel-payment-confirmation",
    "license": "MIT",
    "require": {},
    "autoload": {
        "psr-4": {
            "Webkul\\PaymentConfirmation\\": "src/"
        }
    },
    "minimum-stability": "dev"
}
```

- [ ] **Step 1.3: Create `src/Providers/PaymentConfirmationServiceProvider.php`** (stub — will be filled out incrementally in later tasks)

```php
<?php

namespace Webkul\PaymentConfirmation\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaymentConfirmationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'paymentconfirmation');
        $this->registerRoutes();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/paymentmethods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'admin'])
            ->prefix(config('app.admin_url'))
            ->group(__DIR__.'/../Routes/admin-web.php');

        Route::middleware(['web'])
            ->group(__DIR__.'/../Routes/shop-web.php');
    }
}
```

- [ ] **Step 1.4: Add autoload and provider to root `composer.json`**

Open root `composer.json`. In the `autoload.psr-4` section add:
```json
"Webkul\\PaymentConfirmation\\": "packages/Webkul/PaymentConfirmation/src/"
```

In the `providers` key inside `extra.laravel` (create it if absent) add:
```json
"Webkul\\PaymentConfirmation\\Providers\\PaymentConfirmationServiceProvider"
```

If the root `composer.json` does not use Laravel auto-discovery for local packages, instead open `bootstrap/providers.php` (Laravel 11) or `config/app.php` `providers` array and add:
```php
Webkul\PaymentConfirmation\Providers\PaymentConfirmationServiceProvider::class,
```

- [ ] **Step 1.5: Dump autoloader and verify**

```bash
composer dump-autoload
php artisan package:discover
```

Expected: no errors; new package appears in discovered packages output.

- [ ] **Step 1.6: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/ composer.json bootstrap/providers.php
git commit -m "feat: scaffold PaymentConfirmation package"
```

---

## Task 2: Database Migrations

**Files:**
- Create: `src/Database/Migrations/2026_04_04_000001_create_payment_confirmation_details_table.php`
- Create: `src/Database/Migrations/2026_04_04_000002_create_order_payment_confirmation_receipts_table.php`

- [ ] **Step 2.1: Create migration for `payment_confirmation_details`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Database/Migrations/2026_04_04_000001_create_payment_confirmation_details_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_confirmation_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('instructions');
            $table->unsignedInteger('inventory_source_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('inventory_source_id')
                ->references('id')
                ->on('inventory_sources')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_confirmation_details');
    }
};
```

- [ ] **Step 2.2: Create migration for `order_payment_confirmation_receipts`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Database/Migrations/2026_04_04_000002_create_order_payment_confirmation_receipts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_confirmation_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id')->unique();
            $table->unsignedInteger('payment_detail_id')->nullable();
            $table->text('instructions_snapshot');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('payment_detail_id')
                ->references('id')
                ->on('payment_confirmation_details')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_confirmation_receipts');
    }
};
```

- [ ] **Step 2.3: Run migrations**

```bash
php artisan migrate
```

Expected: two new tables created with no errors.

- [ ] **Step 2.4: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Database/
git commit -m "feat: add payment confirmation database migrations"
```

---

## Task 3: Models, Repositories, and Order Status

**Files:**
- Create: `src/Models/PaymentDetail.php`
- Create: `src/Models/OrderPaymentReceipt.php`
- Create: `src/Repositories/PaymentDetailRepository.php`
- Create: `src/Repositories/OrderPaymentReceiptRepository.php`
- Modify: `packages/Webkul/Sales/src/Models/Order.php`

- [ ] **Step 3.1: Add `STATUS_AWAITING_CONFIRMATION` to Order model**

Open `packages/Webkul/Sales/src/Models/Order.php`.

Find the block of `STATUS_*` constants (around the `STATUS_FRAUD` constant) and add after it:

```php
public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
```

Then find the `$statusLabel` property array and add:

```php
self::STATUS_AWAITING_CONFIRMATION => 'Awaiting Confirmation',
```

- [ ] **Step 3.2: Create `PaymentDetail` model**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Models/PaymentDetail.php

namespace Webkul\PaymentConfirmation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Inventory\Models\InventorySource;

class PaymentDetail extends Model
{
    protected $table = 'payment_confirmation_details';

    protected $fillable = [
        'title',
        'instructions',
        'inventory_source_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventorySource(): BelongsTo
    {
        return $this->belongsTo(InventorySource::class, 'inventory_source_id');
    }
}
```

- [ ] **Step 3.3: Create `OrderPaymentReceipt` model**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Models/OrderPaymentReceipt.php

namespace Webkul\PaymentConfirmation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Models\Order;

class OrderPaymentReceipt extends Model
{
    protected $table = 'order_payment_confirmation_receipts';

    protected $fillable = [
        'order_id',
        'payment_detail_id',
        'instructions_snapshot',
        'receipt_path',
        'receipt_original_name',
    ];

    protected $appends = ['receipt_url'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentDetail(): BelongsTo
    {
        return $this->belongsTo(PaymentDetail::class, 'payment_detail_id');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }

    public function hasReceipt(): bool
    {
        return $this->receipt_path !== null;
    }
}
```

- [ ] **Step 3.4: Create `PaymentDetailRepository`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Repositories/PaymentDetailRepository.php

namespace Webkul\PaymentConfirmation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PaymentConfirmation\Models\PaymentDetail;

class PaymentDetailRepository extends Repository
{
    public function model(): string
    {
        return PaymentDetail::class;
    }
}
```

- [ ] **Step 3.5: Create `OrderPaymentReceiptRepository`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Repositories/OrderPaymentReceiptRepository.php

namespace Webkul\PaymentConfirmation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;

class OrderPaymentReceiptRepository extends Repository
{
    public function model(): string
    {
        return OrderPaymentReceipt::class;
    }
}
```

- [ ] **Step 3.6: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Models/ \
        packages/Webkul/PaymentConfirmation/src/Repositories/ \
        packages/Webkul/Sales/src/Models/Order.php
git commit -m "feat: add PaymentDetail and OrderPaymentReceipt models, add awaiting_confirmation status"
```

---

## Task 4: Payment Class and Config

**Files:**
- Create: `src/Payment/PaymentConfirmation.php`
- Create: `src/Config/paymentmethods.php`
- Create: `src/Config/menu.php`

- [ ] **Step 4.1: Create the payment class**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Payment/PaymentConfirmation.php

namespace Webkul\PaymentConfirmation\Payment;

use Webkul\Payment\Payment\Payment;

class PaymentConfirmation extends Payment
{
    /**
     * Payment method code.
     */
    protected $code = 'paymentconfirmation';

    /**
     * No external redirect needed.
     */
    public function getRedirectUrl(): string
    {
        return '';
    }

    /**
     * Available when active in config.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active');
    }
}
```

- [ ] **Step 4.2: Create `src/Config/paymentmethods.php`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Config/paymentmethods.php

return [
    'paymentconfirmation' => [
        'code'        => 'paymentconfirmation',
        'title'       => 'Payment with Confirmation',
        'description' => 'Transfer payment and upload your receipt for confirmation.',
        'class'       => \Webkul\PaymentConfirmation\Payment\PaymentConfirmation::class,
        'active'      => true,
        'sort'        => 5,
    ],
];
```

- [ ] **Step 4.3: Create `src/Config/menu.php`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Config/menu.php

return [
    [
        'key'   => 'settings.payment-confirmation',
        'name'  => 'Payment Confirmation Details',
        'route' => 'admin.payment-confirmation.payment-details.index',
        'sort'  => 5,
        'icon'  => '',
    ],
];
```

- [ ] **Step 4.4: Verify the payment method appears at checkout**

```bash
php artisan config:clear
php artisan cache:clear
```

Log in to the shop as a customer, add a product to cart, go through checkout to the payment step. Verify "Payment with Confirmation" appears in the list.

- [ ] **Step 4.5: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Payment/ \
        packages/Webkul/PaymentConfirmation/src/Config/
git commit -m "feat: add PaymentConfirmation payment class and config"
```

---

## Task 5: Order Creation Listener

**Files:**
- Create: `src/Listeners/CreatePaymentConfirmationRecord.php`
- Modify: `src/Providers/PaymentConfirmationServiceProvider.php`

The listener fires after an order is saved. It checks if the payment method is `paymentconfirmation`, finds inventory sources for the ordered products, picks a random matching payment detail, and saves a snapshot.

- [ ] **Step 5.1: Write a feature test for the listener**

Create `packages/Webkul/PaymentConfirmation/tests/Feature/CreatePaymentConfirmationRecordTest.php`:

```php
<?php

use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\PaymentConfirmation\Models\PaymentDetail;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;

it('creates a receipt record when order uses paymentconfirmation', function () {
    $source = InventorySource::factory()->create();

    $detail = PaymentDetail::create([
        'title'               => 'Test Instructions',
        'instructions'        => 'Please transfer to account XYZ.',
        'inventory_source_id' => $source->id,
        'is_active'           => true,
    ]);

    $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
    $order->payment()->create(['method' => 'paymentconfirmation', 'method_title' => 'Payment with Confirmation']);

    event('checkout.order.save.after', $order);

    expect(OrderPaymentReceipt::where('order_id', $order->id)->exists())->toBeTrue();
    $receipt = OrderPaymentReceipt::where('order_id', $order->id)->first();
    expect($receipt->instructions_snapshot)->toBe('Please transfer to account XYZ.');
});

it('does nothing when order uses a different payment method', function () {
    $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
    $order->payment()->create(['method' => 'cashondelivery', 'method_title' => 'Cash on Delivery']);

    event('checkout.order.save.after', $order);

    expect(OrderPaymentReceipt::where('order_id', $order->id)->exists())->toBeFalse();
});
```

- [ ] **Step 5.2: Run the test to verify it fails**

```bash
php artisan test --filter CreatePaymentConfirmationRecordTest
```

Expected: FAIL — listener class not found yet.

- [ ] **Step 5.3: Create the listener**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Listeners/CreatePaymentConfirmationRecord.php

namespace Webkul\PaymentConfirmation\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\PaymentConfirmation\Models\PaymentDetail;
use Webkul\Sales\Models\Order;

class CreatePaymentConfirmationRecord
{
    public function handle(Order $order): void
    {
        if ($order->payment?->method !== 'paymentconfirmation') {
            return;
        }

        // Already created (e.g. retry scenario)
        if (OrderPaymentReceipt::where('order_id', $order->id)->exists()) {
            return;
        }

        $detail = $this->selectDetail($order);

        OrderPaymentReceipt::create([
            'order_id'             => $order->id,
            'payment_detail_id'    => $detail?->id,
            'instructions_snapshot' => $detail?->instructions ?? '',
        ]);
    }

    private function selectDetail(Order $order): ?PaymentDetail
    {
        // Get product IDs from the order items
        $productIds = $order->items->pluck('product_id')->filter()->unique();

        // Find inventory source IDs for those products
        $sourceIds = DB::table('product_inventories')
            ->whereIn('product_id', $productIds)
            ->pluck('inventory_source_id')
            ->unique();

        // Try to find a matching active detail
        $detail = PaymentDetail::where('is_active', true)
            ->whereIn('inventory_source_id', $sourceIds)
            ->inRandomOrder()
            ->first();

        // Fallback: any active detail
        return $detail ?? PaymentDetail::where('is_active', true)->inRandomOrder()->first();
    }
}
```

- [ ] **Step 5.4: Register the listener in the ServiceProvider**

In `src/Providers/PaymentConfirmationServiceProvider.php`, update `boot()`:

```php
public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->loadViewsFrom(__DIR__.'/../Resources/views', 'paymentconfirmation');
    $this->registerRoutes();

    Event::listen(
        'checkout.order.save.after',
        \Webkul\PaymentConfirmation\Listeners\CreatePaymentConfirmationRecord::class.'@handle'
    );
}
```

- [ ] **Step 5.5: Run the test again**

```bash
php artisan test --filter CreatePaymentConfirmationRecordTest
```

Expected: PASS.

- [ ] **Step 5.6: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Listeners/ \
        packages/Webkul/PaymentConfirmation/src/Providers/ \
        packages/Webkul/PaymentConfirmation/tests/
git commit -m "feat: add listener to create payment confirmation record on order save"
```

---

## Task 6: Admin — Payment Details CRUD

**Files:**
- Create: `src/Routes/admin-web.php`
- Create: `src/Http/Requests/PaymentDetailRequest.php`
- Create: `src/Http/Controllers/Admin/PaymentDetailController.php`
- Create: `src/Resources/views/admin/payment-details/index.blade.php`
- Create: `src/Resources/views/admin/payment-details/create.blade.php`
- Create: `src/Resources/views/admin/payment-details/edit.blade.php`

- [ ] **Step 6.1: Create admin routes file**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Routes/admin-web.php

use Illuminate\Support\Facades\Route;
use Webkul\PaymentConfirmation\Http\Controllers\Admin\OrderReceiptController;
use Webkul\PaymentConfirmation\Http\Controllers\Admin\PaymentDetailController;

Route::prefix('payment-confirmation')->name('admin.payment-confirmation.')->group(function () {
    Route::resource('payment-details', PaymentDetailController::class)
        ->except(['show']);

    Route::post('approve/{orderId}', [OrderReceiptController::class, 'approve'])
        ->name('approve');
});
```

- [ ] **Step 6.2: Create `PaymentDetailRequest`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Http/Requests/PaymentDetailRequest.php

namespace Webkul\PaymentConfirmation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => 'required|string|max:255',
            'instructions'        => 'required|string',
            'inventory_source_id' => 'required|integer|exists:inventory_sources,id',
            'is_active'           => 'boolean',
        ];
    }
}
```

- [ ] **Step 6.3: Create `PaymentDetailController`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Http/Controllers/Admin/PaymentDetailController.php

namespace Webkul\PaymentConfirmation\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\PaymentConfirmation\Http\Requests\PaymentDetailRequest;
use Webkul\PaymentConfirmation\Repositories\PaymentDetailRepository;

class PaymentDetailController extends Controller
{
    public function __construct(
        protected PaymentDetailRepository $paymentDetailRepository,
        protected InventorySourceRepository $inventorySourceRepository,
    ) {}

    public function index(): View
    {
        $details = $this->paymentDetailRepository->all();

        return view('paymentconfirmation::admin.payment-details.index', compact('details'));
    }

    public function create(): View
    {
        $inventorySources = $this->inventorySourceRepository->all();

        return view('paymentconfirmation::admin.payment-details.create', compact('inventorySources'));
    }

    public function store(PaymentDetailRequest $request): RedirectResponse
    {
        $this->paymentDetailRepository->create(array_merge(
            $request->validated(),
            ['is_active' => $request->boolean('is_active', true)]
        ));

        session()->flash('success', 'Payment detail created successfully.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }

    public function edit(int $id): View
    {
        $detail = $this->paymentDetailRepository->findOrFail($id);
        $inventorySources = $this->inventorySourceRepository->all();

        return view('paymentconfirmation::admin.payment-details.edit', compact('detail', 'inventorySources'));
    }

    public function update(PaymentDetailRequest $request, int $id): RedirectResponse
    {
        $this->paymentDetailRepository->update(
            array_merge($request->validated(), ['is_active' => $request->boolean('is_active', false)]),
            $id
        );

        session()->flash('success', 'Payment detail updated successfully.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->paymentDetailRepository->delete($id);

        session()->flash('success', 'Payment detail deleted.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }
}
```

- [ ] **Step 6.4: Create index view**

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details/index.blade.php --}}
<x-admin::layouts>
    <x-slot:title>Payment Confirmation Details</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Payment Confirmation Details
        </p>

        <a href="{{ route('admin.payment-confirmation.payment-details.create') }}"
           class="primary-button">
            Add New Detail
        </a>
    </div>

    <div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
        <div class="p-4">
            @if($details->isEmpty())
                <p class="text-gray-500 dark:text-gray-400">No payment details found.</p>
            @else
                <table class="w-full text-sm text-left">
                    <thead class="border-b dark:border-gray-700">
                        <tr>
                            <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Title</th>
                            <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Inventory Source</th>
                            <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Active</th>
                            <th class="p-3 font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($details as $detail)
                            <tr class="border-b dark:border-gray-700">
                                <td class="p-3 text-gray-800 dark:text-white">{{ $detail->title }}</td>
                                <td class="p-3 text-gray-800 dark:text-white">
                                    {{ $detail->inventorySource?->name ?? '—' }}
                                </td>
                                <td class="p-3">
                                    @if($detail->is_active)
                                        <span class="label-active">Active</span>
                                    @else
                                        <span class="label-info">Inactive</span>
                                    @endif
                                </td>
                                <td class="p-3 flex gap-2">
                                    <a href="{{ route('admin.payment-confirmation.payment-details.edit', $detail->id) }}"
                                       class="cursor-pointer">
                                        <x-admin::icons.edit class="h-5 w-5 text-blue-500" />
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.payment-confirmation.payment-details.destroy', $detail->id) }}"
                                          onsubmit="return confirm('Delete this detail?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursor-pointer">
                                            <x-admin::icons.delete class="h-5 w-5 text-red-500" />
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-admin::layouts>
```

- [ ] **Step 6.5: Create the create/edit form view**

Create `src/Resources/views/admin/payment-details/create.blade.php`:

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details/create.blade.php --}}
<x-admin::layouts>
    <x-slot:title>Add Payment Detail</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">Add Payment Detail</p>
    </div>

    <form method="POST" action="{{ route('admin.payment-confirmation.payment-details.store') }}"
          class="box-shadow mt-4 rounded bg-white dark:bg-gray-900 p-4">
        @csrf

        @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => null])

        <div class="mt-4">
            <button type="submit" class="primary-button">Save</button>
            <a href="{{ route('admin.payment-confirmation.payment-details.index') }}" class="secondary-button ml-2">Cancel</a>
        </div>
    </form>
</x-admin::layouts>
```

Create `src/Resources/views/admin/payment-details/edit.blade.php`:

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details/edit.blade.php --}}
<x-admin::layouts>
    <x-slot:title>Edit Payment Detail</x-slot:title>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">Edit Payment Detail</p>
    </div>

    <form method="POST"
          action="{{ route('admin.payment-confirmation.payment-details.update', $detail->id) }}"
          class="box-shadow mt-4 rounded bg-white dark:bg-gray-900 p-4">
        @csrf
        @method('PUT')

        @include('paymentconfirmation::admin.payment-details._form', ['inventorySources' => $inventorySources, 'detail' => $detail])

        <div class="mt-4">
            <button type="submit" class="primary-button">Update</button>
            <a href="{{ route('admin.payment-confirmation.payment-details.index') }}" class="secondary-button ml-2">Cancel</a>
        </div>
    </form>
</x-admin::layouts>
```

Create `src/Resources/views/admin/payment-details/_form.blade.php`:

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details/_form.blade.php --}}

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
    <input type="text" name="title"
           value="{{ old('title', $detail?->title) }}"
           class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 dark:bg-gray-800 dark:text-white"
           required />
    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instructions (shown to customer)</label>
    <textarea name="instructions" rows="6"
              class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 dark:bg-gray-800 dark:text-white"
              required>{{ old('instructions', $detail?->instructions) }}</textarea>
    @error('instructions') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inventory Source</label>
    <select name="inventory_source_id"
            class="w-full rounded border border-gray-300 dark:border-gray-600 p-2 dark:bg-gray-800 dark:text-white"
            required>
        <option value="">-- Select Source --</option>
        @foreach($inventorySources as $source)
            <option value="{{ $source->id }}"
                {{ old('inventory_source_id', $detail?->inventory_source_id) == $source->id ? 'selected' : '' }}>
                {{ $source->name }}
            </option>
        @endforeach
    </select>
    @error('inventory_source_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="mb-4 flex items-center gap-2">
    <input type="hidden" name="is_active" value="0" />
    <input type="checkbox" name="is_active" value="1" id="is_active"
           {{ old('is_active', $detail?->is_active ?? true) ? 'checked' : '' }} />
    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">Active</label>
</div>
```

- [ ] **Step 6.6: Verify admin CRUD works**

```bash
php artisan route:list | grep payment-confirmation
```

Expected: see routes for index, create, store, edit, update, destroy.

Visit `http://localhost/admin/payment-confirmation/payment-details` — verify the list page loads.
Create one entry — verify it saves and appears in the list.

- [ ] **Step 6.7: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Routes/ \
        packages/Webkul/PaymentConfirmation/src/Http/ \
        packages/Webkul/PaymentConfirmation/src/Resources/views/admin/payment-details/
git commit -m "feat: add admin CRUD for payment confirmation details"
```

---

## Task 7: Customer Receipt Upload (Shop)

**Files:**
- Create: `src/Routes/shop-web.php`
- Create: `src/Http/Controllers/Shop/ReceiptController.php`

- [ ] **Step 7.1: Create shop routes**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Routes/shop-web.php

use Illuminate\Support\Facades\Route;
use Webkul\PaymentConfirmation\Http\Controllers\Shop\ReceiptController;

Route::middleware(['customer'])
    ->prefix('payment-confirmation')
    ->name('shop.payment-confirmation.')
    ->group(function () {
        Route::post('upload/{orderId}', [ReceiptController::class, 'upload'])
            ->name('upload');
    });
```

- [ ] **Step 7.2: Write a feature test for receipt upload**

Create `packages/Webkul/PaymentConfirmation/tests/Feature/ReceiptUploadTest.php`:

```php
<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Customer\Models\Customer;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;

it('customer can upload receipt and order status changes to awaiting_confirmation', function () {
    Storage::fake('local');

    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => Order::STATUS_PENDING,
    ]);
    $order->payment()->create(['method' => 'paymentconfirmation', 'method_title' => 'Payment with Confirmation']);

    OrderPaymentReceipt::create([
        'order_id'              => $order->id,
        'instructions_snapshot' => 'Transfer to account ABC.',
    ]);

    $file = UploadedFile::fake()->image('receipt.jpg');

    $response = $this->actingAs($customer, 'customer')
        ->post(route('shop.payment-confirmation.upload', $order->id), [
            'receipt' => $file,
        ]);

    $response->assertRedirect();

    $receipt = OrderPaymentReceipt::where('order_id', $order->id)->first();
    expect($receipt->receipt_path)->not->toBeNull();
    expect($receipt->receipt_original_name)->toBe('receipt.jpg');

    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_AWAITING_CONFIRMATION);
});

it('rejects upload if file is not an image or pdf', function () {
    Storage::fake('local');

    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status'      => Order::STATUS_PENDING,
    ]);
    $order->payment()->create(['method' => 'paymentconfirmation', 'method_title' => 'Payment with Confirmation']);

    OrderPaymentReceipt::create([
        'order_id'              => $order->id,
        'instructions_snapshot' => 'Transfer to account ABC.',
    ]);

    $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

    $response = $this->actingAs($customer, 'customer')
        ->post(route('shop.payment-confirmation.upload', $order->id), [
            'receipt' => $file,
        ]);

    $response->assertSessionHasErrors(['receipt']);
});
```

- [ ] **Step 7.3: Run tests to see them fail**

```bash
php artisan test --filter ReceiptUploadTest
```

Expected: FAIL — controller not found.

- [ ] **Step 7.4: Create `ReceiptController`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Http/Controllers/Shop/ReceiptController.php

namespace Webkul\PaymentConfirmation\Http\Controllers\Shop;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class ReceiptController extends Controller
{
    public function __construct(protected OrderRepository $orderRepository) {}

    public function upload(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf|max:10240',
        ]);

        $order = Order::findOrFail($orderId);

        // Ensure the order belongs to the authenticated customer
        abort_if($order->customer_id !== auth('customer')->id(), 403);

        // Ensure order is in pending state and uses this payment method
        abort_if($order->payment?->method !== 'paymentconfirmation', 403);
        abort_if($order->status !== Order::STATUS_PENDING, 403);

        $receipt = OrderPaymentReceipt::where('order_id', $orderId)->firstOrFail();

        $file = $request->file('receipt');
        $path = $file->store('payment-receipts/'.$orderId);

        $receipt->update([
            'receipt_path'          => $path,
            'receipt_original_name' => $file->getClientOriginalName(),
        ]);

        $order->update(['status' => Order::STATUS_AWAITING_CONFIRMATION]);

        session()->flash('success', 'Receipt uploaded successfully. Awaiting confirmation.');

        return back();
    }
}
```

- [ ] **Step 7.5: Run tests again**

```bash
php artisan test --filter ReceiptUploadTest
```

Expected: PASS.

- [ ] **Step 7.6: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Routes/shop-web.php \
        packages/Webkul/PaymentConfirmation/src/Http/Controllers/Shop/ \
        packages/Webkul/PaymentConfirmation/tests/Feature/ReceiptUploadTest.php
git commit -m "feat: add customer receipt upload for payment confirmation"
```

---

## Task 8: Admin — Order Receipt View & Approve Action

**Files:**
- Create: `src/Http/Controllers/Admin/OrderReceiptController.php`
- Create: `src/Resources/views/admin/orders/payment-confirmation.blade.php`
- Modify: `packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php`

- [ ] **Step 8.1: Create `OrderReceiptController`**

```php
<?php
// packages/Webkul/PaymentConfirmation/src/Http/Controllers/Admin/OrderReceiptController.php

namespace Webkul\PaymentConfirmation\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;
use Webkul\Sales\Models\Order;

class OrderReceiptController extends Controller
{
    public function approve(int $orderId): RedirectResponse
    {
        $order = Order::findOrFail($orderId);

        abort_if($order->status !== Order::STATUS_AWAITING_CONFIRMATION, 403, 'Order is not awaiting confirmation.');

        $receipt = OrderPaymentReceipt::where('order_id', $orderId)->firstOrFail();

        abort_if(! $receipt->hasReceipt(), 403, 'No receipt uploaded yet.');

        $order->update(['status' => Order::STATUS_PROCESSING]);

        session()->flash('success', 'Payment approved. Order is now processing.');

        return back();
    }
}
```

- [ ] **Step 8.2: Create the admin order partial view**

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/admin/orders/payment-confirmation.blade.php --}}

@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
@endphp

@if($receipt)
<div class="box-shadow rounded bg-white dark:bg-gray-900 mt-4">
    <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
        <p class="text-base font-semibold text-gray-700 dark:text-white">
            Payment Confirmation
        </p>
    </div>

    <div class="p-4 space-y-4">
        {{-- Instructions snapshot --}}
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Instructions sent to customer</p>
            <div class="rounded border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ $receipt->instructions_snapshot ?: '—' }}</div>
        </div>

        {{-- Receipt file --}}
        <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Payment Receipt</p>
            @if($receipt->hasReceipt())
                <a href="{{ $receipt->receipt_url }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 text-sm underline">
                    {{ $receipt->receipt_original_name ?? 'Download Receipt' }}
                </a>
            @else
                <span class="text-sm text-gray-400 dark:text-gray-500">Not yet uploaded.</span>
            @endif
        </div>

        {{-- Approve button --}}
        @if($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION && $receipt->hasReceipt())
            <form method="POST"
                  action="{{ route('admin.payment-confirmation.approve', $order->id) }}">
                @csrf
                <button type="submit"
                        class="primary-button"
                        onclick="return confirm('Approve this payment and move order to Processing?')">
                    Approve Payment
                </button>
            </form>
        @endif
    </div>
</div>
@endif
```

- [ ] **Step 8.3: Inject partial into the admin order view**

Open `packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php`.

Find the line containing:
```blade
{!! view_render_event('bagisto.admin.sales.order.left_component.after', ['order' => $order]) !!}
```

Immediately **before** that line, add:

```blade
@if($order->payment?->method === 'paymentconfirmation')
    @include('paymentconfirmation::admin.orders.payment-confirmation', ['order' => $order])
@endif
```

- [ ] **Step 8.4: Verify in browser**

Open an order in admin that was placed with "Payment with Confirmation". Verify the "Payment Confirmation" section appears showing the instructions snapshot and the receipt state.

- [ ] **Step 8.5: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Http/Controllers/Admin/OrderReceiptController.php \
        packages/Webkul/PaymentConfirmation/src/Resources/views/admin/orders/ \
        packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php
git commit -m "feat: add admin order receipt view and approve action"
```

---

## Task 9: Shop — Order Payment Instructions View

**Files:**
- Create: `src/Resources/views/shop/orders/payment-confirmation.blade.php`
- Modify: `packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php`

- [ ] **Step 9.1: Create the shop order partial**

```blade
{{-- packages/Webkul/PaymentConfirmation/src/Resources/views/shop/orders/payment-confirmation.blade.php --}}

@php
    $receipt = \Webkul\PaymentConfirmation\Models\OrderPaymentReceipt::where('order_id', $order->id)->first();
@endphp

@if($receipt)
<div class="rounded border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 mt-6 p-4">
    <h3 class="text-base font-semibold text-gray-800 dark:text-white mb-3">
        Payment Instructions
    </h3>

    <div class="mb-4 rounded bg-gray-50 dark:bg-gray-800 p-3 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap border border-gray-200 dark:border-gray-700">
        {{ $receipt->instructions_snapshot }}
    </div>

    @if($order->status === \Webkul\Sales\Models\Order::STATUS_PENDING && ! $receipt->hasReceipt())
        {{-- Upload form --}}
        <form method="POST"
              action="{{ route('shop.payment-confirmation.upload', $order->id) }}"
              enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Attach Payment Receipt
                </label>
                <input type="file" name="receipt"
                       accept="image/*,application/pdf"
                       class="block w-full text-sm text-gray-700 dark:text-gray-300
                              file:mr-4 file:py-2 file:px-4 file:rounded
                              file:border-0 file:text-sm file:font-medium
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100" />
                @error('receipt')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="px-4 py-2 rounded bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                Submit Receipt
            </button>
        </form>

    @elseif($order->status === \Webkul\Sales\Models\Order::STATUS_AWAITING_CONFIRMATION)
        <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Receipt submitted. Your payment is awaiting confirmation.
        </div>

    @elseif(in_array($order->status, [\Webkul\Sales\Models\Order::STATUS_PROCESSING, \Webkul\Sales\Models\Order::STATUS_COMPLETED]))
        <div class="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Payment confirmed. Your order is being processed.
        </div>
    @endif
</div>
@endif
```

- [ ] **Step 9.2: Inject into the shop order view**

Open `packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php`.

Find the line:
```blade
{!! view_render_event('bagisto.shop.customers.account.orders.view.before', ['order' => $order]) !!}
```

Immediately **after** that line, add:

```blade
@if($order->payment?->method === 'paymentconfirmation')
    @include('paymentconfirmation::shop.orders.payment-confirmation', ['order' => $order])
@endif
```

- [ ] **Step 9.3: Clear view cache and verify**

```bash
php artisan view:clear
```

Log in as customer, place a test order with "Payment with Confirmation", then view the order detail. Verify instructions are shown and the upload form is visible.

- [ ] **Step 9.4: Commit**

```bash
git add packages/Webkul/PaymentConfirmation/src/Resources/views/shop/ \
        packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php
git commit -m "feat: add shop order payment instructions and receipt upload UI"
```

---

## Task 10: End-to-End Verification

- [ ] **Step 10.1: Add at least one payment detail in admin**

Admin → `http://localhost/admin/payment-confirmation/payment-details/create`
- Title: "Main Account"
- Instructions: "Please transfer the order total to: Bank XYZ, Account 12345678, Reference: [Order ID]."
- Inventory Source: select any source
- Active: checked → Save

- [ ] **Step 10.2: Place a full test order**

1. Log in as a registered customer in the shop
2. Add a product to cart
3. Proceed to checkout → select "Payment with Confirmation" → place order
4. Verify: order created with status `pending`
5. Verify: `order_payment_confirmation_receipts` table has a row with the instructions snapshot

- [ ] **Step 10.3: Upload receipt as customer**

On the order detail page:
1. Verify payment instructions are displayed
2. Attach a small image file and click "Submit Receipt"
3. Verify: flash message appears
4. Verify: order status changed to `awaiting_confirmation` in DB
5. Verify: page now shows "Receipt submitted. Awaiting confirmation." message

- [ ] **Step 10.4: Approve as admin**

1. Open the order in admin panel
2. Verify the "Payment Confirmation" section shows: instructions snapshot, receipt filename as download link, "Approve Payment" button
3. Click "Approve Payment"
4. Verify: flash message "Payment approved. Order is now processing."
5. Verify: order status is now `processing`
6. Verify: shop order page now shows "Payment confirmed. Your order is being processed."

- [ ] **Step 10.5: Verify "Awaiting Confirmation" appears in order status lists**

Check admin order list — verify `awaiting_confirmation` status shows as "Awaiting Confirmation" label (not a raw code).

- [ ] **Step 10.6: Final commit**

```bash
git add .
git commit -m "feat: complete payment with confirmation implementation"
```

---

## Self-Review Checklist

| Spec Requirement | Covered In |
|---|---|
| New payment method registered | Task 4 |
| New "Awaiting Confirmation" order status | Task 3 |
| Payment details CRUD (admin) | Task 6 |
| Payment details linked to inventory source | Tasks 2, 3, 5 |
| Random detail selected from matching sources | Task 5 |
| Instructions snapshot frozen on order | Task 5 |
| Customer sees instructions on order page | Task 9 |
| Customer can upload receipt | Task 7 |
| Status → awaiting_confirmation after upload | Task 7 |
| Admin sees instructions + receipt in order | Task 8 |
| Admin can approve → status → processing | Task 8 |
| Only registered customers (auth middleware) | Task 7 (customer middleware on route) |
| Fallback to any active detail if no source match | Task 5 |
