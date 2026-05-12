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
        return filter_var($this->getConfigData('active'), FILTER_VALIDATE_BOOLEAN);
    }
}
