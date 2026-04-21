# External Payments — иконка метода оплаты

**Дата:** 2026-04-21

## Проблема

Метод оплаты `external_payments` не имеет иконки. В checkout Vue-компонент рендерит `payment.image` для каждого метода — без иконки отображается сломанный `<img>`.

## Решение

Следуем паттерну `CashOnDelivery`: переопределить `getImage()` в классе `ExternalPayments`, добавить дефолтный SVG-ассет в Shop.

## Изменения

### 1. SVG-иконка

**Файл:** `packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg`

Плоская иконка кредитной карты (~55×55 px). Попадает в Vite-манифест автоматически через `import.meta.glob(["../images/**"])` в `app.js`.

### 2. Переопределение `getImage()`

**Файл:** `packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php`

```php
public function getImage(): string
{
    $url = $this->getConfigData('image');
    return $url ? Storage::url($url) : bagisto_asset('images/external-payments.svg', 'shop');
}
```

Логика: если admin загрузил кастомную картинку в конфиге — используем её; иначе — дефолтный SVG из ассетов Shop.

### 3. Пересборка ассетов

```bash
cd packages/Webkul/Shop && npm run build
```

## Не требуется

- Новые конфиги / поля в `system.php`
- Регистрация нового Vite-namespace
- Изменения в шаблонах checkout
- Изменения в других пакетах

## Затронутые файлы

| Файл | Тип изменения |
|------|--------------|
| `packages/Webkul/Shop/src/Resources/assets/images/external-payments.svg` | Новый файл |
| `packages/Webkul/ExternalPayments/src/Payment/ExternalPayments.php` | Правка метода |
