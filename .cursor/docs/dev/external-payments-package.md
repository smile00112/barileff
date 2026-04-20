# Пакет ExternalPayments (Webkul\ExternalPayments)

Интеграция с произвольной внешней платёжной системой через REST API. Покупатель перенаправляется на платёжную страницу внешнего сервиса; результат возвращается через webhook.

---

## Структура

| Слой | Путь |
|------|------|
| Пакет | `packages/Webkul/ExternalPayments/` |
| Namespace | `Webkul\ExternalPayments\` |
| Provider | `ExternalPaymentsServiceProvider` |
| Платёжный класс | `Payment\ExternalPayments` |
| HTTP-клиент | `Services\ApiClient` |
| Контроллер оплаты | `Http\Controllers\PaymentController` |
| Контроллер webhook | `Http\Controllers\WebhookController` |
| Маршруты | `Http\routes.php` |
| Конфиг методов оплаты | `Config\paymentmethods.php` |
| Системный конфиг | `Config\system.php` |
| Переводы | `Resources\lang\{en,ru}\app.php` |
| Вид редиректа | `Resources\views\redirect.blade.php` *(не используется в текущем потоке — контроллер делает прямой PHP-редирект)* |
| Тесты | `tests\Feature\WebhookTest.php` |

---

## Конфигурация

Секция в admin-панели: **Sales → Payment Methods → External Payments**.

Ключ в core-конфиге: `sales.payment_methods.external_payments`.

| Поле | Тип | channel_based | Описание |
|------|-----|:---:|---------|
| `title` | text | да | Отображаемое название метода оплаты |
| `description` | textarea | да | Описание для покупателя |
| `api_server_url` | text | да | Базовый URL внешнего API (без trailing slash) |
| `api_token` | password | да | Bearer-токен для запросов к внешнему API и верификации входящих webhook |
| `paid_order_status` | select | да | Статус заказа после успешной оплаты: `processing` или `completed` |
| `active` | boolean | да | Включить/выключить метод |
| `sort` | select (1–6) | нет | Порядок отображения |

Метод доступен покупателю только если `active = true`, `api_server_url` и `api_token` заполнены (`ExternalPayments::isAvailable()`).

---

## Маршруты

Файл: `Http\routes.php`.

| Метод | URI | Контроллер#Action | Имя |
|-------|-----|-------------------|-----|
| GET | `/external-payments/redirect` | `PaymentController@redirect` | `external-payments.redirect` |
| GET | `/external-payments/success` | `PaymentController@success` | `external-payments.success` |
| GET | `/external-payments/cancel` | `PaymentController@cancel` | `external-payments.cancel` |
| POST | `/external-payments/webhook` | `WebhookController@handle` | `external-payments.webhook` |

Первые три — middleware `web`. Webhook исключён из `VerifyCsrfToken`.

---

## Платёжный поток

```
Покупатель нажимает "Оплатить"
  → GET /external-payments/redirect
      1. Получить корзину (Cart::getCart()) — если нет, редирект на корзину с ошибкой
      2. Сериализовать через OrderResource → создать Order (OrderRepository::create())
      3. Деактивировать корзину (Cart::deActivateCart())
      4. Собрать payload:
           amount            = order.grand_total (float)
           client_name       = billing first_name + last_name (или company_name)
           client_email      = billing.email ?? order.customer_email
           client_phone      = billing.phone
           external_order_id = order.id (string)
           product_name      = items names через запятую (или "Заказ #N")
      5. ApiClient::createPayment(payload)
           → POST {api_server_url}/api/external-payments/create
           ← { success: true, payment_url: "...", payment_id: 123 }
      6. Сохранить payment_id в order.payment.additional['payment_id']
      7. Сохранить order.id в session['external_payment_order_id']
      8. Redirect → payment_url (на сторонний сайт)

Покупатель завершает оплату на стороннем сайте:
  → GET /external-payments/success
      - Извлечь order_id из сессии → flash('order_id')
      - Редирект на shop.checkout.onepage.success

  или → GET /external-payments/cancel
      - Flash ошибки → редирект на корзину

Параллельно внешний сервис отправляет:
  → POST /external-payments/webhook  (см. раздел Webhook)
