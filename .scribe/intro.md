# Introduction

API for mobile applications: catalog, categories, products, cart, checkout, customer profile, wishlist.

<aside>
    <strong>Base URL</strong>: <code>https://my_bagisto.test</code>
</aside>

    This documentation covers the Shop API used by mobile applications.

    **Base URL:** Use `config("app.url")` or your deployed store URL (e.g. `https://store.example.com`).

    **Authentication:** Most endpoints are public. Customer-specific endpoints (addresses, wishlist) require session authentication. Authenticate via `POST /api/customer/login` with `email` and `password` in the request body. The response sets session cookies; include them in subsequent requests for protected endpoints.

    **Groups:**
    - **Core** — Countries, states
    - **Categories** — Category tree, attributes, filters
    - **Products** — Product listing, related, up-sell
    - **Product Reviews** — Reviews list and create
    - **Compare** — Compare items
    - **Cart** — Cart CRUD, coupons, shipping estimate
    - **Checkout Onepage** — Addresses, shipping, payment, order creation
    - **Delivery Zones** — Города и зоны доставки с тарифами, выбор зоны (карта/адрес)
    - **Customer Auth** — Login
    - **Customer Addresses** — Address management (authenticated)
    - **Wishlist** — Wishlist management (authenticated)

    <aside>As you scroll, you'll see code examples for working with the API. You can switch the language used with the tabs at the top right.</aside>

