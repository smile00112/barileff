# Спецификация: модуль платёжной системы для интеграции с ExternalPayments

Документ описывает требования к отдельному Laravel-пакету платёжного провайдера (например `Webkul\AcmeBankPayment`), чтобы он корректно работал вместе с `Webkul\ExternalPayments`.

## Роль пакета провайдера

Пакет провайдера отвечает за:

1. Создание и хранение записи о платеже (история операций в БД).
2. Вызов API банка/PSP для получения ссылки на оплату и отслеживания статуса.
3. Обработку возврата пользователя после оплаты (redirect), при необходимости — входящие webhooks от банка.
4. Переход платежа в терминальное состояние «успешно оплачен» и уведомление остальной системы через событие `external_payments.payment.success`.

Пакет **ExternalPayments** не вызывает API банка напрямую — только через зарегистрированный **адаптер**.

## Контракт адаптера

Реализовать `Webkul\ExternalPayments\Contracts\PaymentProviderAdapterInterface`.

### `createPayment(array $data): array`

**Входные данные** (минимум; фактический набор формирует `CreatePaymentController`):

| Ключ | Описание |
|------|----------|
| `amount` | Сумма |
| `client_name`, `client_email`, `client_phone` | Клиент |
| `external_order_id` | Опционально, но обязателен для внешних систем с WooCommerce |
| `product_name` | Опционально |
| `company_id` | Если задан у внешней системы — для мультикомпанийных сценариев |
| `success_redirect_path`, `fail_redirect_path` | Пути на приложение (например для WooCommerce-сценария) |

Адаптер создаёт платёж у провайдера и возвращает **строго**:

```php
[
    'payment_id'   => int,    // ID записи платежа в таблице провайдера (используется в external_payment_requests.provider_payment_id)
    'payment_url'  => string, // URL для перенаправления плательщика
    'order_id'     => string, // Идентификатор заказа/операции в терминах провайдера
]
```

После ответа ядро создаёт строку в `external_payment_requests` с тем же `payment_provider` (ключ из конфига) и `provider_payment_id = payment_id`. Эти значения должны однозначно находить платёж при последующих колбэках и при обработке события успеха.

### `getMinAmount(): float`

Минимальная сумма для валидации в `CreatePaymentController` (правило `min:...`).

## Регистрация в приложении

1. В `config/external-payments.php` (или переопределении в приложении) добавить:
   - в `providers` — ключ (например `acme`), имя и `enabled`;
   - в `adapters` — `'acme' => \Webkul\AcmeBankPayment\Services\AcmeExternalPaymentAdapter::class`.

2. Убедиться, что `PaymentProviderRegistry` подхватывает класс (`class_exists`).

3. В админке ExternalPayments для нужной внешней системы добавить разрешённый провайдер с этим ключом и при необходимости пометить default.

## Событие успешной оплаты

После того как платёж признан успешным (webhook банка, синхронная проверка статуса и т.д.), пакет провайдера **обязан** выполнить:

```php
Event::dispatch('external_payments.payment.success', [$payment]);
```

где `$payment` — объект (обычно Eloquent-модель) со свойствами, которые ожидает `ExternalPaymentWebhookSender::buildPayload`:

- `id` — совпадает с `provider_payment_id` в `external_payment_requests`;
- `order_id`, `external_order_id`, `transaction_id`, `amount`, `status`;
- `client_name`, `client_email`, `client_phone`;
- `created_at`, `updated_at` (желательно Carbon для ISO8601 в payload).

Без этого исходящий webhook на сторону клиента и сценарии, завязанные на это событие, не сработают.

## Согласованность идентификаторов

- `payment_id` в ответе `createPayment` = первичный ключ записи, по которой затем ищется внешняя заявка парами `(payment_provider, provider_payment_id)`.
- Любой redirect/webhook от банка должен позволять восстановить ту же запись (по `payment_id`, `operation_id` и т.д. — на усмотрение пакета провайдера), чтобы статус можно было синхронизировать до диспатча `external_payments.payment.success`.

## WooCommerce и редиректы

Текущая реализация колбэков WooCommerce и слушателей статуса в ExternalPayments **ориентирована на Tochka** (конкретные модели и жёсткий ключ `'tochka'`).

Для нового провайдера необходимо одно из:

1. **Расширить ExternalPayments** (рекомендуется при втором и следующих провайдерах): убрать хардкод `'tochka'`, передавать ключ провайдера из модели платежа или из `external_payment_requests`, унифицировать колбэки.
2. **Дублировать логику** в пакете провайдера: собственные маршруты редиректа, которые обновляют WooCommerce через те же принципы, что `WooCommerceOrderStatusService`, и редирект на `order-received`.

Пока ядро не обобщено, спецификация нового PSP должна явно включать пункт «проверить совместимость с WooCommerce и webhook» в тест-плане.

## Зависимости между пакетами

- Пакет провайдера **может** зависеть от ExternalPayments только ради интерфейса адаптера (или копии контракта в общем contracts-пакете — по политике проекта).
- ExternalPayments не должен иметь жёсткой зависимости на новый пакет, кроме регистрации класса адаптера в конфиге.

## Чеклист приёмки

- [ ] Адаптер зарегистрирован, ключ совпадает в конфиге и в БД внешней системы.
- [ ] `POST /api/external-payments/create` возвращает 201 и корректный `payment_url`.
- [ ] В БД появляется строка `external_payment_requests` с верными `payment_provider` и `provider_payment_id`.
- [ ] После успешной оплаты диспатчится `external_payments.payment.success` с объектом, содержащим ожидаемые поля.
- [ ] При настроенном `webhook_url` у внешней системы уходит POST и запись помечается `webhook_sent`.
- [ ] Проверены минимальная сумма и ошибки валидации (422).
- [ ] Если используется WooCommerce: отдельно проверены сценарии с реальным/тестовым заказом после доработки интеграции (см. выше).

## Известные ограничения текущего ядра ExternalPayments

Слушатели `SendExternalPaymentWebhookListener`, `ExternalPaymentRegistrationListener`, `UpdateWooCommerceOrderStatusListener`, задание `ProcessExternalPaymentRegistrationJob` и часть кода WooCommerce-колбэков используют фиксированную строку `'tochka'`. Для полной поддержки нескольких провайдеров потребуется рефакторинг этих участков; новый пакет должен это учитывать в оценке трудозатрат.
