<?php

namespace Webkul\Admin\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\Admin\Listeners\Admin;
use Webkul\Admin\Listeners\Category;
use Webkul\Admin\Listeners\Customer;
use Webkul\Admin\Listeners\GDPR;
use Webkul\Admin\Listeners\Invoice;
use Webkul\Admin\Listeners\Order;
use Webkul\Admin\Listeners\Refund;
use Webkul\Admin\Listeners\Shipment;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'catalog.product.create.after' => [
            [Category::class, 'afterProductCreated'],
        ],

        'catalog.product.update.before' => [
            [Category::class, 'beforeProductUpdated'],
        ],

        'catalog.product.update.after' => [
            [Category::class, 'afterProductUpdated'],
        ],

        'catalog.product.delete.before' => [
            [Category::class, 'beforeProductDeleted'],
        ],

        'customer.create.after' => [
            [Customer::class, 'afterCreated'],
        ],

        'customer.gdpr-request.create.after' => [
            [GDPR::class, 'afterGdprRequestCreated'],
        ],

        'customer.gdpr-request.update.after' => [
            [GDPR::class, 'afterGdprRequestUpdated'],
        ],

        'admin.password.update.after' => [
            [Admin::class, 'afterPasswordUpdated'],
        ],

        'checkout.order.save.after' => [
            [Order::class, 'afterCreated'],
        ],

        'sales.order.cancel.after' => [
            [Order::class, 'afterCanceled'],
        ],

        'sales.invoice.save.after' => [
            [Invoice::class, 'afterCreated'],
        ],

        'sales.shipment.save.after' => [
            [Shipment::class, 'afterCreated'],
        ],

        'sales.refund.save.after' => [
            [Refund::class, 'afterCreated'],
        ],
    ];
}
