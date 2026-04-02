<?php

namespace Webkul\PushNotification\Listeners;

use Webkul\PushNotification\Models\PushNotificationSetting;
use Webkul\PushNotification\Repositories\InPageNotificationRepository;
use Webkul\PushNotification\Repositories\PushNotificationSettingRepository;
use Webkul\PushNotification\Services\WebPushService;

class PushNotificationDispatcher
{
    /**
     * Maps event names to display labels and available placeholder variables.
     */
    protected const EVENT_MAP = [
        'checkout.order.save.after' => ['label' => 'New Order', 'section' => 'both'],
        'sales.order.cancel.after' => ['label' => 'Order Cancelled', 'section' => 'both'],
        'sales.order.update-status.after' => ['label' => 'Order Status Updated', 'section' => 'shop'],
        'sales.invoice.save.after' => ['label' => 'Invoice Created', 'section' => 'both'],
        'sales.shipment.save.after' => ['label' => 'Shipment Created', 'section' => 'both'],
        'sales.refund.save.after' => ['label' => 'Refund Created', 'section' => 'both'],
        'customer.create.after' => ['label' => 'New Customer', 'section' => 'admin'],
    ];

    public function __construct(
        protected PushNotificationSettingRepository $settingRepository,
        protected InPageNotificationRepository $inPageNotificationRepository,
        protected WebPushService $webPushService
    ) {}

    /**
     * Handle any of the registered events.
     */
    public function handle(string $eventName, mixed $payload): void
    {
        $context = $this->buildContext($eventName, $payload);

        $settings = $this->settingRepository->getActiveForEvent($eventName);

        foreach ($settings as $setting) {
            $title = $this->replacePlaceholders($setting->title, $context);
            $body = $this->replacePlaceholders($setting->body, $context);
            $url = $context['url'] ?? null;

            $this->dispatchForTarget($setting, $title, $body, $url, $context);
        }
    }

    /**
     * Dispatch the notification to the correct targets based on setting target.
     */
    protected function dispatchForTarget(
        PushNotificationSetting $setting,
        string $title,
        string $body,
        ?string $url,
        array $context
    ): void {
        if (in_array($setting->target, ['admin', 'both'])) {
            $this->webPushService->sendToAdmins($title, $body, $url);
        }

        if (in_array($setting->target, ['customer', 'both'])) {
            $customerId = $context['customer_id'] ?? null;

            if ($customerId !== null) {
                $this->webPushService->sendToCustomer($customerId, $title, $body, $url);

                $this->inPageNotificationRepository->create([
                    'customer_id' => $customerId,
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                ]);
            }
        }
    }

    /**
     * Build a context array of variables for placeholder replacement from the event payload.
     */
    protected function buildContext(string $eventName, mixed $payload): array
    {
        $context = [];

        if ($payload === null) {
            return $context;
        }

        match (true) {
            str_starts_with($eventName, 'checkout.order') || str_starts_with($eventName, 'sales.order') => $this->extractOrderContext($payload, $context),
            str_starts_with($eventName, 'sales.invoice') => $this->extractInvoiceContext($payload, $context),
            str_starts_with($eventName, 'sales.shipment') => $this->extractShipmentContext($payload, $context),
            str_starts_with($eventName, 'sales.refund') => $this->extractRefundContext($payload, $context),
            str_starts_with($eventName, 'customer.create') => $this->extractCustomerContext($payload, $context),
            default => null,
        };

        return $context;
    }

    protected function extractOrderContext(mixed $order, array &$context): void
    {
        if (! is_object($order)) {
            return;
        }

        $context['order_id'] = $order->id ?? '';
        $context['order_status'] = $order->status ?? '';
        $context['customer_name'] = trim(($order->customer_first_name ?? '').' '.($order->customer_last_name ?? ''));
        $context['customer_id'] = $order->customer_id ?? null;
        $context['url'] = route('admin.sales.orders.view', $order->id ?? 0);
    }

    protected function extractInvoiceContext(mixed $invoice, array &$context): void
    {
        if (! is_object($invoice)) {
            return;
        }

        $context['order_id'] = $invoice->order_id ?? '';
        $context['customer_id'] = $invoice->order?->customer_id ?? null;
        $context['customer_name'] = $invoice->order?->customer_full_name ?? '';
        $context['url'] = route('admin.sales.invoices.view', $invoice->id ?? 0);
    }

    protected function extractShipmentContext(mixed $shipment, array &$context): void
    {
        if (! is_object($shipment)) {
            return;
        }

        $context['order_id'] = $shipment->order_id ?? '';
        $context['customer_id'] = $shipment->order?->customer_id ?? null;
        $context['customer_name'] = $shipment->order?->customer_full_name ?? '';
        $context['url'] = route('admin.sales.shipments.view', $shipment->id ?? 0);
    }

    protected function extractRefundContext(mixed $refund, array &$context): void
    {
        if (! is_object($refund)) {
            return;
        }

        $context['order_id'] = $refund->order_id ?? '';
        $context['customer_id'] = $refund->order?->customer_id ?? null;
        $context['customer_name'] = $refund->order?->customer_full_name ?? '';
        $context['url'] = route('admin.sales.refunds.view', $refund->id ?? 0);
    }

    protected function extractCustomerContext(mixed $customer, array &$context): void
    {
        if (! is_object($customer)) {
            return;
        }

        $context['customer_name'] = $customer->name ?? trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
        $context['customer_id'] = null;
    }

    /**
     * Replace {placeholder} tokens in a string using the context array.
     */
    protected function replacePlaceholders(string $text, array $context): string
    {
        foreach ($context as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $text = str_replace('{'.$key.'}', (string) $value, $text);
            }
        }

        return $text;
    }

    /**
     * Returns the predefined event map for use in settings UI.
     *
     * @return array<string, array{label: string, section: string}>
     */
    public static function getEventMap(): array
    {
        return self::EVENT_MAP;
    }
}
