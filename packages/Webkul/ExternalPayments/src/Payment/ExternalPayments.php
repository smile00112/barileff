<?php

namespace Webkul\ExternalPayments\Payment;

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
     * Check if payment method is available.
     */
    public function isAvailable(): bool
    {
        if (! $this->getConfigData('active')) {
            return false;
        }

        if (! $this->getConfigData('api_server_url') || ! $this->getConfigData('api_token')) {
            return false;
        }

        return true;
    }
}
