# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Customer endpoints use <b>session-based authentication</b>. Call <code>POST /api/customer/login</code> with <code>email</code> and <code>password</code> in the body. The response sets session cookies; include them (with credentials) in subsequent requests for protected endpoints (addresses, wishlist).
