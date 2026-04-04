<?php

namespace Webkul\PaymentConfirmation\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class OrderReceiptController extends Controller
{
    public function approve(int $orderId): RedirectResponse
    {
        // Will be implemented in Task 8
        return back();
    }
}
