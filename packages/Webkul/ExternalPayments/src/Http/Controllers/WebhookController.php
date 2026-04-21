<?php

namespace Webkul\ExternalPayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\ExternalPayments\Models\InventorySourceConfig;
use Webkul\ExternalPayments\Repositories\InventorySourceConfigRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class WebhookController extends Controller
{
    /**
     * Payment statuses treated as successful payment.
     *
     * @var array<string>
     */
    private const PAID_STATUSES = ['paid', 'completed', 'approved', 'processing'];

    /**
     * Payment statuses treated as failed/declined payment.
     *
     * @var array<string>
     */
    private const FAILED_STATUSES = ['failed', 'cancelled', 'declined'];

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected InventorySourceConfigRepository $configRepository,
    ) {}

    /**
     * Handle incoming webhook from the external payment API.
     *
     * Expected payload:
     *   { order_id: int, payment_status: string }
     */
    public function handle(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        if (empty($data['order_id']) || empty($data['payment_status'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $order = $this->orderRepository->find((int) $data['order_id']);

        if (! $order || $order->payment->method !== 'external_payments') {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $sourceConfig = $this->resolveSourceConfig($order);

        if (! $this->authorizeRequest($request, $sourceConfig)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $status = strtolower((string) $data['payment_status']);

        if (in_array($status, self::PAID_STATUSES, true)) {
            $this->markAsPaid($order, $sourceConfig);
        } elseif (in_array($status, self::FAILED_STATUSES, true)) {
            $this->orderRepository->update(['status' => Order::STATUS_CANCELED], $order->id);
        }

        return response()->json(['success' => true, 'message' => 'OK'], 200);
    }

    /**
     * Resolve the inventory source config for a given order.
     *
     * Falls back to searching by Bearer token across all active configs.
     */
    private function resolveSourceConfig(Order $order): ?InventorySourceConfig
    {
        $sourceId = $order->payment->additional['inventory_source_id'] ?? null;

        if ($sourceId) {
            return $this->configRepository->findOneWhere(['inventory_source_id' => (int) $sourceId]);
        }

        return null;
    }

    /**
     * Verify the incoming request carries a valid Bearer token.
     *
     * If no config is found (legacy order or missing source), all requests with any
     * matching active config token are accepted. If no api_token is configured, the
     * request is accepted (development mode).
     */
    private function authorizeRequest(Request $request, ?InventorySourceConfig $sourceConfig): bool
    {
        $authHeader = $request->header('Authorization', '');

        if ($sourceConfig) {
            if (empty($sourceConfig->api_token)) {
                return true;
            }

            return $authHeader === 'Bearer '.$sourceConfig->api_token;
        }

        // Fallback: accept if any active config matches the bearer token
        $token = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : null;

        if (! $token) {
            // No token provided and no specific source config — allow only if there are no configured tokens
            return ! $this->configRepository->findOneByField('api_token', '!=', null, ['active' => true]);
        }

        return (bool) $this->configRepository->findOneWhere(['api_token' => $token, 'active' => true]);
    }

    /**
     * Set the order to the configured paid status and create an invoice.
     */
    private function markAsPaid(Order $order, ?InventorySourceConfig $sourceConfig): void
    {
        $paidStatus = $sourceConfig?->paid_order_status ?: Order::STATUS_PROCESSING;

        if ($paidStatus !== Order::STATUS_PROCESSING) {
            $this->orderRepository->update(['status' => Order::STATUS_PROCESSING], $order->id);
        }

        $this->orderRepository->update(['status' => $paidStatus], $order->id);

        $order->refresh();

        if ($order->canInvoice()) {
            $invoiceData = ['order_id' => $order->id];

            foreach ($order->items as $item) {
                $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
            }

            $this->invoiceRepository->create($invoiceData);
        }
    }
}
