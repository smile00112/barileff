<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\ManagerApp\Http\Requests\UpdateOrderStatusRequest;
use Webkul\ManagerApp\Http\Resources\OrderDetailResource;
use Webkul\ManagerApp\Http\Resources\OrderResource;
use Webkul\ManagerApp\Services\ManagerOrderService;

class OrderController extends Controller
{
    public function __construct(private readonly ManagerOrderService $service) {}

    /**
     * GET /manager/api/orders
     *
     * Returns paginated orders for the manager's warehouse(s).
     * Query params: status, search, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user('sanctum');

        $orders = $this->service->getOrdersForManager($admin, $request->only(['status', 'search', 'per_page']));

        return response()->json([
            'data'  => OrderResource::collection($orders->items()),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /manager/api/orders/{id}
     *
     * Returns full order detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $admin = $request->user('sanctum');

        $order = $this->service->findOrderForManager($admin, $id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        return response()->json(new OrderDetailResource($order));
    }

    /**
     * PATCH /manager/api/orders/{id}/status
     *
     * Update order status. Allowed: processing, completed, canceled, awaiting_confirmation.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $admin = $request->user('sanctum');

        $order = $this->service->findOrderForManager($admin, $id);

        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order = $this->service->updateStatus($order, $request->validated('status'));

        return response()->json(new OrderDetailResource($order));
    }

    /**
     * GET /manager/api/orders/statuses
     *
     * Returns allowed statuses with display labels.
     */
    public function statuses(): JsonResponse
    {
        return response()->json($this->service->getStatusLabels());
    }
}
