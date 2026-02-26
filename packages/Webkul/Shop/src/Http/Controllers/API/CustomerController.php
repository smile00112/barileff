<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Event;
use Webkul\Shop\Http\Requests\Customer\LoginRequest;

/**
 * Customer authentication (login).
 *
 * @group Customer Auth
 */
class CustomerController extends APIController
{
    /**
     * Login Customer.
     *
     * Authenticates the customer. On success, session cookies are set for use with protected endpoints.
     *
     * @bodyParam email string required Customer email. Example: customer@example.com
     * @bodyParam password string required Customer password (min 6 chars). Example: password123
     *
     * @response 200 {}
     * @response 403 scenario="Invalid credentials" {"message": "These credentials do not match our records."}
     * @response 403 scenario="Account not activated" {"message": "Your account is not activated."}
     * @response 403 scenario="Email not verified" {"message": "Please verify your email first."}
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
         * Event passed to prepare cart after login.
         */
        Event::dispatch('customer.after.login', auth()->guard()->user());

        return response()->json([]);
    }
}
