# MAP MANIFEST — Полный справочник по Яндекс Картам

> Этот файл — полный манифест для ИИ-агентов. Содержит ВСЁ о реализации Яндекс Карт
> в двух проектах: **Barileff (Bagisto/Laravel)** и **love_food (Nuxt 3)**.

---

# ЧАСТЬ 1: BARILEFF (Bagisto / Laravel + Vue 3 Options API)

## 1.1 Файлы и расположение

| Файл | Назначение |
|------|------------|
| `packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php` | Основной компонент — модалка выбора доставки с картой |
| `packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/bottom.blade.php` | Шапка сайта — кнопка открытия модалки |
| `packages/Webkul/Shop/src/Routes/api.php` | API маршруты (зоны, адреса, пикап) |
| `packages/Webkul/Shop/src/Http/Controllers/API/AddressController.php` | CRUD адресов покупателя |
| `packages/Webkul/Shop/src/Http/Controllers/API/DeliveryZonesController.php` | API зон доставки |
| `config/services.php` | Конфиг `services.yandex_maps.api_key` |

## 1.2 Архитектура компонента

Компонент `v-delivery-method-selector` — Vue 3 Options API, встроенный в Blade через `<script type="text/x-template">`. Рендерится inline на КАЖДОЙ странице в шапке сайта. Модалка открывается по клику на кнопку в хедере.

```
Шапка сайта (desktop/bottom.blade.php)
  └── <v-delivery-method-selector>
        ├── Кнопка в хедере (@click="openModal") — ДВА СОСТОЯНИЯ:
        │   ├── v-if="!confirmedAddress" — округлая (rounded-full, px-5, py-3):
        │   │    └── icon-location + title + subtitle + icon-arrow-right
        │   └── v-else — прямоугольная (w-[300px], rounded-2xl, justify-between):
        │        └── иконка грузовика + «Доставка» / адрес + SVG-стрелка вправо
        ├── Overlay (чёрный backdrop)
        └── Модалка (фиксированная, по центру)
              ├── Header: кнопка закрытия + табы (Доставка / Самовывоз)
              ├── Content (overflow-y-auto):
              │   ├── Поле поиска адреса + выпадашка подсказок (max 10)
              │   ├── Чекбокс "Частный дом"
              │   ├── 4 поля: квартира, подъезд, этаж, домофон (grid 2x2)
              │   ├── Сохранённые адреса (для авторизованных)
              │   ├── Блок с инфо о доставке (зона, время, цена)
              │   ├── Карта Yandex Maps (<div ref="mapContainer">, 360px)
              │   └── Таб самовывоза (список точек)
              └── Footer: кнопка "Подтвердить"
```

## 1.3 Yandex Maps API

- **URL**: `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={key}`
- **Ключ**: из `config('services.yandex_maps.api_key')`
- **Загрузка**: динамический `<script>` через `loadYandexMapsLibraryWithDynamicScript()`
- **Дедупликация**: проверка `window.ymaps`, `document.querySelector('script[data-yandex-maps]')`, shared promise

## 1.4 Все data() свойства (44+)

### Состояние UI
| Свойство | Тип | Начальное | Назначение |
|----------|-----|-----------|------------|
| `isOpen` | Boolean | `false` | Видимость модалки |
| `activeTab` | String | `'delivery'` | Текущий таб (`delivery` \| `pickup`) |
| `cityDropdownOpen` | Boolean | `false` | Состояние кастомного дропдауна города |
| `isLoading` | Boolean | `false` | Общий лоадер |
| `isLoadingPickup` | Boolean | `false` | Загрузка точек самовывоза |
| `isLoadingAddresses` | Boolean | `false` | Загрузка адресов покупателя |
| `isSearchingSuggestions` | Boolean | `false` | Загрузка подсказок |
| `isSubmittingDelivery` | Boolean | `false` | Отправка формы |
| `message` | String | `''` | Сообщение (ошибка/успех) |
| `messageIsError` | Boolean | `false` | Тип сообщения |

### Данные
| Свойство | Тип | Назначение |
|----------|-----|------------|
| `isCustomer` | Boolean | Авторизован ли покупатель |
| `customerProfile` | Object/null | Профиль покупателя |
| `cities` | Array | Все города с зонами |
| `allZones` | Array | Плоский массив всех зон из всех городов |
| `pickupPoints` | Array | Точки самовывоза (inventory sources) |
| `savedAddresses` | Array | Сохранённые адреса покупателя |
| `cartSummary` | Object/null | Данные корзины (subtotal) |