```

При ошибке `ApiClient::createPayment()` бросает `\RuntimeException` — заказ уже создан, корзина деактивирована, покупатель видит сообщение об ошибке и попадает на страницу корзины.

---

## Webhook

### Авторизация

Входящий запрос проверяется по заголовку `Authorization: Bearer <token>`.
Токен сравнивается с `api_token` из конфига канала.
Если `api_token` не задан — все запросы принимаются (режим разработки).

### Формат payload

```json
{
  "order_id": 42,
  "payment_status": "paid"
}
```

Оба поля обязательны; при отсутствии — `400 Bad Request`.

### Маппинг статусов

| Группа | Значения `payment_status` | Действие |
|--------|--------------------------|----------|
| Оплачено | `paid`, `completed`, `approved`, `processing` | Перевести заказ в `paid_order_status`, создать инвойс |
| Отклонено | `failed`, `cancelled`, `declined` | Перевести заказ в `canceled` |
| Остальные | любые другие | Игнорировать (ответ `200 OK`) |

Регистр `payment_status` игнорируется (приводится к нижнему).

### Создание инвойса при оплате (`markAsPaid`)

1. Получить `paid_order_status` из конфига (по умолчанию `processing`).
2. Если статус не `processing` — сначала установить `processing`, затем целевой статус (обход guard в Bagisto).
3. Если `order->canInvoice()` — создать инвойс через `InvoiceRepository::create()` со всеми позициями в количестве `qty_to_invoice`.

### Коды ответов webhook

| Ситуация | HTTP |
|----------|------|
| Нет Authorization / неверный токен | 401 |
| Отсутствует `order_id` или `payment_status` | 400 |
| Заказ не найден или метод оплаты не `external_payments` | 404 |
| Успех (включая игнорируемые статусы) | 200 `{ success: true, message: "OK" }` |

---

## ApiClient

Класс: `Services\ApiClient`. Принимает `serverUrl` и `token` в конструкторе. Таймаут: 30 секунд.

### `createPayment(array $data): array`

```
POST {serverUrl}/api/external-payments/create
Authorization: Bearer {token}
Content-Type: application/json

Body: { amount, client_name, client_email, client_phone, external_order_id, product_name }

Ожидаемый ответ HTTP 201:
{ "success": true, "payment_url": "https://...", "payment_id": 123 }
```

Бросает `\RuntimeException` если статус ≠ 201, `success` пустой или `payment_url` отсутствует.

### `checkStatus(int $paymentId): array`

```
GET {serverUrl}/api/tochka-payment/payments/{paymentId}/status
Authorization: Bearer {token}

Ожидаемый ответ HTTP 200:
{ "payment_status": "paid" }
```

Бросает `\RuntimeException` если статус ≠ 200. Метод реализован, но в текущих контроллерах не используется (только webhook). Путь `/api/tochka-payment/payments/{id}/status` захардкожен — при подключении другого провайдера требует изменения.

---

## Переводы

Пространство имён: `external-payments::app`.

| Ключ | EN | RU |
|------|----|----|
| `configuration.index.sales.payment-methods.external-payments` | External Payments | Внешние платежи |
| `configuration.index.sales.payment-methods.api-server-url` | API Server URL | URL сервера API |
| `configuration.index.sales.payment-methods.api-token` | Authorization Token | Токен авторизации |
| `configuration.index.sales.payment-methods.paid-order-status` | Paid Order Status | Статус заказа после оплаты |
| `payment.cart-not-found` | Your cart was not found... | Корзина не найдена... |
| `payment.misconfigured` | Payment method is not configured... | Метод оплаты не настроен... |
| `payment.create-failed` | Failed to create payment... | Не удалось создать платёж... |
| `payment.cancelled` | Payment was cancelled. | Оплата была отменена. |
| `payment.redirecting` | Redirecting to payment gateway | Переход к платёжному шлюзу |

---

## Тесты

Файл: `tests\Feature\WebhookTest.php`. Покрытие: только webhook (`POST /external-payments/webhook`).

| Сценарий |
|----------|
| `400` при отсутствии `order_id` |
| `400` при отсутствии `payment_status` |
| `401` при неверном Bearer-токене |
| `404` при несуществующем заказе |
| `404` при заказе с другим методом оплаты |
| Статус заказа → `processing` при `payment_status=paid` |
| Статус заказа → `canceled` при `failed` / `declined` |
| Все «успешные» статусы: `paid`, `completed`, `approved`, `processing` (dataset) |
| Webhook без токена принимается если `api_token` не задан |

`PaymentController` тестами не покрыт.
