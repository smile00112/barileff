# Пакет ProductTag (Webkul\ProductTag)

AI-генерация тегов для товаров через GigaChat. Теги хранятся в отдельной таблице и связываются с продуктами через pivot.

---

## Структура

| Слой | Путь |
|------|------|
| Пакет | `packages/Webkul/ProductTag/` |
| Namespace | `Webkul\ProductTag\` |
| Provider | `ProductTagServiceProvider` |
| Модель | `Tag` |
| Репозиторий | `TagRepository` (extends BaseRepository с Prettus) |
| Сервис | `GigaChatTagService` |
| Job | `GenerateAITagsJob` |

---

## Концепция

1. Для продукта диспатчится `GenerateAITagsJob`
2. Job вызывает `GigaChatTagService::generateTags()` — отправляет prompt в GigaChat AI
3. Из ответа парсятся теги (через запятую), нормализуются (lowercase, trim, max 100 символов)
4. `TagRepository::syncByNames()` — firstOrCreate для каждого тега по name+locale
5. Результат синхронизируется с продуктом через `syncWithoutDetaching`

### Prompt для GigaChat

Просит сгенерировать короткие теги на русском: синонимы, возможные опечатки, транслитерацию, сленг. На вход: название и описание товара (до 500 символов).

---

## Таблицы БД

### `tags`

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint PK | |
| name | string | Текст тега |
| locale | string(10) default 'ru' | Локаль |
| timestamps | | |
| INDEX(name, locale) | | |

### `product_tag` (pivot)

| Поле | Тип |
|------|-----|
| product_id | uint FK → products (cascade) |
| tag_id | bigint FK → tags (cascade) |
| PRIMARY KEY(product_id, tag_id) | |

---

## Модель Tag

- Реализует `Webkul\ProductTag\Contracts\Tag`
- Fillable: name, locale
- Связь: `products(): BelongsToMany` → ProductProxy через `product_tag`

---

## Сервис GigaChatTagService

- Зависимость: `tigusigalpa/gigachat-php` (конфиг: `config/gigachat.php`)
- Метод: `generateTags(Product $product, int $limit = 10): array<string>`
- Ответ парсится по запятым, нормализуется

---

## Job GenerateAITagsJob

- Реализует `ShouldQueue`
- Конструктор: `int $productId`
- Загружает продукт, генерирует теги, синхронизирует
- Молча завершается если продукт не найден или теги пусты

---

## Репозиторий TagRepository

- Наследует `Prettus\Repository\Eloquent\BaseRepository` (не Webkul Repository)
- `syncByNames(array $names, string $locale = 'ru'): array<int>` — firstOrCreate + возвращает ID

---

## Нет UI

Пакет не имеет маршрутов, контроллеров или views. Теги генерируются только через Job. Управление — через код или Tinker.

---

## Зависимости

- `tigusigalpa/gigachat-php: ^1.1` — GigaChat PHP SDK
- Конфиг: `config/gigachat.php`
