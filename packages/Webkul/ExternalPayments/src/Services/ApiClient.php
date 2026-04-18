<?php

namespace Webkul\ExternalPayments\Services;

use Illuminate\Support\Facades\Http;

class ApiClient
{
    /**
     * API server base URL.
     */
    private string $serverUrl;

    /**
     * Bearer authorization token.
     */
    private string $token;

    /**
     * Request timeout in seconds.
     */
    private int $timeout = 30;

    /**
     * Create a new API client instance.
     */
    public function __construct(string $serverUrl, string $token)
    {
        $this->serverUrl = rtrim($serverUrl, '/');
        $this->token = $token;
    }

    /**
     * Create a new payment on the external API.
     *
     * Expected request body:
     *   amount, client_name, client_email, client_phone, external_order_id, product_name
     *
     * Expected successful response (HTTP 201):
     *   { success: true, payment_url: "https://...", payment_id: 123 }
     *
     * @param  array{amount: float, client_name: string, client_email: string, client_phone: string, external_order_id: string, product_name: string}  $data
     * @return array{success: bool, payment_url: string, payment_id: int}
     *
     * @throws \RuntimeException
     */
    public function createPayment(array $data): array
    {
        $response = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->post($this->serverUrl.'/api/external-payments/create', $data);

        if ($response->status() !== 201) {
            $message = $response->json('message') ?? trans('external-payments::app.payment.create-failed');

            throw new \RuntimeException($message, $response->status());
        }

        $result = $response->json();

        if (empty($result['success']) || empty($result['payment_url'])) {
            throw new \RuntimeException(
                $result['message'] ?? trans('external-payments::app.payment.create-failed')
            );
        }

        return $result;
    }

    /**
     * Check payment status on the external API.
     *
     * Expected successful response (HTTP 200):
     *   { payment_status: "paid"|"pending"|"failed"|... }
     *
     * @return array{payment_status: string}
     *
     * @throws \RuntimeException
     */
    public function checkStatus(int $paymentId): array
    {
        $response = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->get($this->serverUrl.'/api/tochka-payment/payments/'.$paymentId.'/status');

        if ($response->status() !== 200) {
            $message = $response->json('message') ?? trans('external-payments::app.payment.status-failed');

            throw new \RuntimeException($message, $response->status());
        }

        return $response->json();
    }
}
