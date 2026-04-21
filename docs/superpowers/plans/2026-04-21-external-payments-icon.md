# External Payments Icon Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Добавить SVG-иконку кредитной карты для метода оплаты `external_payments`, отображаемую на странице checkout.

**Architecture:** Переопределяем `getImage()` в классе `ExternalPayments` по паттерну `CashOnDelivery` — возвращаем дефолтный SVG-ассет из Shop-пакета. SVG автоматически попадает в Vite-манифест через `import.meta.glob(["../images/**"])` в `app.js`.

**Tech Stack:** PHP 8.2, Laravel 11, Vite (laravel-vite-plugin), SVG

---

### Task 1: Добавить SVG-иконку

**Files:**
- Create: `packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg`

- [ ] **Step 1: Создать SVG-файл иконки кредитной карты**

Создать файл `packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg` со следующим содержимым:

```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 55 55" width="55" height="55">
  <!-- Card body -->
  <rect x="4" y="12" width="47" height="31" rx="4" ry="4" fill="#4A90D9"/>
  <!-- Magnetic stripe -->
  <rect x="4" y="20" width="47" height="8" fill="#2C5F8A"/>
  <!-- Chip -->
  <rect x="10" y="30" width="10" height="8" rx="2" ry="2" fill="#F5C518"/>
  <line x1="15" y1="30" x2="15" y2="38" stroke="#C9A000" stroke-width="1"/>
  <line x1="10" y1="34" x2="20" y2="34" stroke="#C9A000" stroke-width="1"/>
  <!-- Card number dots -->
  <circle cx="28" cy="34" r="1.5" fill="#fff" opacity="0.8"/>
  <circle cx="33" cy="34" r="1.5" fill="#fff" opacity="0.8"/>
  <circle cx="38" cy="34" r="1.5" fill="#fff" opacity="0.8"/>
  <circle cx="43" cy="34" r="1.5" fill="#fff" opacity="0.8"/>
</svg>
```

- [ ] **Step 2: Проверить, что файл создан**

```bash
ls packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg
```

Ожидаемый вывод: путь к файлу без ошибок.

---

### Task 2: Переопределить `getImage()` в классе ExternalPayments

**Files:**
- Modify: `packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php`
- Create: `packages/Webkul/ExternalPayments/tests/Feature/ExternalPaymentsImageTest.php`

- [ ] **Step 1: Написать failing-тест**

Создать файл `packages/Webkul/ExternalPayments/tests/Feature/ExternalPaymentsImageTest.php`:

```php
<?php

use Webkul\ExternalPayments\Payment\ExternalPayments;

it('returns non-empty image url when no image configured in admin', function () {
    $payment = new ExternalPayments();

    $image = $payment->getImage();

    expect($image)->not->toBeNull()->not->toBeEmpty();
});
```

- [ ] **Step 2: Запустить тест — убедиться, что он падает**

```bash
php artisan test --compact --testsuite="ExternalPayments Feature Test" --filter="returns non-empty image"
```

Ожидаемый вывод: FAILED (базовый `Payment::getImage()` возвращает `null` из конфига).

- [ ] **Step 3: Реализовать `getImage()` в `ExternalPayments`**

Открыть `packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php` и привести его к следующему виду:

```php
<?php

namespace Webkul\ExternalPayments\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;

class ExternalPayments extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'external_payments';

    /**
     * Return redirect URL to the payment page.
     */
    public function getRedirectUrl(): string
    {
        return route('external-payments.redirect');
    }

    /**
     * Check if payment method is available.
     */
    public function isAvailable(): bool
    {
        if (! $this->getConfigData('active')) {
            return false;
        }

        if (! $this->getConfigData('api_server_url') || ! $this->getConfigData('api_token')) {
            return false;
        }

        return true;
    }

    /**
     * Get payment method image.
     */
    public function getImage(): string
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/external-payments.svg', 'shop');
    }
}
```

- [ ] **Step 4: Запустить тест — убедиться, что проходит**

```bash
php artisan test --compact --testsuite="ExternalPayments Feature Test" --filter="returns non-empty image"
```

Ожидаемый вывод: PASSED.

- [ ] **Step 5: Запустить весь набор тестов ExternalPayments (regression check)**

```bash
php artisan test --compact --filter=WebhookTest
```

Ожидаемый вывод: все тесты PASSED.

- [ ] **Step 6: Форматирование**

```bash
vendor/bin/pint packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php
```

- [ ] **Step 7: Коммит**

```bash
git add packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg
git add packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php
git add packages/Webkul/ExternalPayments/tests/Feature/ExternalPaymentsImageTest.php
git commit -m "feat(external-payments): add default credit card icon"
```

---

### Task 3: Пересборка Shop-ассетов

**Files:** (нет PHP-файлов — только Vite build)

- [ ] **Step 1: Собрать ассеты Shop**

```bash
cd packages/Webkul/Shop && npm run build
```

Ожидаемый вывод: успешная сборка без ошибок, в конце строки вида `dist/assets/...`.

- [ ] **Step 2: Проверить наличие иконки в манифесте**

```bash
grep "external-payments" public/themes/shop/default/build/manifest.json
```

Ожидаемый вывод:

```
"src/Resources/assets/images/external-payments.svg": { "file": "assets/external-payments-XXXXXXXX.svg", ... }
```

- [ ] **Step 3: Закоммитить собранные ассеты**

```bash
git add public/themes/shop/default/build/
git commit -m "build: rebuild shop assets with external-payments icon"
```
