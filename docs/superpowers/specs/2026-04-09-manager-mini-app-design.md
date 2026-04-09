# Manager Mini-App — Design Spec
**Date:** 2026-04-09

## Context

Warehouse managers need a lightweight, mobile-first app to handle orders for their specific warehouse. The main Bagisto admin panel is too broad; managers should only see orders routed to their inventory source, update statuses, and get push notifications when new orders arrive or statuses change. The app must work offline (PWA), install on phones, and stay live without page refreshes.

---

## Scope

One new Laravel package: `packages/Webkul/ManagerApp/`
- Backend: REST API with Sanctum auth, order management, WebPush subscriptions, Reverb broadcast events
- Frontend: Vue 3 SPA with PWA support, its own Vite build

---

## 1. Database

### Migration: `add_inventory_source_id_to_orders_table`

Add `inventory_source_id` (nullable unsigned int, FK → `inventory_sources`, set null on delete) to the `orders` table.

Populated by a new event listener `CopyInventorySourceToOrder` that fires on `checkout.order.save.after`: reads the order's associated cart's `inventory_source_id` and writes it to the order.

**Why not the `before` event:** the order ID doesn't exist yet before creation; `save.after` ensures we have the order model to update.

---

## 2. Manager Identity

A user is a valid manager when **both** conditions are met:
1. Their role has the ACL permission `manager.app.access` (defined in `Config/acl.php`)
2. They have at least one row in `admin_inventory_sources` (checked via `Admin::isInventorySourceRestricted()`)

The `ManagerAuthenticate` middleware enforces both on every API request after Sanctum resolves the token.

---

## 3. Backend API

**Route prefix:** `/api/manager` (registered in `routes/api.php`, protected by `sanctum` + `manager.authenticate` middleware, except login)

**Served from:** separate subdomain (e.g. `manager.example.com`). API at `/api/manager/*`, SPA shell at `/*` (via `routes/web.php`).

**CORS:** `config/cors.php` must allow `manager.example.com` origin for `/api/manager/*` and `/broadcasting/auth`. Add to the `allowed_origins` list before deploying.

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/manager/auth/login` | Validate credentials, return Sanctum token |
| DELETE | `/api/manager/auth/logout` | Revoke current token |
| GET | `/api/manager/auth/me` | Authenticated admin + assigned inventory sources |

Token stored in `localStorage` on the frontend. Sent as `Authorization: Bearer {token}` header on all API requests, including the private channel auth request to `/broadcasting/auth` (Laravel Echo must be configured with `auth.headers.Authorization`).

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/manager/orders` | Paginated order list (15/page) |
| GET | `/api/manager/orders/{id}` | Full order detail |
| PATCH | `/api/manager/orders/{id}/status` | Update status |
| GET | `/api/manager/orders/statuses` | List of allowed target statuses |

**Filter params on `GET /orders`:** `date_from`, `date_to`, `customer` (matches `customer_first_name`, `customer_last_name`, `customer_email`), `status`, `page`

**Scope:** all queries are scoped to `orders.inventory_source_id IN (manager's source IDs)`. Uses existing `OrderRepository` extended in a service class — no direct model queries in the controller.

**`OrderResource`** (list): `id`, `increment_id`, `status`, `status_label`, `created_at`, `customer_full_name`, `grand_total`, `order_currency_code`

**`OrderDetailResource`** (detail): above + `items` (name, qty, price), `shipping_address`, `billing_address`, `payment.method`, `shipping_title`, `comments`

**`UpdateOrderStatusRequest`** validates that the given status exists in the allowed list returned by `GET /orders/statuses`. The allowed list is the full set of Order status constants — the API returns all of them; business logic for restricting transitions can be added later.

### Push Subscriptions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/manager/push/subscribe` | Upsert WebPush subscription for this admin |
| DELETE | `/api/manager/push/subscribe` | Remove subscription by endpoint |

Uses existing `PushSubscriptionRepository::upsertForSubscribable()` with `subscribable_type = 'admin'`, `subscribable_id = admin.id`.

---

## 4. Real-Time (Laravel Reverb)

### Broadcast events

`ManagerOrderCreated` and `ManagerOrderStatusUpdated` implement `ShouldBroadcast` and broadcast on private channel `manager.{admin_id}`.

**Listener `BroadcastOrderEvents`** fires on:
- `checkout.order.save.after` → dispatches `ManagerOrderCreated` to all managers of `order->inventory_source_id`
- `sales.order.update-status.after` → dispatches `ManagerOrderStatusUpdated`

To find target managers: `Admin::whereHas('inventorySources', fn($q) => $q->where('id', $order->inventory_source_id))->get()`

**Channel auth:** standard Sanctum-authenticated private channel in `routes/channels.php`:
```php
Broadcast::channel('manager.{adminId}', function ($user, $adminId) {
    return (int) $user->id === (int) $adminId;
});
```

### Push notifications (WebPush)

Add `WebPushService::sendToManagersForInventorySource(int $sourceId, string $title, string $body, ?string $url)` which queries `push_subscriptions` for all admin subscriptions whose `subscribable_id` is in the list of managers for that source.