### Выбранные значения
| Свойство | Тип | Назначение |
|----------|-----|------------|
| `selectedZone` | Object/null | Текущая выбранная зона |
| `resolvedZone` | Object/null | Зона определённая по координатам |
| `selectedPickupPoint` | Object/null | Выбранная точка самовывоза |
| `confirmedAddress` | String | Подтверждённый адрес (показан в хедере) |

### Форма адреса
| Свойство | Тип | Назначение |
|----------|-----|------------|
| `deliveryQuery` | String | Текст в поле поиска |
| `suggestions` | Array | Список подсказок геокодера |
| `highlightedSuggestionIndex` | Number | Индекс выделенной подсказки (клавиатура) |
| `isPrivateHouse` | Boolean | Чекбокс "Частный дом" |
| `apartment` | String | Квартира |
| `entrance` | String | Подъезд |
| `floor` | String | Этаж |
| `intercom` | String | Домофон |
| `activeCustomerAddressId` | Number/null | ID выбранного сохранённого адреса |
| `editingCustomerAddressId` | Number/null | ID редактируемого адреса |

### Карта
| Свойство | Тип | Назначение |
|----------|-----|------------|
| `map` | ymaps.Map/null | Инстанс карты |
| `mapReady` | Boolean | Карта инициализирована |
| `mapLoadError` | String | Ошибка загрузки карты |
| `mapMarker` | ymaps.Placemark/null | Маркер адреса |
| `mapZoneObjects` | Object | Полигоны зон `{ zoneId: ymaps.Polygon }` |
| `currentAddressCoords` | Array/null | Координаты пина `[lat, lng]` |
| `_geoObjectsAdded` | Boolean | Флаг: geo-объекты добавлены на карту |

### Сервисные
| Свойство | Тип | Назначение |
|----------|-----|------------|
| `activeReverseGeocodeRequestId` | Number | ID для дедупликации запросов геокодера |
| `activeSuggestionsRequestId` | Number | ID для дедупликации подсказок |
| `suggestionTimer` | Number/null | Таймер debounce для подсказок |
| `yandexMapsLoaderPromise` | Promise/null | Shared promise загрузки скрипта |

### API URL-ы (из Laravel route())
| Свойство | Маршрут | HTTP |
|----------|---------|------|
| `zonesApiUrl` | `shop.api.delivery_zones.index` | GET |
| `selectApiUrl` | `shop.api.delivery_zones.select` | POST |
| `pickupApiUrl` | `shop.api.delivery_zones.pickup_points` | GET |
| `cartSummaryApiUrl` | `shop.checkout.onepage.summary` | GET |
| `customerAddressesApiUrl` | `shop.api.customers.account.addresses.index` | GET |
| `customerAddressUpdateUrl` | `shop.api.customers.account.addresses.update` | PUT |
| `customerAddressDeleteUrl` | `shop.api.customers.account.addresses.delete` | DELETE |

## 1.5 Computed свойства

| Имя | Логика |
|-----|--------|
| `activeCity` | Город текущей selectedZone, или первый город, или null |
| `deliveryPlaceholder` | Строка "Адрес" |
| `showSuggestions` | `suggestions.length > 0 && activeTab === 'delivery'` |
| `visibleSuggestions` | `suggestions.slice(0, 10)` |
| `deliverySummary` | Объект `{ timeLabel, priceLabel, freeFromLabel }` — рассчитывается по rates зоны и subtotal корзины |
| `canConfirmDelivery` | Валидация: есть query + зона + (частный дом ИЛИ apartment+entrance+floor) |

## 1.6 Методы (все, сгруппированы)

### A. Жизненный цикл модалки

#### `openModal()` async
1. `isOpen = true`, блокировка body overflow
2. Очистка message, mapLoadError
3. `loadCities()` — загрузка городов и зон
4. `loadCartSummary()` — данные корзины
5. `loadCustomerAddresses()` — адреса (если авторизован)
6. `restorePersistedAddress()` — восстановление из localStorage
7. `loadPickupPoints()` — если таб самовывоза
8. `$nextTick(() => setTimeout(() => this.initMap(), 250))`

