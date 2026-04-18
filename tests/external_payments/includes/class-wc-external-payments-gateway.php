<?php

/**
 * External Payments Gateway
 */
if (! defined('ABSPATH')) {
    exit;
}

/**
 * WC_External_Payments_Gateway class
 */
class WC_External_Payments_Gateway extends WC_Payment_Gateway
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'external_payments';
        $this->icon = '';
        $this->has_fields = false;
        $this->method_title = __('External Payments', 'external-payments');
        $this->method_description = __('Оплата через внешнюю систему платежей', 'external-payments');
        $this->supports = [
            'products',
        ];

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title', __('External Payments', 'external-payments'));
        $this->description = $this->get_option('description', '');
        $this->enabled = $this->get_option('enabled', 'no');
        $this->api_server_url = $this->get_option('api_server_url', '');
        $this->api_token = $this->get_option('api_token', '');
        $this->paid_order_status = $this->get_option('paid_order_status', 'processing');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_'.$this->id, [$this, 'process_admin_options']);
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Включить/Выключить', 'external-payments'),
                'type' => 'checkbox',
                'label' => __('Включить External Payments', 'external-payments'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Название', 'external-payments'),
                'type' => 'text',
                'description' => __('Название метода оплаты, которое видит покупатель', 'external-payments'),
                'default' => __('External Payments', 'external-payments'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Описание', 'external-payments'),
                'type' => 'textarea',
                'description' => __('Описание метода оплаты, которое видит покупатель', 'external-payments'),
                'default' => __('Оплата через внешнюю систему платежей', 'external-payments'),
                'desc_tip' => true,
            ],
            'api_server_url' => [
                'title' => __('Адрес сервера API', 'external-payments'),
                'type' => 'text',
                'description' => __('URL сервера Laravel API (например: https://api.example.com)', 'external-payments'),
                'default' => '',
                'placeholder' => 'https://api.example.com',
                'desc_tip' => true,
            ],
            'api_token' => [
                'title' => __('Токен авторизации', 'external-payments'),
                'type' => 'password',
                'description' => __('Токен авторизации из модуля ExternalPayments', 'external-payments'),
                'default' => '',
                'desc_tip' => true,
            ],
            'paid_order_status' => [
                'title' => __('Статус оплаченного заказа', 'external-payments'),
                'type' => 'select',
                'description' => __('Статус заказа после успешной оплаты', 'external-payments'),
                'default' => 'processing',
                'options' => wc_get_order_statuses(),
                'desc_tip' => true,
            ],
        ];
    }

    /**
     * Process payment
     *
     * @param  int  $order_id  Order ID
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        if (! $order) {
            wc_add_notice(__('Ошибка: заказ не найден.', 'external-payments'), 'error');

            return [
                'result' => 'fail',
            ];
        }

        // Mark order as pending payment
        $order->update_status('pending', __('Ожидание оплаты через External Payments', 'external-payments'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return success with redirect to payment page
        return [
            'result' => 'success',
            'redirect' => add_query_arg(
                [
                    'order_id' => $order->get_id(),
                    'order_key' => $order->get_order_key(),
                ],
                home_url('/wc-api/wc_external_payments_payment')
            ),
        ];
    }

    /**
     * Check if the gateway is available for use
     *
     * @return bool
     */
    public function is_available()
    {
        // Always show in admin settings
        if (is_admin()) {
            return true;
        }

        if ($this->enabled !== 'yes') {
            return false;
        }

        // Check if required settings are filled
        if (empty($this->api_server_url) || empty($this->api_token)) {
            return false;
        }

        return parent::is_available();
    }
}
