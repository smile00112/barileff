# Спецификация: Настройки → Темы (`/admin/settings/themes/`)

Документ описывает функциональность раздела административной панели **Settings → Themes** в пакете `Webkul\Admin`. В кодовой базе Bagisto этот экран управляет записями **кастомизации темы оформления витрины** (англ. *theme customization*), а не файлом конфигурации Laravel/Vite тем.

---

## 1. Терминология

| Понятие | Описание |
|--------|----------|
| **Раздел админки «Themes»** | CRUD над сущностью `ThemeCustomization` (таблица `theme_customizations`), плюс массовые операции. |
| **Тип блока (`type`)** | Вид домашнего/макетного контента (карусель, сетка, HTML и т.д.). Задаётся при создании и не меняет суть записи произвольно без учёта валидации. |
| **Код темы магазина (`theme_code`)** | Строковый ключ из `config('themes.shop')` (например, `default`). Должен совпадать с темой канала (`channel.theme`), чтобы блок показывался на витрине. |
| **Тема в `config/themes.php`** | Регистрация путей к view/assets витрины и админки; расширяется при добавлении новых shop-тем — тогда они появляются в селекте «темы» при создании/редактировании кастомизации. |

---

## 2. Назначение функционала

Администратор создаёт **настраиваемые блоки контента** для витрины: слайдеры, карусели категорий и товаров, произвольный HTML/CSS, ссылки в подвале, блок «услуги/преимущества». Блоки привязаны к **каналу** и **коду темы магазина**, имеют **порядок сортировки** и флаг **активности**.

---

## 3. Доступ и навигация

- **Пункт меню:** `settings.themes` → маршрут `admin.settings.themes.index`.
- **Префикс URL:** задаётся глобальным префиксом админки (`config('app.admin_url')`); типичный путь: `{admin_prefix}/settings/themes`.
- **События представления (точки расширения):**
  - `bagisto.admin.settings.themes.create.before` / `.after`
  - `bagisto.admin.settings.themes.list.before` / `.after`

---

## 4. Права доступа (ACL)

| Ключ | Назначение |
|------|------------|
| `settings.themes` | Просмотр списка (индекс, DataGrid). |
| `settings.themes.create` | Создание записи (`store`). |
| `settings.themes.edit` | Редактирование, массовая смена статуса. |
| `settings.themes.delete` | Удаление, массовое удаление. |

---

## 5. HTTP-маршруты

Группа: префикс `themes`, контроллер `Webkul\Admin\Http\Controllers\Settings\ThemeController`.

| Метод | Путь (относительно группы) | Имя маршрута | Действие |
|-------|----------------------------|--------------|----------|
| GET | `''` | `admin.settings.themes.index` | Список или JSON DataGrid при AJAX. |
| POST | `store` | `admin.settings.themes.store` | Создание; при наличии `id` — загрузка изображений для существующей записи. |
| GET | `edit/{id}` | `admin.settings.themes.edit` | Форма редактирования. |
| POST | `edit/{id}` | `admin.settings.themes.update` | Сохранение. |
| DELETE | `edit/{id}` | `admin.settings.themes.delete` | Удаление записи и каталога файлов `theme/{id}`. |
| POST | `mass-update` | `admin.settings.themes.mass_update` | Массовое изменение `status`. |
| POST | `mass-delete` | `admin.settings.themes.mass_delete` | Массовое удаление. |

Основные файлы реализации:

- `packages/Webkul/Admin/src/Http/Controllers/Settings/ThemeController.php`
- `packages/Webkul/Admin/src/Routes/settings-routes.php`
- `packages/Webkul/Admin/src/DataGrids/Theme/ThemeDataGrid.php`

---

## 6. Список записей (DataGrid)

Отображает данные из `theme_customizations` с джойнами переводов и канала (с учётом запрошенной локали или всех локалей при режиме `all`).

**Колонки:** канал, тема (`theme_code` с человекочитаемым именем из `config('themes.shop')`), тип, имя, порядок сортировки, статус.

**Фильтры:** по каналу, теме, типу, имени, сортировке, статусу.

**Действия:** переход на редактирование, удаление (при наличии прав).

**Массовые действия:** смена статуса (активен/неактивен), удаление.

---

## 7. Создание записи

Модальная форма на странице индекса (Vue-компонент `v-create-theme-form`):

- **name** — обязательное имя блока (для админки).
- **sort_order** — число, порядок среди блоков на главной.
- **type** — один из типов из раздела 9.
- **channel_id** — обязательный канал из `core()->getAllChannels()`.
- **theme_code** — выбор из `config('themes.shop')` (код темы витрины).

После успешного создания выполняется редирект на `edit/{id}`.

---

## 8. Редактирование

Шаблон: `packages/Webkul/Admin/src/Resources/views/settings/themes/edit.blade.php`.

**Общие поля:**

- имя, сортировка, каналы (select), код темы витрины, статус (вкл/выкл).

**Специфичные для типа секции** подключаются через `@includeWhen` по `$theme->type`:

- `image_carousel` → `edit/image-carousel`
- `product_carousel` → `edit/product-carousel`
- `category_carousel` → `edit/category-carousel`
- `category_grid` → `edit/category-grid`
- `category_nested_grid` → `edit/category-nested-grid`
- `static_content` → `edit/static-content`
- `footer_links` → `edit/footer-links`
- `services_content` → `edit/services-content`