#### `closeModal()`
1. `isOpen = false`, очистка suggestions
2. Восстановление body overflow
3. `teardownMap()` — уничтожение карты

#### `switchTab(tab)`
Переключение таба, очистка message, загрузка данных, реинициализация карты.

### B. Загрузка данных

| Метод | Что делает |
|-------|------------|
| `loadCities()` | GET зоны → `cities[]` + `allZones[]` (flatten) |
| `loadCartSummary()` | GET корзина → `cartSummary` |
| `loadCustomerAddresses()` | GET адреса → `savedAddresses[]` |
| `loadPickupPoints()` | GET пикап-точки → `pickupPoints[]` |

### C. Инициализация и жизненный цикл карты

#### `loadYandexMapsLibrary()` → Promise
Загружает скрипт Yandex Maps API:
1. Проверяет `window.ymaps` — если есть, резолвит сразу
2. Проверяет shared promise `yandexMapsLoaderPromise`
3. Создаёт `<script>`, добавляет в `<head>`
4. Ждёт `load` + поллит `window.ymaps.ready` → `ymaps.ready()`

#### `initMap()` async — КРИТИЧЕСКИЙ МЕТОД
```
1. Guard: map не null, isOpen, activeTab === 'delivery'
2. Guard: $refs.mapContainer существует
3. await loadYandexMapsLibrary()
4. Guard: isOpen (мог закрыть пока грузилось)
5. await ymaps.ready()
6. Guard: isOpen (повторная проверка)
7. Расчёт center/zoom:
   - Если есть currentAddressCoords → center=coords, zoom=15
   - Иначе → calculateZoneBoundsArray() → center=midpoint, zoom по maxSpan
8. new ymaps.Map(container, { center, zoom, controls, suppressMapOpenBlock })
9. map.behaviors.disable(['scrollZoom', 'dblClickZoom'])
10. map.events.add('click', handler) — с setTimeout(0) обёрткой
11. mapReady = true, _geoObjectsAdded = false
12. await map.zoomRange.get(center) — ЖДЁМ загрузку zoom range metadata
13. addGeoObjectsSafely():
    - addZonePolygons() — полигоны зон
    - addMarkerIfNeeded() — маркер (если есть coords)
    - map.container.fitToViewport()
```

#### `calculateZoneBoundsArray()` → [[minLat, minLng], [maxLat, maxLng]] | null
Вычисляет bounding box всех зон для начального view карты.

#### `addZonePolygons()`
Для каждой зоны:
1. `parseZoneCoords(zone)` — парсит polygon_json
2. Создаёт `new ymaps.Polygon([coords], {}, { fillColor, strokeColor, strokeWidth })`
3. `map.geoObjects.add(polygon)`
4. Сохраняет в `mapZoneObjects[zone.id] = polygon`

#### `addMarkerIfNeeded()`
Если `currentAddressCoords` → создаёт `Placemark`, добавляет на карту, сохраняет в `mapMarker`.

#### `updateMarker()` — обновление маркера БЕЗ пересоздания
- Если маркер есть + coords есть → `mapMarker.geometry.setCoordinates(coords)` (перемещение)
- Если маркера нет + coords есть → создание нового `Placemark` + add
- Если маркер есть + coords null → `remove` + null

#### `updateZoneHighlight()` — выделение зоны БЕЗ пересоздания
Для каждого полигона в `mapZoneObjects`:
- `polygon.options.set({ fillColor, strokeWidth })` — меняет стили

#### `teardownMap()`
1. Отмена `suggestionTimer`
2. `map.destroy()` (try/catch)
3. Обнуление всех map-свойств

#### `focusMapOnCoords(coords, { minimumZoom })`
`map.setCenter(coords, zoom, { checkZoomRange: true, duration: 200 })`

### D. Утилиты координат

| Метод | Вход | Выход | Назначение |
|-------|------|-------|------------|
| `toPlainCoords(coords)` | any | `[lat, lng]` \| null | Валидация + нормализация |
| `normalizeCoordinatePair(point)` | any | `[lat, lng]` \| null | Алиас toPlainCoords |
| `stripClosingPoint(points)` | Array | Array | Убирает замыкающую точку (если last === first) |
| `parseCoordinatesValue(value)` | JSON/Array | Array<[lat,lng]> | Парсит polygon_json |
| `parseZoneCoords(zone)` | Zone object | Array \| null | parseCoordinatesValue + фильтрация |
| `coordsEqual(a, b)` | two coords | Boolean | Сравнение с точностью 1e-8 |

