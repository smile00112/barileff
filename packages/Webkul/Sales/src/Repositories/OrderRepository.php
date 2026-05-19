<?php

namespace Webkul\Sales\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductCustomizableOptionRepository;
use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Generators\OrderSequencer;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\Shipment;
use Webkul\Sales\Services\OrderStatusTransitionService;
use Webkul\Sales\Services\TransitionContext;

class OrderRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderItemRepository $orderItemRepository,
        protected ShipmentItemRepository $shipmentItemRepository,
        protected ProductCustomizableOptionRepository $productCustomizableOptionRepository,
        protected DownloadableLinkPurchasedRepository $downloadableLinkPurchasedRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderContract::class;
    }

    /**
     * This method will try attempt to a create order.
     *
     * @return OrderContract
     */
    public function createOrderIfNotThenRetry(array $data, int $attempt = 1)
    {
        DB::beginTransaction();

        try {
            Event::dispatch('checkout.order.save.before', [$data]);

            $data['status'] = app(OrderWorkflowSettingRepository::class)
                ->getValue('new_order_status', Order::STATUS_PENDING);

            $order = $this->model->create(array_merge($data, ['increment_id' => $this->generateIncrementId()]));

            $order->payment()->create($data['payment']);

            if (isset($data['shipping_address'])) {
                $order->addresses()->create($data['shipping_address']);
            }

            $order->addresses()->create($data['billing_address']);

            foreach ($data['items'] as $item) {
                Event::dispatch('checkout.order.orderitem.save.before', $item);

                $orderItem = $this->orderItemRepository->create(array_merge($item, ['order_id' => $order->id]));

                if (! empty($item['children'])) {
                    foreach ($item['children'] as $child) {
                        $this->orderItemRepository->create(array_merge($child, ['order_id' => $order->id, 'parent_id' => $orderItem->id]));
                    }
                }

                $this->orderItemRepository->manageInventory($orderItem);

                $this->orderItemRepository->manageCustomizableOptions($orderItem);

                $this->downloadableLinkPurchasedRepository->saveLinks($orderItem, 'available');

                Event::dispatch('checkout.order.orderitem.save.after', $orderItem);
            }

            Event::dispatch('checkout.order.save.after', $order);

            $this->attachInventorySource($order);
        } catch (\Exception $e) {
            /* rolling back first */
            DB::rollBack();

            /* storing log for errors */
            Log::error(
                'OrderRepository:createOrderIfNotThenRetry: '.$e->getMessage(),
                ['data' => $data, 'attempt' => $attempt]
            );

            if ($attempt >= 3) {
                throw $e;
            }

            /* recalling */
            return $this->createOrderIfNotThenRetry($data, $attempt + 1);
        } finally {
            /* commit in each case */
            DB::commit();
        }

        return $order;
    }

    /**
     * Create order.
     *
     * @return OrderContract
     */
    public function create(array $data)
    {
        return $this->createOrderIfNotThenRetry($data);
    }

    /**
     * Cancel order. This method should be independent as admin also can cancel the order.
     *
     * @param  Order|int  $orderOrId
     * @return bool
     */
    public function cancel($orderOrId)
    {
        /* order */
        $order = $this->resolveOrderInstance($orderOrId);

        /* check wether order can be cancelled or not */
        if (! $order->canCancel()) {
            return false;
        }

        Event::dispatch('sales.order.cancel.before', $order);

        foreach ($order->items as $item) {
            if (! $item->qty_to_cancel) {
                continue;
            }

            $orderItems = [];

            if ($item->getTypeInstance()->isComposite()) {
                foreach ($item->children as $child) {
                    $orderItems[] = $child;
                }
            } else {
                $orderItems[] = $item;
            }

            foreach ($orderItems as $orderItem) {
                $this->orderItemRepository->returnQtyToProductInventory($orderItem);

                if ($orderItem->qty_ordered) {
                    $orderItem->qty_canceled += $orderItem->qty_to_cancel;
                    $orderItem->save();

                    if (
                        $orderItem->parent
                        && $orderItem->parent->qty_ordered
                    ) {
                        $orderItem->parent->qty_canceled += $orderItem->parent->qty_to_cancel;
                        $orderItem->parent->save();
                    }
                } else {
                    $orderItem->parent->qty_canceled += $orderItem->parent->qty_to_cancel;
                    $orderItem->parent->save();
                }
            }

            $this->downloadableLinkPurchasedRepository->updateStatus($item, 'expired');
        }

        $this->updateOrderStatus($order);

        Event::dispatch('sales.order.cancel.after', $order);

        return true;
    }

    /**
     * Generate increment id.
     *
     * @return int
     */
    public function generateIncrementId()
    {
        return app(OrderSequencer::class)->resolveGeneratorClass();
    }

    /**
     * Is order in completed state.
     *
     * @param  OrderContract  $order
     * @return bool
     */
    public function isInCompletedState($order)
    {
        $totalQtyOrdered = $totalQtyInvoiced = $totalQtyShipped = $totalQtyRefunded = $totalQtyCanceled = 0;

        foreach ($order->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyInvoiced += $item->qty_invoiced;

            if (! $item->isStockable()) {
                $totalQtyShipped += $item->qty_invoiced;
            } else {
                $totalQtyShipped += $item->qty_shipped;
            }

            $totalQtyRefunded += $item->qty_refunded;
            $totalQtyCanceled += $item->qty_canceled;
        }

        /**
         * Canceled state.
         */
        if ($totalQtyOrdered === $totalQtyCanceled) {
            return false;
        }

        /**
         * Closed state.
         */
        if ($totalQtyOrdered === $totalQtyRefunded + $totalQtyCanceled) {
            return false;
        }

        /**
         * Completed state.
         */
        if (
            $totalQtyOrdered === $totalQtyInvoiced + $totalQtyCanceled
        ) {
            if ($totalQtyInvoiced === $totalQtyShipped) {
                return $totalQtyOrdered === $totalQtyShipped + $totalQtyCanceled;
            }

            return $totalQtyOrdered === $totalQtyShipped + $totalQtyRefunded;
        }

        /**
         * If order is already completed and total quantity ordered is not equal to refunded
         * then it can be considered as completed.
         */
        if (
            $order->status === Order::STATUS_COMPLETED
            && $totalQtyOrdered != $totalQtyRefunded
        ) {
            return true;
        }

        return false;
    }

    /**
     * Is order in cancelled state.
     *
     * @param  OrderContract  $order
     * @return bool
     */
    public function isInCanceledState($order)
    {
        $totalQtyOrdered = $totalQtyCanceled = 0;

        foreach ($order->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyCanceled += $item->qty_canceled;
        }

        return $totalQtyOrdered === $totalQtyCanceled;
    }

    /**
     * Is order in closed state.
     *
     * @param  mixed  $order
     * @return bool
     */
    public function isInClosedState($order)
    {
        $totalQtyOrdered = $totalQtyRefunded = $totalQtyCanceled = 0;

        foreach ($order->items()->get() as $item) {
            $totalQtyOrdered += $item->qty_ordered;
            $totalQtyRefunded += $item->qty_refunded;
            $totalQtyCanceled += $item->qty_canceled;
        }

        return $totalQtyOrdered === $totalQtyRefunded + $totalQtyCanceled;
    }

    /**
     * Update order status.
     *
     * @param  OrderContract  $order
     * @param  string  $orderState
     * @return void
     */
    public function updateOrderStatus($order, $orderState = null)
    {
        Event::dispatch('sales.order.update-status.before', $order);

        if (! empty($orderState)) {
            $status = $orderState;
        } else {
            $status = Order::STATUS_PROCESSING;

            if ($this->isInCompletedState($order)) {
                $status = Order::STATUS_COMPLETED;
            }

            if ($this->isInCanceledState($order)) {
                $status = Order::STATUS_CANCELED;
            } elseif ($this->isInClosedState($order)) {
                $status = Order::STATUS_CLOSED;
            }
        }

        app(OrderStatusTransitionService::class)->transition(
            $order,
            $status,
            TransitionContext::forSystem('order-lifecycle')
        );
    }

    /**
     * Collect totals.
     *
     * @param  OrderContract  $order
     * @return mixed
     */
    public function collectTotals($order)
    {
        // order invoice total
        $order->sub_total_invoiced = $order->base_sub_total_invoiced = 0;
        $order->shipping_invoiced = $order->base_shipping_invoiced = 0;
        $order->tax_amount_invoiced = $order->base_tax_amount_invoiced = 0;
        $order->discount_invoiced = $order->base_discount_invoiced = 0;

        foreach ($order->invoices as $invoice) {
            $order->sub_total_invoiced += $invoice->sub_total;
            $order->base_sub_total_invoiced += $invoice->base_sub_total;

            $order->shipping_invoiced += $invoice->shipping_amount;
            $order->base_shipping_invoiced += $invoice->base_shipping_amount;

            $order->tax_amount_invoiced += $invoice->tax_amount;
            $order->base_tax_amount_invoiced += $invoice->base_tax_amount;

            $order->discount_invoiced += $invoice->discount_amount;
            $order->base_discount_invoiced += $invoice->base_discount_amount;
        }

        $order->grand_total_invoiced = $order->sub_total_invoiced + $order->shipping_invoiced + $order->tax_amount_invoiced - $order->discount_invoiced;
        $order->base_grand_total_invoiced = $order->base_sub_total_invoiced + $order->base_shipping_invoiced + $order->base_tax_amount_invoiced - $order->base_discount_invoiced;

        // order refund total
        $order->sub_total_refunded = $order->base_sub_total_refunded = 0;
        $order->shipping_refunded = $order->base_shipping_refunded = 0;
        $order->tax_amount_refunded = $order->base_tax_amount_refunded = 0;
        $order->discount_refunded = $order->base_discount_refunded = 0;
        $order->grand_total_refunded = $order->base_grand_total_refunded = 0;

        foreach ($order->refunds as $refund) {
            $order->sub_total_refunded += $refund->sub_total;
            $order->base_sub_total_refunded += $refund->base_sub_total;

            $order->shipping_refunded += $refund->shipping_amount;
            $order->base_shipping_refunded += $refund->base_shipping_amount;

            $order->tax_amount_refunded += $refund->tax_amount;
            $order->base_tax_amount_refunded += $refund->base_tax_amount;

            $order->discount_refunded += $refund->discount_amount;
            $order->base_discount_refunded += $refund->base_discount_amount;

            $order->grand_total_refunded += $refund->adjustment_refund - $refund->adjustment_fee;
            $order->base_grand_total_refunded += $refund->base_adjustment_refund - $refund->base_adjustment_fee;
        }

        $order->grand_total_refunded += $order->sub_total_refunded + $order->shipping_refunded + $order->tax_amount_refunded - $order->discount_refunded;
        $order->base_grand_total_refunded += $order->base_sub_total_refunded + $order->base_shipping_refunded + $order->base_tax_amount_refunded - $order->base_discount_refunded;

        $order->save();

        return $order;
    }

    /**
     * Copy inventory_source_id from the cart to the order and deduct stock.
     */
    private function attachInventorySource(Order $order): void
    {
        $cart = $order->cart;

        if (! $cart || ! $cart->inventory_source_id) {
            return;
        }

        $order->inventory_source_id = $cart->inventory_source_id;
        $order->save();

        $this->createSystemShipment($order);
    }

    /**
     * Create a hidden system shipment that deducts inventory at order creation time.
     * No ShipmentItem records are created — qty_shipped on order items stays 0.
     * Inventory is deducted directly via ShipmentItemRepository::updateProductInventory().
     */
    private function createSystemShipment(Order $order): void
    {
        $inventorySource = $order->inventory_source;

        if (! $inventorySource) {
            return;
        }

        $address = $order->shipping_address ?? $order->billing_address;

        if (! $address) {
            return;
        }

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'total_qty' => 0,
            'total_weight' => 0,
            'carrier_title' => null,
            'track_number' => null,
            'customer_id' => $order->customer_id,
            'customer_type' => $order->customer_type,
            'order_address_id' => $address->id,
            'inventory_source_id' => $inventorySource->id,
            'inventory_source_name' => $inventorySource->name,
            'is_system' => true,
        ]);

        foreach ($order->items as $orderItem) {
            if ($orderItem->getTypeInstance()->isComposite()) {
                foreach ($orderItem->children as $child) {
                    if (! $child->product?->manage_stock) {
                        continue;
                    }

                    $qty = $child->qty_ordered ?: $orderItem->qty_ordered;

                    $this->shipmentItemRepository->updateProductInventory([
                        'shipment' => $shipment,
                        'product' => $child->product,
                        'qty' => $qty,
                        'vendor_id' => 0,
                    ]);
                }
            } else {
                if (! $orderItem->product?->manage_stock) {
                    continue;
                }

                $this->shipmentItemRepository->updateProductInventory([
                    'shipment' => $shipment,
                    'product' => $orderItem->product,
                    'qty' => $orderItem->qty_ordered,
                    'vendor_id' => 0,
                ]);
            }
        }
    }

    /**
     * This method will find order if id is given else pass the order as it is.
     *
     * @param  Order|int  $orderOrId
     * @return OrderContract
     */
    protected function resolveOrderInstance($orderOrId)
    {
        return $orderOrId instanceof Order
            ? $orderOrId
            : $this->findOrFail($orderOrId);
    }
}
