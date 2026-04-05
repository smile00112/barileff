<?php

namespace Webkul\PaymentConfirmation\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\PaymentConfirmation\Models\PaymentDetail;

class PaymentDetailRepository extends Repository
{
    public function model(): string
    {
        return PaymentDetail::class;
    }
}
