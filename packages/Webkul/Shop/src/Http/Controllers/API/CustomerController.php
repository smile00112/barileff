<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Webkul\Shop\Http\Requests\Customer\LoginRequest;

/**
 * Аутентификация покупателя (вход).
 *
 * @group Авторизация покупателя
 */
class CustomerController extends APIController
{
    /**
     * Выполнить вход покупателя.
     *
     * Аутентифицирует покупателя. При успехе выставляются session-cookie
     * для доступа к защищенным endpoint-ам.
     *
     * @bodyParam email string required Email покупателя. Example: customer@example.com
     * @bodyParam password string required Пароль покупателя (минимум 6 символов). Example: password123
     *
     * @response 200 {}
     * @response 403 scenario="Неверные учетные данные" {"message": "Эти учетные данные не совпадают с нашими записями."}
     * @response 403 scenario="Аккаунт не активирован" {"message": "Ваш аккаунт не активирован."}
     * @response 403 scenario="Email не подтвержден" {"message": "Сначала подтвердите ваш email."}
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        if (! auth()->guard('customer')->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'message' => trans('shop::app.customers.login-form.invalid-credentials'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->status) {
            auth()->guard('customer')->logout();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.not-activated'),
            ], Response::HTTP_FORBIDDEN);
        }

        if (! auth()->guard('customer')->user()->is_verified) {
            Cookie::queue(Cookie::make('enable-resend', 'true', 1));

            Cookie::queue(Cookie::make('email-for-resend', $request->get('email'), 1));

            auth()->guard('customer')->logout();

            return response()->json([
                'message' => trans('shop::app.customers.login-form.verify-first'),
            ], Response::HTTP_FORBIDDEN);
        }

        /**
         * Событие для подготовки корзины после входа.
         */
        Event::dispatch('customer.after.login', auth()->guard()->user());

        return response()->json([]);
    }
}