### E. Геокодирование

#### `runYmapsGeocode(query, options)` → Promise
Обёртка над `ymaps.geocode()`:
- **Retry**: до 2 повторов при `scriptError` (JSONP сбой), задержка 500ms между попытками
- Логирует запрос / результат / ошибку

#### `geocodeRequest(query, results)` → Promise
1. `await loadYandexMapsLibrary()`
2. `return runYmapsGeocode(query, { results })`

#### `searchByCoords(coords)` → Promise<{ fullAddress, displayAddress, coords, city, province } | null>
Обратное геокодирование координат в адрес:
1. `geocodeRequest(coords, 1)`
2. Извлекает `metaDataProperty.GeocoderMetaData.Address.Components`
3. Ищет province, locality
4. Возвращает объект или null

#### `syncAddressFromCoords(coords, { minimumZoom, source })` async
Полный цикл обработки клика по карте:
1. Дедупликация через `activeReverseGeocodeRequestId++`
2. `placePin(coords)` — ставит маркер
3. `searchByCoords(coords)` — определяет адрес
4. Обновляет `deliveryQuery` и `deliveryAddressFull`
5. `focusMapOnCoords()` — центрирует карту
6. `findZoneByCoords(coords)` — определяет зону

### F. Подсказки адресов

#### `handleDeliveryInput()`
Debounce 250ms → `fetchAddressSuggestions()`

#### `fetchAddressSuggestions()` async
1. Формирует запрос: `"{city_name}, {user_input}"`
2. `ymaps.geocode(query, { results: 20 })`
3. Дедупликация по label
4. Разбивает на primary (улица) / secondary (город) 
5. Заполняет `suggestions[]`

#### `selectSuggestion(suggestion)`
1. `deliveryQuery = suggestion.label`
2. Скрыть подсказки
3. `placePin(suggestion.coords)`
4. Pan map
5. `findZoneByCoords()`

### G. Определение зоны

#### `findZoneByCoords(coords)`
1. Для каждой зоны: `pointInPolygon(lat, lng, polygon)`
2. Если найдена → `selectedZone = zone`, `updateZoneHighlight()`
3. Если нет → flash "Вне зон доставки", `updateZoneHighlight()`

#### `pointInPolygon(lat, lng, polygon)` → Boolean
Алгоритм ray casting — стандартный GIS-метод.

### H. Маркер

#### `placePin(coords)`
1. `currentAddressCoords = toPlainCoords(coords)`
2. `updateMarker()`

#### `clearPin()`
1. `currentAddressCoords = null`
2. `updateMarker()`

### I. Валидация и отправка

#### `validateDeliveryForm()` → String | null
1. `deliveryQuery` не пустой
2. Если не частный дом — есть цифра (номер дома)
3. Если не частный дом — заполнены apartment + entrance + floor
4. `selectedZone` выбрана

#### `confirmSelection()` async
1. Валидация формы
2. POST `/api/delivery-zones/select` с `{ delivery_zone_id, delivery_point_lat, delivery_point_lng, city }`
3. Обновление корзины
4. Сохранение адреса (customer / guest)
5. `confirmedAddress = getDisplayAddress()` — заполняет кнопку хедера
6. `message = ''` — сообщение НЕ показывается
7. Задержка 200ms, закрытие модалки

### J. Управление адресами

| Метод | Назначение |
|-------|------------|
| `normalizeCustomerAddress(address)` | Парсит поле `additional` JSON → flat-объект |
| `selectSavedAddress(address)` | Выбирает сохранённый адрес, заполняет форму |
| `startEditAddress(address)` | Переключает в режим редактирования |
| `deleteSavedAddress(address)` | DELETE API, удаление из массива |
| `applyStoredAddress(payload)` | Заполняет все поля из сохранённого адреса |
| `restorePersistedAddress()` | Восстанавливает из localStorage |
| `persistGuestAddress()` | Сохраняет в localStorage с TTL 7 дней |
| `persistCustomerAddress()` | POST/PUT API |
| `readGuestAddress()` | Читает из localStorage, проверяет TTL |
| `clearGuestAddress()` | removeItem из localStorage |

