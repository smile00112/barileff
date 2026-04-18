<?php

/**
 * Payment Form Template
 *
 * @var WC_Order $order
 */
if (! defined('ABSPATH')) {
    exit;
}

$gateway = new WC_External_Payments_Gateway;
$api = new WC_External_Payments_API($gateway->api_server_url, $gateway->api_token);

// Get order data
$order_id = $order->get_id();
$order_total = $order->get_total();
$billing_email = $order->get_billing_email();
$billing_first_name = $order->get_billing_first_name();
$billing_last_name = $order->get_billing_last_name();
$billing_phone = $order->get_billing_phone();

$client_name = trim($billing_first_name.' '.$billing_last_name);
if (empty($client_name)) {
    $client_name = $order->get_billing_company();
}

// Get product names
// $product_names = array();
// foreach ($order->get_items() as $item) {
//     $product_names[] = $item->get_name();
// }
// $product_name = implode(', ', $product_names);
// if (empty($product_name)) {
//     $product_name = __('Заказ #', 'external-payments') . $order_id;
// }
$message_count = (int) $order_total / 2;
// Prepare payment data
$payment_data = [
    'amount' => $order_total,
    'client_name' => $client_name,
    'client_email' => $billing_email,
    'client_phone' => $billing_phone,
    'external_order_id' => (string) $order_id,
    'product_name' => 'Оплата исходящих сообщений ('.$message_count.'шт.)', // $product_name,
];

// Create payment
$payment_result = $api->create_payment($payment_data);

if (is_wp_error($payment_result)) {
    wc_add_notice($payment_result->get_error_message(), 'error');
    wp_redirect(wc_get_checkout_url());
    exit;
}

if (! isset($payment_result['success']) || ! $payment_result['success']) {
    $error_message = isset($payment_result['message']) ? $payment_result['message'] : __('Ошибка создания платежа', 'external-payments');
    wc_add_notice($error_message, 'error');
    wp_redirect(wc_get_checkout_url());
    exit;
}

$payment_url = isset($payment_result['payment_url']) ? $payment_result['payment_url'] : '';

if (empty($payment_url)) {
    wc_add_notice(__('Не получена ссылка на оплату', 'external-payments'), 'error');
    wp_redirect(wc_get_checkout_url());
    exit;
}

// Store payment_id in order meta for later use
if (isset($payment_result['payment_id'])) {
    $order->update_meta_data('_external_payment_id', $payment_result['payment_id']);
    $order->save();
}

// Redirect to payment URL
wp_redirect($payment_url);
exit;
