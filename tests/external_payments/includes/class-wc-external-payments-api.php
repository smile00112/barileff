<?php

/**
 * External Payments API Client
 */
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WC_External_Payments_API class
 */
class WC_External_Payments_API
{
    /**
     * API server URL
     *
     * @var string
     */
    private $api_server_url;

    /**
     * API token
     *
     * @var string
     */
    private $api_token;

    /**
     * Constructor
     *
     * @param  string  $api_server_url
     * @param  string  $api_token
     */
    public function __construct($api_server_url, $api_token)
    {
        $this->api_server_url = rtrim($api_server_url, '/');
        $this->api_token = $api_token;
    }

    /**
     * Create payment
     *
     * @param  array  $data
     * @return array|WP_Error
     */
    public function create_payment($data)
    {
        $url = $this->api_server_url.'/api/external-payments/create';

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->api_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        $result = json_decode($body, true);

        if ($status_code !== 201) {
            $error_message = isset($result['message']) ? $result['message'] : __('Ошибка создания платежа', 'external-payments');

            return new WP_Error('api_error', $error_message, ['status' => $status_code]);
        }

        return $result;
    }

    /**
     * Check payment status
     *
     * @param  int  $payment_id
     * @return array|WP_Error
     */
    public function check_payment_status($payment_id)
    {
        $url = $this->api_server_url.'/api/tochka-payment/payments/'.intval($payment_id).'/status';

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->api_token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);

        $result = json_decode($body, true);

        if ($status_code !== 200) {
            $error_message = isset($result['message']) ? $result['message'] : __('Ошибка проверки статуса платежа', 'external-payments');

            return new WP_Error('api_error', $error_message, ['status' => $status_code]);
        }

        return $result;
    }
}
