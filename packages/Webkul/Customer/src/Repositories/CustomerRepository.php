<?php

namespace Webkul\Customer\Repositories;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Models\CustomerGroup;
use Webkul\Sales\Models\Order;

class CustomerRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Customer\Contracts\Customer';
    }

    /**
     * Check if customer has order pending or processing.
     *
     * @param  Customer
     * @return bool
     */
    public function haveActiveOrders($customer)
    {
        return $customer->orders->pluck('status')->contains(function ($val) {
            return $val === 'pending' || $val === 'processing';
        });
    }

    /**
     * Returns current customer group
     *
     * @return CustomerGroup
     */
    public function getCurrentGroup()
    {
        $customer = auth()->guard()->user();

        return $customer->group ?? core()->getGuestCustomerGroup();
    }

    /**
     * Upload customer's images.
     *
     * @param  array  $data
     * @param  Customer  $customer
     * @param  string  $type
     * @return void
     */
    public function uploadImages($data, $customer, $type = 'image')
    {
        if (isset($data[$type])) {
            $request = request();

            foreach ($data[$type] as $imageId => $image) {
                $file = $type.'.'.$imageId;
                $dir = 'customer/'.$customer->id;

                if ($request->hasFile($file)) {
                    if ($customer->{$type}) {
                        Storage::delete($customer->{$type});
                    }

                    $customer->{$type} = $request->file($file)->store($dir);
                    $customer->save();
                }
            }
        } else {
            if ($customer->{$type}) {
                Storage::delete($customer->{$type});
            }

            $customer->{$type} = null;
            $customer->save();
        }
    }

    /**
     * Create a customer account from a guest order.
     *
     * Generates a random password, creates the customer, links historical
     * guest orders to the new account, and dispatches registration events.
     *
     * @return array{customer: \Webkul\Customer\Contracts\Customer, password: string}
     */
    public function createFromGuestCheckout(Order $order): array
    {
        $plainPassword = Str::password(16);

        $groupCode = core()->getConfigData('customer.settings.create_new_account_options.default_group');

        $group = CustomerGroup::where('code', $groupCode)->first();

        Event::dispatch('customer.registration.before');

        $customer = $this->create([
            'first_name' => $order->billing_address->first_name,
            'last_name' => $order->billing_address->last_name,
            'email' => $order->customer_email,
            'password' => bcrypt($plainPassword),
            'api_token' => Str::random(80),
            'token' => md5(uniqid(rand(), true)),
            'is_verified' => 1,
            'status' => 1,
            'customer_group_id' => $group?->id,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);

        Event::dispatch('customer.create.after', $customer);

        $this->syncNewRegisteredCustomerInformation($customer);

        Event::dispatch('customer.registration.after', $customer);

        return ['customer' => $customer, 'password' => $plainPassword];
    }

    /**
     * @param  \Webkul\Customer\Contracts\Customer  $customer
     * @return mixed
     */
    public function syncNewRegisteredCustomerInformation($customer)
    {
        /**
         * Setting registered customer to orders.
         */
        Order::where('customer_email', $customer->email)->update([
            'is_guest' => 0,
            'customer_id' => $customer->id,
            'customer_type' => Customer::class,
        ]);

        /**
         * Grabbing orders by `customer_id`.
         */
        $orders = Order::where('customer_id', $customer->id)->get();

        /**
         * Setting registered customer to associated order's relations.
         */
        $orders->each(function ($order) use ($customer) {
            $order->addresses()->update([
                'customer_id' => $customer->id,
            ]);

            $order->shipments()->update([
                'customer_id' => $customer->id,
                'customer_type' => Customer::class,
            ]);

            $order->downloadable_link_purchased()->update([
                'customer_id' => $customer->id,
            ]);
        });
    }
}