Сохранение: POST на `admin.settings.themes.update`, локализуемые опции попадают в перевод модели под ключом текущего `locale` из запроса.

---

## 9. Типы кастомизаций (`type`)

Набор фиксируется валидацией в `ThemeController` и константами модели `Webkul\Theme\Models\ThemeCustomization`.

| Значение `type` | Константа модели | Назначение | Хранение контента |
|-----------------|------------------|------------|-------------------|
| `image_carousel` | `IMAGE_CAROUSEL` | Слайдер картинок с ссылками и заголовками | Перевод `options.images[]`: загрузка в WebP в `storage/theme/{id}/`, плюс поля ссылки/заголовка. Обновление через отдельную логику загрузки в репозитории. |
| `product_carousel` | `PRODUCT_CAROUSEL` | Карусель товаров с фильтрами API | Перевод `options`: заголовок, фильтры для `route('shop.api.products.index')` и ссылка «смотреть все». |
| `category_carousel` | `CATEGORY_CAROUSEL` | Карусель категорий | Перевод `options`: заголовок, фильтры для `route('shop.api.categories.index')`. |
| `category_grid` | `CATEGORY_GRID` | Сетка категорий | Перевод `options`: заголовок, фильтры, лимит, сортировка, число колонок desktop/mobile, показ имени категории. |
| `category_nested_grid` | `CATEGORY_NESTED_GRID` | Вложенная сетка: уровень 1 как заголовки h2, уровень 2 — сетки карточек | Те же `options`, что у `category_grid`. API: список по `parent_id` из фильтров, затем для каждой категории — дети с `parent_id` = id родителя. |
| `static_content` | `STATIC_CONTENT` | Произвольный HTML и CSS | Перевод `options.html` / `options.css`; при сохранении очищается через HTML Purifier (запрещены `script`, `iframe`, `form`). |
| `footer_links` | `FOOTER_LINKS` | Колонки ссылок в подвале | Перевод `options`: секции колонок, в каждой — ссылки (URL, заголовок, порядок, при необходимости meta для фильтра). |
| `services_content` | `SERVICES_CONTENT` | Блок «услуги/преимущества» под шапкой | Перевод `options.services[]`: иконка (класс), заголовок, описание; изображения могут обрабатываться через загрузчик как у карусели. |

---

## 10. Модель данных и переводы

**Таблица `theme_customizations`:** `id`, `theme_code`, `type`, `name`, `sort_order`, `status`, `channel_id`, timestamps.

**Таблица `theme_customization_translations`:** `theme_customization_id`, `locale`, `options` (JSON/массив в Eloquent).

Модель: `Webkul\Theme\Models\ThemeCustomization` (`TranslatableModel`), переводимое поле: `options`.

Репозиторий: `Webkul\Theme\Repositories\ThemeCustomizationRepository` — очистка HTML/CSS для `static_content`, загрузка и склейка изображений для типов карусели/услуг.

---

## 11. Отображение на витрине

- **Главная страница** (`Shop\Http\Controllers\HomeController@index`): выбираются все активные кастомизации с `channel_id` текущего канала, `theme_code` равным `core()->getCurrentChannel()->theme`, сортировка по `sort_order`. В цикле в `shop::home.index` рендерятся типы: `image_carousel`, `static_content`, `category_carousel`, `category_grid`, `category_nested_grid`, `product_carousel` (остальные типы на главной в этом шаблоне не обрабатываются).

- **Подвал:** компонент `shop::components.layouts.footer` запрашивает **одну** активную запись `type = footer_links` для текущего канала и темы (`findOneWhere`). При нескольких подходящих записях фактически используется первая найденная репозиторием.

- **Блок услуг:** `shop::components.layouts.services` — **одна** активная запись `type = services_content` для канала и темы.

Учитывайте ограничение «одна запись» для footer и services при проектировании контента.

---

## 12. События домена

Вызываются из `ThemeController`:

- `theme_customization.create.before`
- `theme_customization.create.after` (аргумент: модель)
- `theme_customization.update.before` / `.after`
- `theme_customization.delete.before` / `.after`

Подписка через `Event::listen` позволяет дополнительно синхронизировать кеш, CDN и т.д.

---

## 13. Файлы мультимедиа

При удалении одной записи (`destroy`) удаляется каталог Storage `theme/{id}` вместе с записями (массовое удаление через `massDestroy` в контроллере **не** вызывает `Storage::deleteDirectory` для каждого id — при массовом удалении каталоги файлов могут остаться; это поведение стоит учитывать при сопровождении).

---

## 14. Автоматизированные проверки

- Feature-тесты: `packages/Webkul/Admin/tests/Feature/Settings/ThemeTest.php`
- E2E (Playwright): `packages/Webkul/Admin/tests/e2e-pw/tests/settings/themes.spec.ts`

---

## 15. Связанная конфигурация

- Регистрация доступных **тем витрины** для выбора в форме: `config/themes.php` → ключ `shop`.
- Тема, с которой сопоставляются блоки на витрине, задаётся в настройках **канала** (поле темы канала при создании/редактировании канала в админке).

Эта спецификация отражает состояние кода на момент составления документа по ветке репозитория проекта.
