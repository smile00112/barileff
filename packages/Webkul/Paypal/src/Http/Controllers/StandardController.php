<?php

namespace Webkul\Paypal\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Paypal\Helpers\Ipn;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;
use Webkul\Shop\Mail\Customer\AccountCreatedNotification;

class StandardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected Ipn $ipnHelper,
        protected CustomerRepository $customerRepository
    ) {}

    /**
     * Redirects to the paypal.
     *
     * @return View
     */
    public function redirect()
    {
        return view('paypal::standard-redirect');
    }

    /**
     * Cancel payment from paypal.
     *
     * @return Response
     */
    public function cancel()
    {
        session()->flash('error', trans('shop::app.checkout.cart.paypal-payment-cancelled'));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Success payment.
     *
     * @return Response
     */
    public function success()
    {
        $cart = Cart::getCart();

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        if (! auth()->guard('customer')->check()) {
            if (! Customer::where('email', $order->customer_email)->exists()) {
                $result = $this->customerRepository->createFromGuestCheckout($order);

                auth()->guard('customer')->login($result['customer']);

                Mail::queue(new AccountCreatedNotification($result['customer'], $result['password']));
            }
        }

        session()->flash('order_id', $order->id);

        return redirect()->route('shop.checkout.onepage.success');
    }

    /**
     * Paypal IPN listener.
     *
     * @return Response
     */
    public function ipn()
    {
        $this->ipnHelper->processIpn(request()->all());
    }
}
