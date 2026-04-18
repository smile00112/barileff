<?php

namespace Webkul\ExternalPayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\ExternalPayments\Payment\ExternalPayments;
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
        protected ExternalPayments $paymentMethod
    ) {}

    /**
     * Handle incoming webhook from the external payment API.
     *
     * Expected payload:
     *   { order_id: int, payment_status: string }
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->authorizeRequest($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $data = $request->json()->all();

        if (empty($data['order_id']) || empty($data['payment_status'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $order = $this->orderRepository->find((int) $data['order_id']);

        if (! $order || $order->payment->method !== 'external_payments') {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $status = strtolower((string) $data['payment_status']);

        if (in_array($status, self::PAID_STATUSES, true)) {
            $this->markAsPaid($order);
        } elseif (in_array($status, self::FAILED_STATUSES, true)) {
            $this->orderRepository->update(['status' => Order::STATUS_CANCELED], $order->id);
        }

        return response()->json(['success' => true, 'message' => 'OK'], 200);
    }

    /**
     * Verify the incoming request carries a valid Bearer token.
     *
     * If no token is configured, all requests are accepted (development mode).
     */
    private function authorizeRequest(Request $request): bool
    {
        $configuredToken = $this->paymentMethod->getConfigData('api_token');

        if (empty($configuredToken)) {
            return true;
        }

        $authHeader = $request->header('Authorization', '');

        return $authHeader === 'Bearer '.$configuredToken;
    }

    /**
     * Set the order to the configured paid status and create an invoice.
     */
    private function markAsPaid(Order $order): void
    {
        $paidStatus = $this->paymentMethod->getConfigData('paid_order_status') ?: Order::STATUS_PROCESSING;

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
