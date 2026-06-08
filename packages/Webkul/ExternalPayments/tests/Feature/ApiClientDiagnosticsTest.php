<?php

use Illuminate\Support\Facades\Http;
use Webkul\ExternalPayments\Exceptions\ApiRequestException;
use Webkul\ExternalPayments\Services\ApiClient;

it('keeps diagnostic context when payment creation fails', function () {
    Http::fake([
        'https://payment.example.com/api/external-payments/create' => Http::response([
            'message' => 'Gateway failed',
            'error' => 'Invalid shop token',
        ], 500),
    ]);

    $payload = [
        'amount' => 123.45,
        'client_name' => 'Ivan Petrov',
        'client_email' => 'ivan@example.com',
        'client_phone' => '+79990000000',
        'external_order_id' => '24',
        'product_name' => 'Test product',
    ];

    try {
        (new ApiClient('https://payment.example.com', 'secret-token'))->createPayment($payload);
    } catch (ApiRequestException $exception) {
        expect($exception->getMessage())->toBe('Gateway failed')
            ->and($exception->getCode())->toBe(500)
            ->and($exception->context())->toMatchArray([
                'url' => 'https://payment.example.com/api/external-payments/create',
                'method' => 'POST',
                'status' => 500,
                'request_payload' => $payload,
                'response_json' => [
                    'message' => 'Gateway failed',
                    'error' => 'Invalid shop token',
                ],
            ])
            ->and($exception->context())->not->toHaveKey('token');

        return;
    }

    $this->fail(ApiRequestException::class.' was not thrown.');
});
