<?php

namespace Webkul\ExternalPayments\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Webkul\ExternalPayments\Exceptions\ApiRequestException;

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
        $url = $this->serverUrl.'/api/external-payments/create';

        $response = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->post($url, $data);

        if ($response->status() !== 201) {
            $message = $response->json('message') ?? trans('external-payments::app.payment.create-failed');

            throw new ApiRequestException(
                $message,
                $response->status(),
                $this->buildRequestContext('POST', $url, $data, $response),
            );
        }

        $result = $response->json();

        if (empty($result['success']) || empty($result['payment_url'])) {
            throw new ApiRequestException(
                $result['message'] ?? trans('external-payments::app.payment.create-failed'),
                $response->status(),
                $this->buildRequestContext('POST', $url, $data, $response),
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
        $url = $this->serverUrl.'/api/tochka-payment/payments/'.$paymentId.'/status';

        $response = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->get($url);

        if ($response->status() !== 200) {
            $message = $response->json('message') ?? trans('external-payments::app.payment.status-failed');

            throw new ApiRequestException(
                $message,
                $response->status(),
                $this->buildRequestContext('GET', $url, ['payment_id' => $paymentId], $response),
            );
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildRequestContext(string $method, string $url, array $payload, Response $response): array
    {
        $responseJson = $response->json();
        $context = [
            'url' => $url,
            'method' => $method,
            'status' => $response->status(),
            'request_payload' => $payload,
        ];

        if (is_array($responseJson)) {
            $context['response_json'] = $responseJson;
        } else {
            $context['response_body'] = mb_substr($response->body(), 0, 2000);
        }

        return $context;
    }
}
