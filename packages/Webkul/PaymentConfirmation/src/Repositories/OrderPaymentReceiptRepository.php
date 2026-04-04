<?php

namespace Webkul\PaymentConfirmation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PaymentConfirmation\Models\OrderPaymentReceipt;

class OrderPaymentReceiptRepository extends Repository
{
    public function model(): string
    {
        return OrderPaymentReceipt::class;
    }
}
