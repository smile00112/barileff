<?php

namespace Webkul\PaymentConfirmation\Payment;

use Illuminate\Support\Facades\Storage;
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
}
