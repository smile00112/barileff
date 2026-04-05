<?php

namespace Webkul\PaymentConfirmation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Sales\Models\Order;

class OrderPaymentReceipt extends Model
{
    protected $table = 'order_payment_confirmation_receipts';

    protected $fillable = [
        'order_id',
        'payment_detail_id',
        'instructions_snapshot',
        'receipt_path',
        'receipt_original_name',
    ];

    protected $appends = ['receipt_url'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentDetail(): BelongsTo
    {
        return $this->belongsTo(PaymentDetail::class, 'payment_detail_id');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path ? Storage::url($this->receipt_path) : null;
    }

    public function hasReceipt(): bool
    {
        return $this->receipt_path !== null;
    }
}
