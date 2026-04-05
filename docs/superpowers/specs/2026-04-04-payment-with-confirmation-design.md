# Payment with Confirmation — Design Spec

**Date:** 2026-04-04  
**Status:** Approved  

---

## Context

The store needs a manual payment method where the customer transfers money offline (via bank, mobile, or any mechanism described in free-form instructions), attaches a payment receipt file, and waits for admin approval before the order is processed. This replaces ad-hoc workarounds and gives both the customer and admin a structured UI within the existing Bagisto order lifecycle.

---

## Architecture

A new standalone package `Webkul/PaymentConfirmation` owns the entire feature. This follows the Bagisto convention for payment methods (e.g., `Webkul/Paypal`) and keeps all related code — payment class, models, migrations, controllers, views — in one removable unit.

**Files modified in existing packages (minimal):**
- `packages/Webkul/Sales/src/Models/Order.php` — add new status constant + label
- `packages/Webkul/Admin/src/Resources/views/sales/orders/view.blade.php` — include payment confirmation partial
- `packages/Webkul/Shop/src/Resources/views/customers/account/orders/view.blade.php` — include payment instructions + upload partial

---

## Database

### `payment_confirmation_details`

Admin-managed list of payment instruction sets, each linked to one inventory source.

| Column | Type | Notes |
|---|---|---|
| `id` | increments | |
| `title` | string | Admin label, e.g. "Warsaw Warehouse Account" |
| `instructions` | text | Free-form text shown to customer |
| `inventory_source_id` | unsignedInt FK → `inventory_sources.id` | Determines when this detail is eligible |
| `is_active` | boolean, default true | Enable/disable without deleting |
| `created_at`, `updated_at` | timestamps | |

**Selection logic:** When an order is placed with this payment method, the system identifies all inventory sources referenced by the order's items, queries all active `payment_confirmation_details` linked to those sources, and picks one at random. If no details are found for the matched sources, fall back to any active detail.

> **Implementation note:** The exact column linking order items to inventory sources must be verified during implementation (check `order_items` and related models — Bagisto may store this via `product_inventories` or a similar join). The fallback ensures the feature works even if the source cannot be determined.

### `order_payment_confirmation_receipts`

One record per order using this payment method.

| Column | Type | Notes |
|---|---|---|
| `id` | increments | |
| `order_id` | unsignedInt FK → `orders.id`, unique | One record per order |
| `payment_detail_id` | nullable unsignedInt FK | Which detail was selected (for reference) |
| `instructions_snapshot` | text | Frozen copy of instructions at order time |
| `receipt_path` | nullable string | Laravel Storage path of uploaded file |
| `receipt_original_name` | nullable string | Original filename shown in admin |
| `created_at`, `updated_at` | timestamps | |

---

## New Order Status

Added to `packages/Webkul/Sales/src/Models/Order.php`:

```php
public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';
```

Added to `$statusLabel`:

```php
self::STATUS_AWAITING_CONFIRMATION => 'Awaiting Confirmation',
```

**Status lifecycle for this payment method:**

```
pending  →  (customer uploads receipt)  →  awaiting_confirmation  →  (admin approves)  →  processing
```

---

## Package Structure

```
packages/Webkul/PaymentConfirmation/
├── composer.json
└── src/
    ├── Config/
    │   ├── paymentmethods.php              # Registers 'paymentconfirmation'
    │   └── system.php                      # Admin config panel (active, title, description)
    ├── Database/
    │   └── Migrations/
    │       ├── YYYY_MM_DD_create_payment_confirmation_details_table.php
    │       └── YYYY_MM_DD_create_order_payment_confirmation_receipts_table.php
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Admin/
    │   │   │   ├── PaymentDetailController.php   # CRUD: index, create, store, edit, update, destroy
    │   │   │   └── OrderReceiptController.php    # approve() action
    │   │   └── Shop/
    │   │       └── ReceiptController.php          # upload() action
    │   └── Requests/
    │       └── PaymentDetailRequest.php
    ├── Listeners/
    │   └── CreatePaymentConfirmationRecord.php    # Listens to checkout.order.save.after
    ├── Models/
    │   ├── PaymentDetail.php
    │   └── OrderPaymentReceipt.php
    ├── Payment/
    │   └── PaymentConfirmation.php                # Extends Webkul\Payment\Payment\Payment
    ├── Providers/
    │   └── PaymentConfirmationServiceProvider.php
    ├── Repositories/
    │   ├── PaymentDetailRepository.php
    │   └── OrderPaymentReceiptRepository.php
    └── Resources/
        └── views/
            ├── admin/
            │   ├── payment-details/
            │   │   ├── index.blade.php
            │   │   ├── create.blade.php
            │   │   └── edit.blade.php
            │   └── orders/
            │       └── payment-confirmation.blade.php   # Injected into admin order view
            └── shop/
                └── orders/
                    └── payment-confirmation.blade.php   # Injected into shop order view
```