### K. Отображение и утилиты

| Метод | Назначение |
|-------|------------|
| `getDisplayAddress()` | Конкатенация адреса + квартиры |
| `formatPrice(price)` | Intl.NumberFormat с валютой |
| `hexToRgba(color, opacity)` | Конвертация #hex → rgba() |
| `shortenAddress(fullAddress)` | Убирает страну из начала адреса |
| `debugLog(message, payload)` | console.log с префиксом [DeliverySelector] |
| `showFlash(message, type, duration)` | Временное сообщение в UI |

## 1.7 API маршруты

```
GET    /api/delivery-zones              → DeliveryZonesController@index
POST   /api/delivery-zones/select       → DeliveryZonesController@select
GET    /api/delivery-zones/pickup-points → DeliveryZonesController@pickupPoints

GET    /api/customer/addresses           → AddressController@index
POST   /api/customer/addresses           → AddressController@store
PUT    /api/customer/addresses/edit/{id} → AddressController@update
DELETE /api/customer/addresses/{id}      → AddressController@delete

GET    /checkout/onepage/summary         → OnepageController@summary
```

## 1.8 Формат данных зон

```json
{
  "id": 1,
  "name": "Центр",
  "city_id": 1,
  "polygon_json": [[55.75, 37.61], [55.76, 37.62], [55.74, 37.63]],
  "polygon_color": "#2563eb",
  "polygon_fill_opacity": 0.28,
  "delivery_time_minutes": 45,
  "rates": [
    { "min_order_total": 0, "price": 300 },
    { "min_order_total": 1500, "price": 0 }
  ]
}
```

Координаты: `[lat, lng]` — широта, долгота. Yandex Maps default.

## 1.9 ИЗВЕСТНАЯ ПРОБЛЕМА — _processBoundsChange

### Симптом
```
Uncaught TypeError: Cannot read properties of undefined (reading '0')
    at _processBoundsChange (full.js)
    at _onCollectionAdd (full.js)
```

### Корневая причина
`map.geoObjects.add(polygon/placemark)` вызывает `_onCollectionAdd` → `_processBoundsChange`, который рассчитывает pixel bounds объекта. Для этого нужны данные zoom range, которые Yandex Maps загружает АСИНХРОННО с сервера после создания карты. Если `add()` вызвать ДО завершения этой загрузки — краш, потому что pixel geometry ещё undefined.

### Почему это воспроизводится именно в модалке
1. **Модалка**: карта создаётся в скрытом контейнере → Yandex Maps container не может корректно рассчитать viewport
2. **Данные предзагружены**: зоны грузятся ДО открытия карты → нет async gap
3. **На обычной странице** (love_food, admin): карта создаётся на видимой странице + данные грузятся ПОСЛЕ создания карты (fetch() даёт задержку)

### Текущий фикс (может не работать)
`await map.zoomRange.get(center)` — ждём promise загрузки zoom range ДО добавления объектов.

### Что РЕАЛЬНО работает (love_food)
В love_food карта создаётся на ВИДИМОЙ странице. Полигоны создаются как `ymaps.GeoObject({ geometry: geoJSON })` (не `ymaps.Polygon`), добавляются в `GeoObjectCollection`, и коллекция добавляется на карту ОДНИМ вызовом `add()`. Также используется `geometry.contains()` вместо ручного `pointInPolygon`.

---
---

# ЧАСТЬ 2: LOVE_FOOD (Nuxt 3 + Composition API)

## 2.1 Файлы и расположение

| Файл | Назначение |
|------|------------|
| `composables/useYandexMap.ts` | Загрузчик скрипта Yandex Maps API |
| `components/Restaurant/Map.vue` | Карта с точками самовывоза (рестораны) |
| `components/modals/Receipt/Map.vue` | Карта с зонами доставки + маркер |
| `composables/useGetYandexDeliveryPrice.ts` | Расчёт стоимости Яндекс Доставки |
| `composables/useTwoGisMaps.ts` | Альтернативный загрузчик 2GIS |
| `store/common.js` | Pinia store — зоны, настройки, координаты |

