<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Sales\UpdateOrderItemRequest;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;

class OrderItemController extends Controller
{
    public function __construct(
        private readonly OrderItemRepository $orderItemRepository,
        private readonly OrderRepository $orderRepository,
    ) {}

    /**
     * Update the quantity of an order item.
     */
    public function update(UpdateOrderItemRequest $request, int $id): JsonResponse
    {
        $orderItem = $this->orderItemRepository->findOrFail($id);

        $newQty = (int) $request->input('quantity');
        $delta = $newQty - (int) $orderItem->qty_ordered;

        if ($delta !== 0) {
            $this->orderItemRepository->adjustInventory($orderItem, $delta);
        }

        $this->orderItemRepository->update(['qty_ordered' => $newQty], $id);

        $order = $orderItem->order;
        $this->orderRepository->collectTotals($order);

        return new JsonResponse([
            'message' => trans('admin::app.sales.orders.view.item-updated'),
        ]);
    }

    /**
     * Remove an item from the order and return its qty to inventory.
     */
    public function destroy(int $id): JsonResponse
    {
        $orderItem = $this->orderItemRepository->findOrFail($id);

        $this->orderItemRepository->adjustInventory($orderItem, -((int) $orderItem->qty_ordered));

        $order = $orderItem->order;

        $orderItem->delete();

        $this->orderRepository->collectTotals($order);

        return new JsonResponse([
            'message' => trans('admin::app.sales.orders.view.item-deleted'),
        ]);
    }
}
