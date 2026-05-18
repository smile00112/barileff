<?php

namespace Webkul\Sales\Services;

readonly class TransitionContext
{
    public function __construct(
        public string $source = 'system',
        public ?string $actorType = null,
        public ?int $actorId = null,
        public ?string $actorName = null,
        public ?string $deliveryType = null,
        public ?string $paymentType = null,
        public ?string $channel = null,
        public ?string $reason = null,
        public ?string $comment = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a context for an admin user action.
     */
    public static function forAdmin(int $userId, string $userName, ?string $channel = null): self
    {
        return new self(
            source: 'admin',
            actorType: 'admin',
            actorId: $userId,
            actorName: $userName,
            channel: $channel,
        );
    }

    /**
     * Create a context for a system/automated action.
     */
    public static function forSystem(string $reason = ''): self
    {
        return new self(source: 'system', reason: $reason);
    }

    /**
     * Create a context for a webhook/payment gateway action.
     */
    public static function forWebhook(string $gatewayName = 'webhook'): self
    {
        return new self(source: 'webhook', actorName: $gatewayName);
    }
}
