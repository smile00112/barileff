# Спецификация модуля ExternalPayments

Пакет `Webkul\ExternalPayments` предоставляет API для внешних систем (магазины, лендинги, WooCommerce и др.), чтобы инициировать оплату через зарегистрированные в приложении платёжные провайдеры. Связь с конкретным банком/PSP реализуется через адаптеры, реализующие `PaymentProviderAdapterInterface`.

## Назначение

- Единая точка входа: HTTP API с авторизацией по токену внешней системы.
- Учёт привязки платежа к внешнему заказу (`external_order_id`), опционально — интеграция с WooCommerce (обновление статуса заказа, редирект на `order-received`).
- Исходящий webhook на URL, заданный у внешней системы, после успешной оплаты.
- Административный UI для управления внешними системами, токенами и разрешёнными провайдерами.

## Архитектура

```
Внешний клиент
    → POST /api/{prefix}/create
        → Middleware ExternalSystemAuth (Bearer token)
            → CreatePaymentController
                → PaymentProviderRegistry → адаптер провайдера
                → запись в external_payment_requests
```

После успешной оплаты на стороне провайдера (сейчас — пакет Tochka) диспатчится событие Laravel `external_payments.payment.success`. Подписчики ExternalPayments обрабатывают webhook, регистрацию администратора и (через события Tochka) обновление WooCommerce.

## Модель данных

### `external_systems`

Внешняя интеграция: магазин или сервис, который вызывает API.

| Поле | Описание |
|------|----------|
| `name` | Название |
| `api_token` | Уникальный токен для Bearer-авторизации |
| `webhook_url` | URL для исходящего POST при успешной оплате (опционально) |
| `is_active` | Активность |
| `company_id` | Привязка к компании (для сценариев с Tochka и регистрацией) |
| `woocommerce_site_url` | Базовый URL сайта WooCommerce |
| `woocommerce_consumer_key` / `woocommerce_consumer_secret` | REST API WooCommerce |
| `paid_order_status` | Целевой статус заказа WooCommerce после оплаты (по умолчанию `processing`) |

Секреты и токен скрыты в сериализации модели там, где применимо.

### `external_system_payment_providers`

Какие ключи провайдеров разрешены для данной внешней системы. Один из записей может быть `is_default = true` — используется, если в запросе не передан `payment_provider`.

Уникальность: пара `(external_system_id, payment_provider)`.

### `external_payment_requests`

Связь созданного платежа у провайдера с внешней системой.

| Поле | Описание |
|------|----------|
| `payment_provider` | Строковый ключ (например `tochka`) |
| `provider_payment_id` | ID записи платежа во внутренней таблице провайдера |
| `provider_order_id` | Идентификатор заказа/операции в терминах провайдера |
| `external_order_id` | ID заказа на стороне клиента (WooCommerce и т.д.) |
| `status` | Например `pending`, после webhook — `paid` |
| `webhook_sent`, `webhook_sent_at` | Факт отправки исходящего webhook |

Индекс по `(payment_provider, provider_payment_id)` для поиска при обработке событий.

## Конфигурация

Файл `config/external-payments.php` (мержится из пакета):

- `api.prefix` — префикс маршрутов API (по умолчанию `external-payments`), итоговый путь: `/api/{prefix}/create`.
- `providers` — список провайдеров с `name`, `enabled`.
- `adapters` — сопоставление ключа провайдера классу, реализующему `PaymentProviderAdapterInterface`.
- `min_amount` — запасной минимум суммы.
- `registration.manager_role_id` — роль создаваемого администратора при сценарии регистрации после оплаты.

## Публичный API

**Метод:** `POST`  
**URL:** `/api/{prefix}/create` (например `/api/external-payments/create`)

**Заголовки:**

- `Authorization: Bearer <api_token>` — обязателен. Токен хранится в `external_systems.api_token`.

**Тело запроса (JSON):**

| Поле | Обязательность | Описание |
|------|----------------|----------|
| `amount` | Да | Сумма, не ниже минимума адаптера |
| `client_name` | Да | ФИО или имя |
| `client_email` | Да | Email |
| `client_phone` | Да | Телефон |
| `external_order_id` | Условно | Обязателен, если у внешней системы задан `woocommerce_site_url` |
| `product_name` | Нет | Наименование |
| `payment_provider` | Нет | Ключ провайдера; иначе — default из БД |

Провайдер должен быть и в конфиге (`enabled` + зарегистрированный адаптер), и в списке разрешённых для данной внешней системы.

**Успешный ответ (201):**

```json
{
  "success": true,
  "payment_id": 123,
  "order_id": "...",
  "payment_url": "https://..."
}
```

Коды ошибок: 401 (токен), 422 (валидация, провайдер не разрешён, нет default), 500 (сбой адаптера).

Дополнительно контроллер может создать покупателя по email, если его нет и задан `company_id`; для WooCommerce в данные адаптера подмешиваются `success_redirect_path` и `fail_redirect_path` на маршруты `external-payments/woocommerce/*`.

## Веб-маршруты (колбэки)

Префикс `external-payments/woocommerce`:

- `GET success` — `WooCommerceCallbackController@success`
- `GET failure` — `WooCommerceCallbackController@failure`

Используются для сценария с WooCommerce: проверка статуса платежа, обновление заказа в WooCommerce, редирект на `.../checkout/order-received/{id}/`.

## Админка

Маршруты под префиксом админки: `external-payments/systems` — список, создание, редактирование внешних систем, генерация токена. См. `Routes/admin.php`, `ExternalSystemController`.

## События и побочные эффекты

### `external_payments.payment.success`

Диспатчится из пакета платёжного провайдера (в проекте — `Webkul\TochkaPayment`) при успешной оплате, аргумент: массив с одним объектом платежа `[ $payment ]`.

Подписчики ExternalPayments:

- **SendExternalPaymentWebhookListener** — находит `external_payment_requests` по провайдеру и `provider_payment_id`, отправляет POST JSON на `webhook_url`.
- **ExternalPaymentRegistrationListener** — при выполнении условий ставит в очередь `ProcessExternalPaymentRegistrationJob` (создание администратора, письма).

### События Tochka: `PaymentSuccess`, `PaymentFailed`

**UpdateWooCommerceOrderStatusListener** обновляет статус заказа в WooCommerce через REST, если для платежа есть запись ExternalPayments и настроен WooCommerce.

## Исходящий webhook

Класс `ExternalPaymentWebhookSender`: тело JSON включает `payment_id`, `order_id`, `external_order_id`, `external_request_id`, `transaction_id`, `amount`, `status`, блок `client`, даты. После успешного HTTP-ответа запись помечается `webhook_sent`, `status` → `paid`.

## Зависимости

- Реализованный адаптер **Tochka** (`TochkaPaymentAdapter`) зависит от `Webkul\TochkaPayment`.
- Регистрация админа и уведомления — от `Webkul\User`, `Webkul\Newsletters`, `Webkul\Core` и др.

## Ограничения и технический долг

В ряде классов жёстко указан провайдер `'tochka'` при поиске `ExternalPaymentRequest` и в webhook-слушателе. Добавление второго провайдера в конфиг **не гарантирует** работу webhook, WooCommerce-колбэков и регистрации без доработки этих мест (см. `PAYMENT_PROVIDER_MODULE_SPEC.md`).

---

*Версия документа соответствует состоянию кода пакета в репозитории.*
