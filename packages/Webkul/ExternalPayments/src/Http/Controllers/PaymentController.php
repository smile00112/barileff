<?php

namespace Webkul\ExternalPayments\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\ExternalPayments\Models\InventorySourceConfig;
use Webkul\ExternalPayments\Repositories\InventorySourceConfigRepository;
use Webkul\ExternalPayments\Services\ApiClient;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shop\Mail\Customer\AccountCreatedNotification;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InventorySourceConfigRepository $configRepository,
        protected CustomerRepository $customerRepository,
    ) {}

    /**
     * Initiate payment: create the order, call external API, redirect to payment URL.
     */
    public function redirect(): RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', trans('external-payments::app.payment.cart-not-found'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $sourceId = getCurrentInventorySourceId();
        $sourceConfig = $sourceId
            ? $this->configRepository->findOneWhere(['inventory_source_id' => $sourceId, 'active' => true])
            : null;

        if (! $sourceConfig || empty($sourceConfig->api_server_url) || empty($sourceConfig->api_token)) {
            session()->flash('error', trans('external-payments::app.payment.misconfigured'));

            return redirect()->route('shop.checkout.cart.index');
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        try {
            $apiClient = $this->makeApiClient($sourceConfig);

            $billingAddress = $order->billing_address;

            $clientName = trim(
                ($billingAddress->first_name ?? '').' '.($billingAddress->last_name ?? '')
            );

            if (empty($clientName)) {
                $clientName = $billingAddress->company_name ?? '';
            }

            $productName = $order->items
                ->pluck('name')
                ->filter()
                ->implode(', ');

            if (empty($productName)) {
                $productName = trans('external-payments::app.payment.order-label').' #'.$order->id;
            }

            $payload = [
                'amount' => (float) $order->grand_total,
                'client_name' => $clientName,
                'client_email' => $billingAddress->email ?? $order->customer_email ?? '',
                'client_phone' => $billingAddress->phone ?? '',
                'external_order_id' => (string) $order->id,
                'product_name' => $productName,
            ];

            $result = $apiClient->createPayment($payload);

        } catch (\RuntimeException $e) {
            Log::error('ExternalPayments: createPayment failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            session()->flash('error', $e->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        }

        $additionalData = ['inventory_source_id' => $sourceId];

        if (isset($result['payment_id'])) {
            $additionalData['payment_id'] = $result['payment_id'];
        }

        $order->payment->update([
            'additional' => $additionalData,
        ]);

        session()->put('external_payment_order_id', $order->id);

        return redirect($result['payment_url']);
    }

    /**
     * Handle successful return from the payment gateway.
     */
    public function success(): RedirectResponse
    {
        $orderId = session()->pull('external_payment_order_id');

        if ($orderId) {
            session()->flash('order_id', $orderId);

            if (! auth()->guard('customer')->check()) {
                $order = $this->orderRepository->find($orderId);

                if ($order && ! Customer::where('email', $order->customer_email)->exists()) {
                    $result = $this->customerRepository->createFromGuestCheckout($order);

                    auth()->guard('customer')->login($result['customer']);

                    Mail::queue(new AccountCreatedNotification($result['customer'], $result['password']));
                }
            }
        }

        return redirect()->route('shop.checkout.onepage.success');
    }

    /**
     * Handle cancelled payment return.
     */
    public function cancel(): RedirectResponse
    {
        session()->flash('error', trans('external-payments::app.payment.cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Build ApiClient from the inventory source config.
     */
    private function makeApiClient(InventorySourceConfig $sourceConfig): ApiClient
    {
        return new ApiClient($sourceConfig->api_server_url, $sourceConfig->api_token);
    }
}
