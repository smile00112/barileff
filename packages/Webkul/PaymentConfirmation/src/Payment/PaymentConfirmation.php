<?php

namespace Webkul\PaymentConfirmation\Payment;

use Webkul\Payment\Payment\Payment;

class PaymentConfirmation extends Payment
{
    /**
     * Payment method code.
     */
    protected $code = 'paymentconfirmation';

    /**
     * No external redirect needed.
     */
    public function getRedirectUrl(): string
    {
        return '';
    }

    /**
     * Available when active in config.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->getConfigData('active');
    }
}