## 2.2 Загрузка скрипта: `useYandexMap.ts`

### Ключевые решения
1. **Global state** вне Vue: `loadingPromise`, `currentLangKey` — переменные модуля
2. **Promise deduplication**: множественные вызовы делят один промис
3. **Смена языка**: удаляет старые `<script>` + сбрасывает `window.ymaps` + перегружает
4. **Polling 100ms**: после `script.onload` поллит `typeof ymaps !== 'undefined'` (нужно время на инициализацию)
5. **Timeout 10-30 секунд**: защита от зависания

```typescript
export function useYandexMaps(langKey: string = ''): Promise<any> {
  // 1. Если загружено с тем же языком → resolve
  // 2. Если язык изменился → удалить старые скрипты, сбросить
  // 3. Если есть loadingPromise → вернуть его
  // 4. Создать <script>, поллить ymaps, resolve
}
```

### URL скрипта
```
https://api-maps.yandex.ru/2.1/?apikey={YMAPS_KEY}&lang={langKey}&coordorder=longlat
```
⚠️ **ВАЖНО**: `coordorder=longlat` — координаты в формате `[lng, lat]` (GeoJSON standard), а НЕ `[lat, lng]`!

## 2.3 Компонент: `Restaurant/Map.vue` (самовывоз)

### Назначение
Показывает точки самовывоза (рестораны) как маркеры. Клик по маркеру → emit `selected`.

### Фаза инициализации
```
onMounted() →
  1. await useYandexMaps()
  2. ymaps.ready(() => {
  3.   map = new ymaps.Map('yandex-map-2', { center, zoom: 13 })
  4.   pointsList.forEach(point => {
  5.     placemark = new ymaps.Placemark(coords, hint, iconOptions)
  6.     placemark.events.add('click', handler)
  7.     map.geoObjects.add(placemark)
  8.   })
  9.   navigator.geolocation.getCurrentPosition → user marker
  10. })
```

### Обработка выбора
```typescript
const onPlaceMarkClick = (index) => {
  acitvePlaceIndex.value = index;
  emits('selected', acitvePlace.value);
  // Меняем размер иконок: выбранный больше, остальные меньше
  placeMarks.value.forEach((place, idx) => {
    place.options.set({
      iconImageSize: index === idx ? [35, 45] : [25, 35],
    });
  });
}
```

### Ключевое отличие от Barileff
- Карта на **ВИДИМОЙ** странице (не в модалке)
- Нет полигонов зон — только точки
- `options.set()` для изменения стилей (не remove/add)

## 2.4 Компонент: `modals/Receipt/Map.vue` (доставка)

### Назначение
Зоны доставки как полигоны + маркер адреса + определение зоны + автообновление при перетаскивании.

### Пропсы
```typescript
{
  deliveryType: string     // 'flat_rate', 'local_pickup', etc.
  deliveryTypeId: string   // Конкретный метод доставки
  currentAddress: any      // Адрес для навигации
  deliveryCoords: [number, number] | null  // Текущие координаты
}
```

### Фаза инициализации
```
ymapsInit() → (вызывается СРАЗУ при setup, не в onMounted!)
  1. ymaps.ready(() => {
  2.   map = new ymaps.Map('yandex-map', { center, zoom: 12 })
  3.   map.events.add(['click'], onMapClick)
  4.   setMarkers()  // <-- Здесь добавляются объекты
  5.   if (isAutoGettingAddress) map.events.add('boundschange', handler)
  6.   map.controls.add(customGeolocationControl)
  7. })
```

### `setMarkers()` — КЛЮЧЕВОЙ МЕТОД
```
1. map.geoObjects.removeAll()  // Очистка
2. collection = new ymaps.GeoObjectCollection({})
3. zonesForMap.forEach(zone => {
     polygon = new ymaps.GeoObject({ geometry: zone.geometry }, zone.options)
     polygon.events.add(['click'], onPolygonClick)
     collection.add(polygon)
   })
4. map.geoObjects.add(collection)  // ОДИН вызов add() для всей коллекции
5. deliveryMarker = new ymaps.Placemark(coords, data, iconOptions)
6. map.geoObjects.add(deliveryMarker)
7. map.setCenter(coords, 16)
```

