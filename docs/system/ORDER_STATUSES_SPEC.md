# Спецификация: функционал статусов заказов (DB-driven)

## 1. Цель

Перевести функционал статусов заказов на модель, где:
- источник истины по статусам и правилам переходов хранится в БД;
- управление статусами выполняется через единый сервис с валидацией;
- UI, API, интеграции и фоновые процессы используют только данные из БД, без хардкода списков статусов в коде.

## 2. Область действия

Входит в scope:
- хранение справочника статусов;
- правила переходов между статусами;
- назначение стартового статуса для нового заказа;
- массовая смена статусов;
- аудит изменений (история);
- использование статусов в админке, API, интеграциях (iiko, Alfabank), push/live activity, аналитике.

Не входит в scope:
- полный рефакторинг доменной модели заказа;
- изменение бизнес-логики оплаты/доставки вне статусов.

## 3. Анализ текущего состояния

### 3.1. Что уже реализовано в БД

1. Таблица `order_statuses` (миграция `2026_03_04_000001_create_order_statuses_table.php`):
- `code` (unique), `name`, `icon`, `color`, `sort_order`, `is_system`.

2. Таблица `order_workflow_settings`:
- ключ-значение (`key`, `value` JSON), уже хранит:
  - `new_order_status`
  - `delivery_types`
  - `payment_types`
  - `pipelines`
  - `tab_groups`

3. Таблица `order_status_history` (миграция `2026_03_12_000001_create_order_status_history_table.php`):
- аудит смены статуса: `old_status`, `new_status`, `user_type`, `user_id`, `user_name`, `source`, `created_at`.

4. Наблюдатель `OrderObserver`:
- автоматически пишет историю при создании заказа и изменении поля `status`.

### 3.2. Что уже DB-driven

1. Админ-настройки статусов:
- `OrderStatusController` читает/сохраняет `order_statuses` и `order_workflow_settings`.
- Есть UI в `admin::settings.order-statuses.index`.

2. Label статуса в `Order`:
- `status_label` берется из `order_statuses` с кэшированием (`Order::getStatusLabelAttribute`).

3. Валидация изменения статуса в админке:
- в `OrderController@updateStatus` и `massUpdateStatus` список валидных статусов читается из `order_statuses`.

### 3.3. Проблемы текущей реализации

1. Источник истины раздвоен:
- одновременно используются данные из БД и константы `Order::STATUS_*`.

2. Нет нормализованной таблицы переходов:
- `pipelines` хранятся JSON-структурой в `order_workflow_settings`, но не применяются как строгие правила перехода.

3. Стартовый статус нового заказа хардкодится:
- в `OrderRepository::createOrderIfNotThenRetry` выставляется `Order::STATUS_PENDING`, вместо чтения `new_order_status` из БД.

4. Нет единой точки смены статуса:
- есть обходы через прямой `update(['status' => ...])` и ручной `event('sales.order.update-status.after', ...)` в отдельных сервисах.

5. В интеграциях и сервисах много хардкод-списков статусов:
- REST API, iiko, push, analytics, бонусы, shop-контроллеры.

6. Fallback-списки статусов в коде:
- при ошибке доступа к таблице используются захардкоженные массивы статусов.

## 4. Целевая модель (обязательная)

### 4.1. Принципы

1. Единственный источник истины по статусам: таблица `order_statuses`.
2. Единственный источник истины по допустимым переходам: таблица `order_status_transitions`.
3. Любая смена статуса выполняется только через доменный сервис переходов.
4. Любая смена статуса всегда пишет историю и публикует однотипные события.

### 4.2. Требуемые данные в БД

#### 4.2.1. `order_statuses` (расширение)

Обязательные поля:
- `id`
- `code` varchar(50) unique
- `name` varchar(100)
- `icon` varchar(50) null
- `color` varchar(20) null
- `sort_order` int
- `is_system` bool
- `is_active` bool default true
- `is_terminal` bool default false
- `is_cancel_state` bool default false
- `is_payment_required` bool default false
- `meta` json null
- `created_at`, `updated_at`

#### 4.2.2. `order_status_transitions` (новая)

Назначение: нормализованные правила переходов.

Поля:
- `id`
- `from_status_code` varchar(50)
- `to_status_code` varchar(50)
- `delivery_type` varchar(100) null
- `payment_type` varchar(100) null
- `channel` varchar(50) null
- `is_active` bool default true
- `priority` int default 100
- `conditions` json null
- `created_at`, `updated_at`

Индексы/ограничения:
- FK-like логическая ссылочная целостность на `order_statuses.code` (через проверку в сервисе/валидаторе или FK при переходе к surrogate key).
- индекс `(from_status_code, delivery_type, payment_type, channel, is_active)`.
- уникальность на комбинацию правила без дублей.

#### 4.2.3. `order_workflow_settings`

Сохраняется для UI-настроек, но не используется как единственный механизм правил переходов.

Ключи:
- `new_order_status` (обязателен, должен существовать в `order_statuses`)
- `tab_groups` (UI)
- опционально: `delivery_types`, `payment_types` как справочник для UI.

### 4.3. Сервис смены статуса (единая точка)

