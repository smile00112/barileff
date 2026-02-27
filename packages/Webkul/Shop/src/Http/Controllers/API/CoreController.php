<?php

namespace Webkul\Shop\Http\Controllers\API;

/**
 * Базовый API (страны, регионы).
 *
 * @group Базовые данные
 */
class CoreController extends APIController
{
    /**
     * Получить список стран.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCountries()
    {
        return response()->json([
            'data' => core()->countries()->map(fn ($country) => [
                'id' => $country->id,
                'code' => $country->code,
                'name' => $country->name,
            ]),
        ]);
    }

    /**
     * Получить список регионов.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStates()
    {
        return response()->json([
            'data' => core()->groupedStatesByCountries(),
        ]);
    }
}
