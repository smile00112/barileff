<?php
/**
 * Plugin Name: External Payments Gateway
 * Plugin URI: https://example.com/external-payments
 * Description: Интеграция с системой внешних платежей через ExternalPayments API
 * Version: 1.0.0
 * Author: Dolinger
 * Author URI: https://example.com
 * Text Domain: external-payments
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 10.0
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WC_EXTERNAL_PAYMENTS_VERSION', '1.0.0');
define('WC_EXTERNAL_PAYMENTS_PLUGIN_FILE', __FILE__);
define('WC_EXTERNAL_PAYMENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_EXTERNAL_PAYMENTS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class WC_External_Payments
{
    /**
     * Plugin instance
     *
     * @var WC_External_Payments
     */
    private static $instance = null;

    /**
     * Get plugin instance
     *
     * @return WC_External_Payments
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes()
    {
        require_once WC_EXTERNAL_PAYMENTS_PLUGIN_DIR.'includes/class-wc-external-payments-gateway.php';
        require_once WC_EXTERNAL_PAYMENTS_PLUGIN_DIR.'includes/class-wc-external-payments-api.php';
        require_once WC_EXTERNAL_PAYMENTS_PLUGIN_DIR.'includes/class-wc-external-payments-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'init_gateway'], 11);
        add_action('woocommerce_api_wc_external_payments_payment', [$this, 'handle_payment_page']);
        add_filter('woocommerce_rest_prepare_shop_order_object', [$this, 'add_payment_url_to_order'], 10, 3);
        add_action('rest_api_init', [$this, 'register_webhook_route']);
    }

    /**
     * Check if WooCommerce is active and version is compatible
     *
     * @return bool
     */
    private function is_woocommerce_compatible()
    {
        if (! class_exists('WooCommerce')) {
            return false;
        }

        // Check minimum WooCommerce version
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
            return false;
        }

        return true;
    }

    /**
     * Initialize gateway
     */
    public function init_gateway()
    {
        if (! $this->is_woocommerce_compatible()) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);

            return;
        }

        if (! class_exists('WC_Payment_Gateway')) {
            return;
        }

        add_filter('woocommerce_payment_gateways', [$this, 'add_gateway']);
    }

    /**
     * Show notice if WooCommerce is not active or version is incompatible
     */
    public function woocommerce_missing_notice()
    {
        ?>
        <div class="error">
            <p>
                <?php
                if (! class_exists('WooCommerce')) {
                    echo esc_html__('External Payments Gateway требует установленного и активированного плагина WooCommerce.', 'external-payments');
                } else {
                    echo esc_html__('External Payments Gateway требует WooCommerce версии 5.0 или выше.', 'external-payments');
                }
        ?>
            </p>
        </div>
        <?php
    }

    /**
     * Add gateway to WooCommerce
     *
     * @param  array  $gateways  Array of gateway class names
     * @return array
     */
    public function add_gateway($gateways)
    {
        // Ensure the gateway class is loaded
        if (! class_exists('WC_External_Payments_Gateway')) {
            require_once WC_EXTERNAL_PAYMENTS_PLUGIN_DIR.'includes/class-wc-external-payments-gateway.php';
        }

        $gateways[] = 'WC_External_Payments_Gateway';

        return $gateways;
    }

    /**
     * Handle payment page
     */
    public function handle_payment_page()
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $order_key = isset($_GET['order_key']) ? sanitize_text_field(wp_unslash($_GET['order_key'])) : '';

        if (! $order_id || ! $order_key) {
            wc_add_notice(__('Неверные параметры заказа.', 'external-payments'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        $order = wc_get_order($order_id);

        if (! $order || $order->get_order_key() !== $order_key) {
            wc_add_notice(__('Заказ не найден.', 'external-payments'), 'error');
            wp_safe_redirect(wc_get_checkout_url());
            exit;
        }

        // Load payment form template
        wc_get_template(
            'payment-form.php',
            [
                'order' => $order,
            ],
            '',
            WC_EXTERNAL_PAYMENTS_PLUGIN_DIR.'templates/'
        );
        exit;
    }

    /**
     * Add payment_url to order REST API response
     *
     * @param  WP_REST_Response  $response
     * @param  WC_Order  $order
     * @param  WP_REST_Request  $request
     * @return WP_REST_Response
     */
    public function add_payment_url_to_order($response, $order, $request)
    {
        if ($order->get_payment_method() === 'external_payments') {
            $payment_url = add_query_arg(
                [
                    'order_id' => $order->get_id(),
                    'order_key' => $order->get_order_key(),
                ],
                home_url('/wc-api/wc_external_payments_payment')
            );

            $response->data['payment_url'] = $payment_url;
        }

        return $response;
    }

    /**
     * Register REST API webhook route
     */
    public function register_webhook_route()
    {
        register_rest_route('wc-external-payments/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_payment_webhook'],
            'permission_callback' => '__return_true',
            'args' => [],
        ]);
    }

    /**
     * Handle payment webhook from Laravel/our server
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_webhook(WP_REST_Request $request)
    {

        $data = $request->get_json_params();

        debug_to_file('external_handle_payment_webhook '.date('Y-m-d H:i:s'));
        debug_to_file($data);

        if (! $data || empty($data['order_id'])) {
            return new WP_REST_Response(
                ['success' => false, 'message' => 'Invalid payload'],
                400
            );
        }
        try {
            $gateway = WC()->payment_gateways()->payment_gateways()['external_payments'] ?? null;
            if (! $gateway || $gateway->id !== 'external_payments') {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Gateway not configured'],
                    500
                );
            }

            // $authHeader = $request->get_header('Authorization');
            // $expectedToken = $gateway->get_option('api_token', '');
            // if (empty($expectedToken) || $authHeader !== 'Bearer ' . $expectedToken) {
            //     return new \WP_REST_Response(
            //         array('success' => false, 'message' => 'Unauthorized'),
            //         401
            //     );
            // }

            $orderId = absint($data['order_id']);
            $order = wc_get_order($orderId);

            if (! $order || $order->get_payment_method() !== 'external_payments') {
                return new WP_REST_Response(
                    ['success' => false, 'message' => 'Order not found'],
                    404
                );
            }

            $status = $data['payment_status'] ?? '';
            $paidStatus = $gateway->get_option('paid_order_status', 'processing');

            if (in_array(strtolower($status), ['paid', 'completed', 'approved', 'processing'])) {

                if ($paidStatus != 'processing') {
                    $order->update_status('processing');
                }

                $order->update_status($paidStatus, __('Оплата получена через webhook', 'external-payments'));
            } elseif (in_array(strtolower($status), ['failed', 'cancelled', 'declined'])) {
                $order->update_status('failed', __('Оплата не прошла', 'external-payments'));
            }

            $order->save();

        } catch (Exception $e) {
            return new WP_REST_Response(
                ['success' => false, 'message' => $e->getMessage()],
                500
            );
        }

        return new WP_REST_Response(['success' => true, 'message' => 'OK'], 200);
    }
}

/**
 * Initialize plugin
 */
function wc_external_payments_init()
{
    // Check if WooCommerce is active before initializing
    if (! class_exists('WooCommerce')) {
        return;
    }

    return WC_External_Payments::get_instance();
}

// Start plugin - use priority 1 to ensure WooCommerce is loaded first
add_action('plugins_loaded', 'wc_external_payments_init', 1);