---

## Key Component Behaviour

### `PaymentConfirmation.php`
- Extends `Webkul\Payment\Payment\Payment`
- `getCode()` returns `'paymentconfirmation'`
- `getRedirectUrl()` returns `''` (no external gateway redirect)
- `isAvailable()` checks `active` config flag

### `CreatePaymentConfirmationRecord` Listener
- Fires on `checkout.order.save.after`
- Exits early if `$order->payment->method !== 'paymentconfirmation'`
- Collects `inventory_source_id` values from order items (via `order_items.product` → inventory)
- Queries active `payment_confirmation_details` where `inventory_source_id` IN collected IDs
- Falls back to any active detail if no match
- Saves snapshot to `order_payment_confirmation_receipts`

### `ReceiptController` (Shop)
- `POST /payment-confirmation/upload/{orderId}` — authenticated, order must belong to customer
- Validates: `receipt` file field, allowed MIME types (image/*, application/pdf)
- Stores file under `payment-receipts/{orderId}/`
- Updates `order_payment_confirmation_receipts` with path and original name
- Changes order status to `awaiting_confirmation`
- Returns redirect back to order detail with success flash

### `OrderReceiptController` (Admin)
- `POST /admin/payment-confirmation/approve/{orderId}`
- Validates order status is `awaiting_confirmation` and receipt exists
- Changes order status to `processing`
- Redirects back to admin order view with success flash

### `PaymentDetailController` (Admin)
- Standard CRUD at `/admin/payment-confirmation/payment-details`
- Form fields: Title, Instructions (textarea), Inventory Source (select from available sources), Active (toggle)

---

## UI Integration

### Admin order view (`view.blade.php`)
Add after the existing Payment Information section:

```blade
@if($order->payment->method === 'paymentconfirmation')
    @include('paymentconfirmation::admin.orders.payment-confirmation', ['order' => $order])
@endif
```

The partial shows:
1. **Instructions sent to customer** — the frozen snapshot text
2. **Receipt file** — download link with original filename (or "Not yet uploaded")
3. **Approve Payment button** — visible only when status is `awaiting_confirmation` and receipt exists

### Shop order view (`view.blade.php`)
Add a "Payment Instructions" tab or section:

```blade
@if($order->payment->method === 'paymentconfirmation')
    @include('paymentconfirmation::shop.orders.payment-confirmation', ['order' => $order])
@endif
```

The partial shows:
1. **Payment instructions** — the snapshot text, always visible
2. **File upload form** — shown when no receipt uploaded yet and status is `pending`
3. **"Receipt submitted"** message — shown when receipt uploaded, status is `awaiting_confirmation`
4. **"Payment confirmed"** message — shown when status is `processing` or beyond

---

## Admin Navigation

Add a link in the admin sidebar under Settings (or a dedicated "Payments" group) pointing to `/admin/payment-confirmation/payment-details`. Register it via the `PaymentConfirmationServiceProvider` using Bagisto's menu system or by modifying the admin layout menu config.

---

## Verification Plan

1. **Enable payment method** — Go to Admin → Configuration → Sales → Payment Methods → enable "Payment with Confirmation"
2. **Add payment detail** — Admin → Payment Confirmation → Payment Details → Create → link to an inventory source, add instructions text
3. **Place test order** — Shop checkout → select "Payment with Confirmation" → complete order
4. **Verify snapshot** — Check DB: `order_payment_confirmation_receipts` has a row with frozen instructions
5. **Customer upload** — Open order detail as customer → verify instructions are shown → upload a receipt file → verify status changes to `awaiting_confirmation`
6. **Admin review** — Open order in admin → verify Payment Confirmation section shows instructions, receipt download link, and Approve button
7. **Admin approve** — Click Approve → verify status changes to `processing`
8. **Status display** — Verify "Awaiting Confirmation" status appears correctly in order lists and detail pages for both admin and customer
