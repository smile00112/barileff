<?php

namespace Webkul\Sales\Services;

use Webkul\Sales\Models\Order;

readonly class TransitionResult
{
    public function __construct(
        public bool $success,
        public Order $order,
        public ?string $message = null,
        public array $errors = [],
    ) {}

    public static function success(Order $order, ?string $message = null): self
    {
        return new self(success: true, order: $order, message: $message);
    }

    public static function failure(Order $order, string $message, array $errors = []): self
    {
        return new self(success: false, order: $order, message: $message, errors: $errors);
    }
}
