<?php

namespace Webkul\DeliveryZones\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Proxy-контроллер для геокодирования через Yandex Maps API.
 *
 * @group Геокодирование
 */
class GeocodingController
{
    /**
     * Обратное геокодирование: координаты → адрес.
     *
     * @queryParam lat number required Широта. Example: 55.7558
     * @queryParam lng number required Долгота. Example: 37.6173
     */
    public function reverse(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $apiKey = config('services.yandex_maps.api_key');

        if (! $apiKey) {
            return response()->json(['error' => 'Geocoding not configured.'], 503);
        }

        $response = Http::timeout(5)->get('https://geocode-maps.yandex.ru/1.x/', [
            'apikey'  => $apiKey,
            'geocode' => $request->input('lng').','.$request->input('lat'),
            'format'  => 'json',
            'results' => 1,
            'lang'    => 'ru_RU',
        ]);

        if (! $response->successful()) {
            return response()->json(['error' => 'Geocoding service unavailable.'], 502);
        }

        $members = data_get($response->json(), 'response.GeoObjectCollection.featureMember', []);
        $first   = $members[0]['GeoObject'] ?? null;

        if (! $first) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'address'      => data_get($first, 'metaDataProperty.GeocoderMetaData.text'),
                'display_name' => data_get($first, 'name'),
                'pos'          => data_get($first, 'Point.pos'),
            ],
        ]);
    }

    /**
     * Подсказки адресов по введённому тексту.
     *
     * @queryParam query string required Текст для поиска. Example: Москва, Тверская
     * @queryParam lat  number optional Широта центра поиска.
     * @queryParam lng  number optional Долгота центра поиска.
     */
    public function suggest(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'lat'   => ['nullable', 'numeric', 'between:-90,90'],
            'lng'   => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $apiKey = config('services.yandex_maps.suggest_api_key')
            ?: config('services.yandex_maps.api_key');

        if (! $apiKey) {
            return response()->json(['error' => 'Geocoding not configured.'], 503);
        }

        $params = [
            'apikey'   => $apiKey,
            'text'     => $request->input('query'),
            'lang'     => 'ru',
            'results'  => 7,
            'highlight'=> 0,
            'types'    => 'street,house,locality',
        ];

        if ($request->filled('lat') && $request->filled('lng')) {
            $params['ll']   = $request->input('lng').','.$request->input('lat');
            $params['spn']  = '0.5,0.5';
            $params['ull']  = $params['ll'];
            $params['strict_bounds'] = 0;
        }

        $response = Http::timeout(5)->get('https://suggest-maps.yandex.ru/v1/suggest', $params);

        if (! $response->successful()) {
            return response()->json(['error' => 'Suggest service unavailable.'], 502);
        }

        $results = collect(data_get($response->json(), 'results', []))->map(fn ($item) => [
            'title'    => data_get($item, 'title.text'),
            'subtitle' => data_get($item, 'subtitle.text'),
            'address'  => trim(implode(', ', array_filter([
                data_get($item, 'subtitle.text'),
                data_get($item, 'title.text'),
            ]))),
            'tags'     => data_get($item, 'tags', []),
        ]);

        return response()->json(['data' => $results]);
    }
}
