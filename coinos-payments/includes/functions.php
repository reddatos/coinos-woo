<?php
/**
 * BTC Lightning Payment Gateways for WooCommerce - Functions
 *
 * @version 1.0.1
 * @since   1.0.0
 * @author  Reddatos
 * @package rdcpg
 */

 if ( !defined( 'ABSPATH' ) ) {
    die;
}

// Generate the API URL for the Coinos API.
if ( !function_exists( 'generate_coinos_api_url' ) ) {
    function generate_coinos_api_url($endpoint) {
        $api_base_url = 'https://coinos.io/api';
        return $api_base_url . $endpoint;
    }
}

// Ading Coinos Pay class to Woocommerce payment gateway
add_filter('woocommerce_payment_gateways', 'add_to_woo_coinos_payment_gateway');
function add_to_woo_coinos_payment_gateway($gateways)
{
    $gateways[] = 'WC_Coinos_Pay_Gateway';
    return $gateways;
}

// Deal with unavailable order in WooCommerce
add_action('template_redirect', 'redirect_unavailable_order_to_home');
function redirect_unavailable_order_to_home() {
    // Check if we're on the order received page
    if (is_wc_endpoint_url('order-received')) {
        $order_id = absint(get_query_var('order-received'));

        // Check if the order is unavailable (change the condition as needed)
        if (!wc_get_order($order_id)) {
            wp_redirect(home_url());
            exit;
        }
    }
}

// Add the custom column to the order admin page
add_filter('manage_edit-shop_order_columns', 'add_order_sats_column');
function add_order_sats_column($columns) {
    $columns['coinos_invoice_received'] = __('Sats', 'coinos-pay-woo');
    return $columns;
}

// Display the value of the custom field in the custom column
add_action('manage_shop_order_posts_custom_column', 'show_order_sats', 10, 2);
function show_order_sats($column, $post_id) {
    if ($column === 'coinos_invoice_received') {
        $order = wc_get_order($post_id);
        $coinos_invoice_received = $order->get_meta('coinos_invoice_received');

    if ($coinos_invoice_received) {
        echo $coinos_invoice_received . ' sats';
    } else {
        echo '-';
    }
    }
}
