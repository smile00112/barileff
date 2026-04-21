<?php

namespace Webkul\ExternalPayments\Payment;

use Webkul\ExternalPayments\Repositories\InventorySourceConfigRepository;
use Webkul\Payment\Payment\Payment;

class ExternalPayments extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'external_payments';

    /**
     * Return redirect URL to the payment page.
     */
    public function getRedirectUrl(): string
    {
        return route('external-payments.redirect');
    }

    /**
     * Check if payment method is available for the current inventory source.
     */
    public function isAvailable(): bool
    {
        $sourceId = getCurrentInventorySourceId();

        if (! $sourceId) {
            return false;
        }

        /** @var InventorySourceConfigRepository $configRepo */
        $configRepo = app(InventorySourceConfigRepository::class);

        $config = $configRepo->findOneWhere([
            'inventory_source_id' => $sourceId,
            'active' => true,
        ]);

        return $config !== null
            && ! empty($config->api_server_url)
            && ! empty($config->api_token);
    }
}