### Обработка кликов
```typescript
const onMapClick = (e, zone) => {
  const coords = e.get('coords');
  deliveryMarker.value.geometry.setCoordinates(coords);  // Перемещаем маркер
  emits('setDeliveryCoords', coords);
  emits('setDeliveryZone', zone);
}
```

### Определение зоны — `geometry.contains()`
```typescript
// В watcher на deliveryCoords:
collection.value.each(item => {
  if (!item.geometry || item.geometry.getType() === 'Point') return;
  if (item.geometry.contains(coords)) {
    matchedZone = item.options.get('iconImageHref');
  }
});
emits('setDeliveryZone', matchedZone);
```

⚠️ **Ключевое отличие**: love_food использует встроенный Yandex `geometry.contains()` для определения зоны! Barileff использует ручной `pointInPolygon()` с ray casting.

### Watchers
| Что наблюдает | Что делает |
|--------------|------------|
| `props.deliveryCoords` | Перемещает маркер + проверяет зону через `geometry.contains()` |
| `props.deliveryType` | Перерисовывает маркеры/зоны |
| `props.deliveryTypeId` | Перерисовывает + перепроверяет зону |
| `props.currentAddress` | Pan к адресу или возврат к центру |

## 2.5 Формат данных зон (GeoJSON)

```json
{
  "type": "FeatureCollection",
  "features": [{
    "type": "Feature",
    "id": "zone_123",
    "geometry": {
      "type": "Polygon",
      "coordinates": [[[37.61, 55.75], [37.62, 55.76], [37.63, 55.74], [37.61, 55.75]]]
    },
    "properties": {
      "fill": "#2563eb",
      "fill-opacity": 0.14,
      "stroke": "#2563eb",
      "stroke-opacity": 1,
      "stroke-width": 2,
      "description": "Зона центр#cid=zone_1"
    }
  }]
}
```

Координаты: `[lng, lat]` — GeoJSON standard (из-за `coordorder=longlat`).

## 2.6 Порядок координат — КРИТИЧЕСКОЕ РАЗЛИЧИЕ

| Проект | Map API | Внутренний формат | GeoJSON |
|--------|---------|-------------------|---------|
| **Barileff** | `[lat, lng]` (default) | `[lat, lng]` | Не используется |
| **love_food** | `[lng, lat]` (coordorder=longlat) | `[lng, lat]` | `[lng, lat]` |

## 2.7 API для расчёта Яндекс Доставки

```typescript
// useGetYandexDeliveryPrice.ts
POST /api/wp-json/delivery/yandex/check_price
Body: {
  stock_id: number,
  delivery_to_address: string,
  coordinates: "lng, lat",  // Обратный порядок для API
  delivery_to_time: string,
  items: [{ product_id, quantity }]
}
Response: { price, time, ... } или { error: true }
```

---
---

# ЧАСТЬ 3: СРАВНЕНИЕ И КЛЮЧЕВЫЕ ОТЛИЧИЯ

## 3.1 Архитектурные различия

| Аспект | Barileff | love_food |
|--------|----------|-----------|
| **Framework** | Vue 3 Options API, inline Blade template | Nuxt 3, Composition API, SFC |
| **Карта в** | Модалке (скрытый контейнер) | Видимой странице |
| **Координаты** | `[lat, lng]` | `[lng, lat]` (coordorder=longlat) |
| **Полигоны** | `ymaps.Polygon([coords])` | `ymaps.GeoObject({ geometry: geoJSON })` |
| **Коллекция** | Прямой `map.geoObjects.add(polygon)` | `GeoObjectCollection` → один `add()` |
| **Point-in-polygon** | Ручной ray casting `pointInPolygon()` | Yandex `geometry.contains()` |
| **Перемещение маркера** | `geometry.setCoordinates()` (новый код) | `geometry.setCoordinates()` |
| **Стили зон** | `polygon.options.set()` (новый код) | `options` задаются при создании |
| **Обновление зон** | Remove + Add (старый) / options.set (новый) | `removeAll()` + полное пересоздание |
| **Геокодирование** | Через `ymaps.geocode()` с retry | Не используется в Map.vue |
| **Загрузка скрипта** | Свой загрузчик с `<script>` deduplication | `useYandexMaps()` composable |
| **Смена языка** | Нет | Есть (удаление старого скрипта) |