The same `BroadcastOrderEvents` listener triggers both broadcast and WebPush — so notifications reach managers even when the app is in the background (WebPush) and in the foreground (WebSocket).

---

## 5. Frontend — Vue 3 SPA

### Build

Location: `packages/Webkul/ManagerApp/resources/js/`
Own `vite.config.js` in the package root using `vite-plugin-pwa`.
Output: `public/manager-app/` (referenced by the Blade SPA shell).

```
npm install          # inside packages/Webkul/ManagerApp/
npm run build
```

Dependencies: `vue@3`, `pinia`, `vue-router@4`, `axios`, `laravel-echo`, `pusher-js`, `vite-plugin-pwa`

### Structure

```
resources/js/
  main.js
  App.vue
  router/index.js       # / → OrdersPage, /login → LoginPage (redirects if authed)
  stores/
    auth.js             # Pinia: token, user, inventorySources
    orders.js           # Pinia: list, filters, pagination, update in-place
  composables/
    useEcho.js          # Laravel Echo setup, subscribe/unsubscribe
    usePush.js          # WebPush permission request, subscribe/unsubscribe API calls
  pages/
    LoginPage.vue
    OrdersPage.vue
  components/
    OrderCard.vue       # collapsed + expanded states
    OrderFilters.vue    # date range, customer, status
    StatusSelector.vue  # dropdown fetched from /orders/statuses
```

### Pages

**LoginPage:** centered card, email + password inputs, "Sign in" button, inline error. On success: store token in `localStorage`, redirect to `/`.

**OrdersPage:**
- Top bar: app name, manager name, logout button
- FilterBar: date-range pickers, customer text input, status multi-select. All changes trigger a debounced (300ms) `ordersStore.fetch()`.
- Order list: `<OrderCard>` per order, most recent first.
- "Load more" pagination button (or infinite scroll).

### OrderCard

**Collapsed state:**
```
[#00123]  09 Apr 14:32  Ivan Petrov           ● Processing  ›
```

**Expanded state (slide-down):**
```
Customer: Ivan Petrov · ivan@example.com · +7 999 123-45-67
Shipping: Moscow, ul. Lenina 5, apt 3
Items:
  2× Product A                              600 ₽
  1× Product B                              400 ₽
                              Subtotal:    1 000 ₽
                              Shipping:      300 ₽
                              Total:       1 300 ₽
Payment: Cash on Delivery

Status: [Processing ▼]   [Save]
```

Status save calls `PATCH /orders/{id}/status`. On success: updates card in-place via `ordersStore`.

### Real-time integration

`useEcho.js` subscribes to `private:manager.{authStore.user.id}` after login:
- `ManagerOrderCreated` → prepend new order to `ordersStore.orders` if it passes active filters
- `ManagerOrderStatusUpdated` → find order by ID in store, update `status` + `status_label` in-place

### PWA

`vite-plugin-pwa` config:
- `name`: "Manager App", `short_name`: "Orders"
- `theme_color`: `#ffffff`
- Strategy: `generateSW` with `NetworkFirst` for API calls (freshness priority), `CacheFirst` for static assets
- Offline fallback: cached SPA shell + last-loaded order list from cache

Push permission prompt shown after first login (with a "Enable notifications" button — not auto-prompted).

### Style

- Palette: white background, slate-700 text, indigo-600 accent, status colors (amber=pending, blue=processing, green=completed, red=canceled)
- Typography: Inter (loaded from Google Fonts)
- No CSS framework — scoped `<style>` per component with CSS custom properties
- Responsive: single-column on mobile, max-width 720px centered on desktop
- Smooth collapse animation: `max-height` transition on OrderCard expand

---

## 6. Package Registration

`composer.json` (root) path repository for `webkul/manager-app`.

`ManagerAppServiceProvider` registers:
- Config merges: `acl.php`
- Route files: `routes/api.php` (middleware: `api`, `auth:sanctum`, `manager.authenticate`) and `routes/web.php`
- Broadcasts channel: `routes/channels.php`
- Migration path
- View path (for `app.blade.php` SPA shell)

`EventServiceProvider` maps:
- `checkout.order.save.after` → `CopyInventorySourceToOrder`, `BroadcastOrderEvents`
- `sales.order.update-status.after` → `BroadcastOrderEvents`

---

## 7. Verification

1. **Auth:** `POST /api/manager/auth/login` with valid manager creds → 200 + token. Invalid creds → 422. Non-manager admin → 403.
2. **Order scoping:** Manager A (source 1) cannot see orders from source 2. Returns only own warehouse orders.
3. **Filters:** Date range, customer search, and status filter all reduce results correctly.
4. **Status update:** `PATCH /orders/{id}/status` with `status=completed` → order updates, listener fires broadcast + WebPush.
5. **Real-time:** Open two browser tabs; place a test order → new card appears in manager app without refresh.
6. **Push notification:** Subscribe, close the tab, place test order → notification appears in OS notification center.
7. **PWA install:** Chrome → "Add to Home Screen" prompt appears. Installed app opens standalone (no browser chrome).
8. **Offline:** Disable network, reopen app → last cached order list visible, graceful error on new fetch.
