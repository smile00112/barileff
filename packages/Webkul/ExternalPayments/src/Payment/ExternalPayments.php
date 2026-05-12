<?php

namespace Webkul\ExternalPayments\Payment;

use Illuminate\Support\Facades\Storage;
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
     * {@inheritdoc}
     */
    public function getTitle()
    {
        $title = $this->getConfigData('title');

        if ($title !== null && $title !== '') {
            return $title;
        }

        return trans('external-payments::app.configuration.index.sales.payment-methods.external-payments');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $description = $this->getConfigData('description');

        if ($description !== null && $description !== '') {
            return $description;
        }

        return trans('external-payments::app.configuration.index.sales.payment-methods.external-payments-info');
    }

    /**
     * Return redirect URL to the payment page.
     */
    public function getRedirectUrl(): string
    {
        return route('external-payments.redirect');
    }

    /**
     * Payment method logo. Falls back to a shared SVG placeholder when no image is uploaded.
     */
    public function getImage(): string
    {
        $url = $this->getConfigData('image');

        return $url
            ? Storage::url($url)
            : bagisto_asset('images/payment-method-placeholder.svg', 'shop');
    }

    /**
     * Check if payment method is available for the current inventory source.
     */
    public function isAvailable(): bool
    {
        $activeGlobal = $this->getConfigData('active');

        if (
            $activeGlobal !== null
            && $activeGlobal !== ''
            && ! filter_var($activeGlobal, FILTER_VALIDATE_BOOLEAN)
        ) {
            return false;
        }

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
