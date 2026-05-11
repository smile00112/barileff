<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\DeliveryZones\Services\CartDeliveryZoneManager;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Requests\CartAddressRequest;
use Webkul\Shop\Http\Resources\CartResource;

/**
 * One-page checkout: адреса, доставка, оплата, создание заказа.
 *
 * @group Оформление заказа (Onepage)
 */
class OnepageController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository
    ) {}

    /**
     * Получить сводку корзины.
     */
    public function summary(): JsonResource
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return new JsonResource([
                'data' => null,
            ]);
        }

        return new CartResource($cart);
    }

    /**
     * Сохранить адрес.
     */
    public function storeAddress(CartAddressRequest $cartAddressRequest): JsonResource
    {
        $params = $cartAddressRequest->all();

        if (
            ! auth()->guard('customer')->check()
            && ! Cart::getCart()->hasGuestCheckoutItems()
        ) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.customer.session.index'),
            ]);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        foreach (['billing', 'shipping'] as $addressType) {
            if (! empty($params[$addressType]['full_name'])) {
                $parts = explode(' ', trim($params[$addressType]['full_name']), 2);
                $params[$addressType]['first_name'] = $parts[0];
                $params[$addressType]['last_name'] = $parts[1] ?? $parts[0];
                unset($params[$addressType]['full_name']);
            }
        }

        Cart::saveAddresses($params);

        $cart = Cart::getCart();

        $deliveryLat = isset($params['delivery_point_lat'])
            ? (float) $params['delivery_point_lat']
            : ($cart->delivery_point_lat !== null ? (float) $cart->delivery_point_lat : null);

        $deliveryLng = isset($params['delivery_point_lng'])
            ? (float) $params['delivery_point_lng']
            : ($cart->delivery_point_lng !== null ? (float) $cart->delivery_point_lng : null);

        $deliveryZoneId = ! empty($params['delivery_zone_id'])
            ? (int) $params['delivery_zone_id']
            : ($cart->delivery_zone_id ? (int) $cart->delivery_zone_id : null);

        app(CartDeliveryZoneManager::class)->applySelection($cart, $deliveryLat, $deliveryLng, $deliveryZoneId);

        Cart::collectTotals();

        if ($cart->haveStockableItems()) {
            if (! $rates = Shipping::collectRates()) {
                return new JsonResource([
                    'redirect' => true,
                    'redirect_url' => route('shop.checkout.cart.index'),
                ]);
            }

            return new JsonResource([
                'redirect' => false,
                'data' => $rates,
            ]);
        }

        return new JsonResource([
            'redirect' => false,
            'data' => Payment::getSupportedPaymentMethods(),
        ]);
    }

    /**
     * Сохранить зону доставки вручную и пересчитать доставку.
     */
    public function storeDeliveryZone()
    {
        $validatedData = $this->validate(request(), [
            'delivery_zone_id' => 'required|integer|exists:delivery_zones,id',
        ]);

        $cart = Cart::getCart();

        if (! $cart) {
            return response()->json([
                'message' => 'Cart not found.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cart->delivery_zone_id = (int) $validatedData['delivery_zone_id'];
        $cart->delivery_zone_mode = 'manual';
        $cart->save();

        Shipping::collectRates();
        Cart::collectTotals();

        return response()->json([
            'cart' => new CartResource(Cart::getCart()),
            'shipping_methods' => array_values(Shipping::collectRates()['shippingMethods'] ?? []),
        ]);
    }

    /**
     * Сохранить способ доставки.
     *
     * @return Response
     */
    public function storeShippingMethod()
    {
        $validatedData = $this->validate(request(), [
            'shipping_method' => 'required',
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['shipping_method']
            || ! Cart::saveShippingMethod($validatedData['shipping_method'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        return response()->json(Payment::getSupportedPaymentMethods());
    }

    /**
     * Сохранить способ оплаты.
     *
     * @return array
     */
    public function storePaymentMethod()
    {
        $validatedData = $this->validate(request(), [
            'payment' => 'required',
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['payment']
            || ! Cart::savePaymentMethod($validatedData['payment'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::collectTotals();

        $cart = Cart::getCart();

        return [
            'cart' => new CartResource($cart),
        ];
    }

    /**
     * Создать заказ.
     */
    public function storeOrder()
    {
        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        $cart = Cart::getCart();

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    /**
     * Проверить заказ перед созданием.
     *
     * @return void|\Exception
     */
    public function validateOrder()
    {
        $cart = Cart::getCart();

        $minimumOrderAmount = core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (
            auth()->guard('customer')->check()
            && auth()->guard('customer')->user()->is_suspended
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.suspended-account-message'));
        }

        if (
            auth()->guard('customer')->user()
            && ! auth()->guard('customer')->user()->status
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.inactive-account-message'));
        }

        if (! Cart::haveMinimumOrderAmount()) {
            throw new \Exception(trans('shop::app.checkout.cart.minimum-order-message', ['amount' => core()->currency($minimumOrderAmount)]));
        }

        if ($cart->haveStockableItems() && ! $cart->shipping_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-shipping-address'));
        }

        if (! $cart->billing_address) {
            throw new \Exception(trans('shop::app.checkout.onepage.address.check-billing-address'));
        }

        if (
            $cart->haveStockableItems()
            && ! $cart->selected_shipping_rate
        ) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-shipping-method'));
        }

        if (! $cart->payment) {
            throw new \Exception(trans('shop::app.checkout.cart.specify-payment-method'));
        }
    }
}
