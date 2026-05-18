<?php

namespace Webkul\PaymentConfirmation\Payment;

use Illuminate\Support\Facades\Storage;
use Webkul\Payment\Payment\Payment;
use Webkul\PaymentConfirmation\Models\PaymentDetail;

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
     * Available only when an active payment detail exists for the current inventory source.
     */
    public function isAvailable(): bool
    {
        $sourceId = getCurrentInventorySourceId();

        if (! $sourceId) {
            return false;
        }

        return PaymentDetail::where('inventory_source_id', $sourceId)
            ->where('is_active', true)
            ->exists();
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