Создать сервис `OrderStatusTransitionService`.

Контракт:
- `transition(Order $order, string $toStatus, TransitionContext $ctx): TransitionResult`
- `canTransition(Order $order, string $toStatus, TransitionContext $ctx): bool`
- `getAvailableTransitions(Order $order, TransitionContext $ctx): array`

`TransitionContext` содержит:
- `actor_type`, `actor_id`, `actor_name`
- `source` (`admin`, `api`, `webhook`, `cron`, `system`)
- `delivery_type`, `payment_type`, `channel`
- `reason`, `comment`, `metadata`.

Правила:
1. `toStatus` должен существовать и быть активным в `order_statuses`.
2. Переход должен быть разрешен в `order_status_transitions` с учетом контекста.
3. Для terminal-статусов переходы наружу запрещены (кроме явно разрешенных).
4. Обновление статуса, запись истории и событие выполняются атомарно (транзакция).
5. Событие после успешной транзакции: `sales.order.update-status.after`.

### 4.4. Создание заказа

Требование:
- стартовый статус нового заказа берется из `order_workflow_settings.new_order_status`.
- если настройка невалидна, используется fallback на активный системный статус с кодом `pending`.
- fallback должен логироваться warning-событием.

### 4.5. История статусов

Требования:
1. Запись в `order_status_history` создается только при реальном изменении статуса.
2. Для каждого изменения фиксируется:
- `old_status`, `new_status`
- actor и source
- timestamp.
3. История доступна в админке и API.
4. Запрещено удаление записей истории из UI.

## 5. Требования к приложению

### 5.1. Admin

1. Экран настройки статусов управляет данными `order_statuses` и `order_status_transitions`.
2. Разрешено:
- добавлять/редактировать пользовательские статусы;
- менять порядок;
- включать/выключать статус;
- настраивать terminal/cancel свойства;
- настраивать переходы по контексту (delivery/payment/channel).
3. Нельзя удалять `is_system = true` статус.
4. Массовая смена статуса использует только `OrderStatusTransitionService`.

### 5.2. API и интеграции

1. Любые изменения статусов в:
- REST API
- iiko
- Alfabank
- cron/jobs
должны идти через `OrderStatusTransitionService`.

2. Прямой `update(['status' => ...])` запрещен (кроме миграционных/админ-утилит под feature flag).

3. API получения заказа возвращает:
- `status` (code)
- `status_label` (из БД)
- `available_transitions` (опционально, если запрошено).

### 5.3. События и подписчики

1. Подписчики (push, analytics, бонусы, live activity) должны опираться на code-статусы из БД.
2. Логика подписчиков не должна содержать локальные "дефолтные словари" статусов.
3. Подписчики должны быть устойчивы к появлению новых статусов.

## 6. Миграция и обратная совместимость

### Этап 1. Подготовка

1. Добавить новые поля в `order_statuses`.
2. Добавить таблицу `order_status_transitions`.
3. Заполнить правила переходов на основе текущих пайплайнов/бизнес-логики.

### Этап 2. Введение сервиса

1. Реализовать `OrderStatusTransitionService`.
2. Перевести admin update/mass-update на сервис.
3. Перевести REST API, iiko, Alfabank на сервис.

### Этап 3. Устранение хардкода

1. Убрать fallback-массивы статусов из контроллеров/моделей.
2. Убрать прямые присваивания `status` в бизнес-коде.
3. Оставить константы `Order::STATUS_*` только как alias для системных кодов на период совместимости.

### Этап 4. Закрепление

1. Добавить линтер-правило/статический чек: запрет `->update(['status' => ...])` вне сервиса переходов.
2. Включить мониторинг ошибок невалидных переходов.

## 7. Нефункциональные требования

1. Производительность:
- получение списка статусов и переходов кэшируется;
- инвалидация кэша при изменении `order_statuses`/`order_status_transitions`.

2. Надежность:
- переход статуса идемпотентен для запроса "в тот же статус";
- конкурирующие обновления обрабатываются с блокировкой записи заказа.

3. Наблюдаемость:
- structured logs по невалидным переходам;
- метрики количества переходов и отказов по причинам.

## 8. Критерии приемки

1. Добавление нового статуса в БД делает его доступным в админке и API без изменения кода.
2. Невалидный переход отклоняется на уровне сервиса с понятной ошибкой.
3. Создание заказа использует `new_order_status` из БД.
4. Каждое изменение статуса создает запись в `order_status_history`.
5. В кодовой базе отсутствуют прямые изменения поля `status` в бизнес-потоках (кроме разрешенных техутилит).
6. Push/analytics/bonus/live activity корректно работают при добавлении нового статуса, если он настроен в правилах.

## 9. Риски

1. Высокая связанность текущих интеграций с константами статусов.
2. Возможные регрессии в оплате/возвратах при ужесточении правил переходов.
3. Необходима поэтапная миграция с feature flag на критичных потоках.

## 10. Рекомендованный feature flag

- `features.order_statuses.db_driven_transitions`

Режимы:
1. `off`: текущая логика.
2. `shadow`: сервис валидирует и логирует расхождения, но не блокирует.
3. `on`: сервис обязателен, блокирует невалидные переходы.