## 3.2 Почему love_food работает, а Barileff нет

### 1. Видимость контейнера
love_food: карта создаётся когда контейнер УЖЕ виден → Yandex Maps корректно вычисляет viewport.
Barileff: модалка анимируется → контейнер может быть не полностью виден при создании карты.

### 2. Async gap
love_food: данные зон загружаются из API ПОСЛЕ создания карты → естественная задержка.
Barileff: зоны предварительно загружены → `addZonePolygons()` вызывается мгновенно.

### 3. GeoJSON vs raw coords
love_food: `ymaps.GeoObject({ geometry: geoJSON })` — Yandex Maps парсит GeoJSON нативно, включая проекцию.
Barileff: `ymaps.Polygon([coords])` — создание полигона напрямую из координат.

### 4. GeoObjectCollection
love_food: все полигоны добавляются в коллекцию, коллекция добавляется ОДНИМ `add()` → один `_processBoundsChange`.
Barileff: каждый полигон добавляется ОТДЕЛЬНЫМ `add()` → N вызовов `_processBoundsChange`.

## 3.3 Рекомендация для исправления Barileff

Чтобы гарантированно исправить `_processBoundsChange`, нужно:

1. **Использовать `ymaps.GeoObjectCollection`** для группового добавления полигонов — один `add()` вместо N
2. **Убедиться что контейнер видим** — `await` на transition-end модалки ДО `initMap()`
3. **`map.container.fitToViewport()`** после создания карты (контейнер мог поменять размер)
4. **`await map.zoomRange.get(center)`** перед добавлением объектов
5. Рассмотреть переход на `ymaps.GeoObject({ geometry })` с GeoJSON вместо `ymaps.Polygon()`
6. Рассмотреть `geometry.contains()` вместо ручного `pointInPolygon()`

---

# ЧАСТЬ 4: БЫСТРАЯ СПРАВКА

## Yandex Maps API 2.1 — ключевые классы

```javascript
// Карта
map = new ymaps.Map(container, { center, zoom, controls })
map.geoObjects.add(object)
map.geoObjects.remove(object)
map.geoObjects.removeAll()
map.setCenter(coords, zoom, { duration })
map.setBounds(bounds, { checkZoomRange, zoomMargin })
map.getZoom()
map.container.fitToViewport()
map.zoomRange.get(coords) // → Promise<[minZoom, maxZoom]>
map.destroy()

// Полигон (способ 1 — raw coords)
polygon = new ymaps.Polygon([coordsRing], {}, { fillColor, strokeColor, strokeWidth })

// Полигон (способ 2 — GeoJSON)
geoObject = new ymaps.GeoObject({
  geometry: { type: 'Polygon', coordinates: [[[lng,lat], ...]] }
}, { fillColor, strokeColor, strokeWidth })

// Коллекция
collection = new ymaps.GeoObjectCollection({})
collection.add(polygon)
collection.each(item => { ... })

// Маркер
placemark = new ymaps.Placemark(coords, { hintContent }, { preset, iconImageHref, iconImageSize })
placemark.geometry.setCoordinates(newCoords)
placemark.options.set({ iconImageSize: [w, h] })

// Геокодирование
ymaps.geocode(query, { results: 10 }) // → Promise<GeoObjectCollection>

// Определение зоны
polygon.geometry.contains([lat, lng]) // → Boolean

// События
map.events.add('click', handler)
map.events.add('boundschange', handler)
map.events.once('actionend', handler)
object.events.add('click', handler)

// Утилиты
ymaps.util.bounds.getCenterAndZoom(bounds, containerSize) // Требует проекцию!
ymaps.geolocation.get() // → Promise<GeoObjectCollection>
```

## Формат polygon_json в БД Barileff

```json
// Массив точек [lat, lng]
[[55.7558, 37.6173], [55.7601, 37.6189], [55.7523, 37.6295]]
```

Без замыкающей точки. Без вложенных колец. Один ring.

## Формат geometry в love_food

```json
// GeoJSON стандарт
{
  "type": "Polygon",
  "coordinates": [[[37.6173, 55.7558], [37.6189, 55.7601], [37.6295, 55.7523], [37.6173, 55.7558]]]
}
```

С замыкающей точкой. [lng, lat]. Вложенный массив (ring).
