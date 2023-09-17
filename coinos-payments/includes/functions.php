<?php

if (!defined('ABSPATH')) {
    die;
}

// Generate the API URL for the Coinos API.
if (!function_exists( 'generate_coinos_api_url')) {
    function generate_coinos_api_url($endpoint) {
        $api_base_url = 'https://coinos.io/api';
        return $api_base_url . $endpoint;
    }
}

// Deal with unavailable orders in WooCommerce.
if (!function_exists( 'redirect_unavailable_order_to_home' ) ) {
    function redirect_unavailable_order_to_home() {
        // Check if we're on the order received page
        if (is_wc_endpoint_url('order-received')) {
            $order_id = absint(get_query_var('order-received'));

            // Check if the order is unavailable (change the condition as needed)
            if (!wc_get_order($order_id)) {
                wp_redirect(home_url()); // Set you custom page as needed
                exit;
            }
        }
    }
}
add_action('template_redirect', 'redirect_unavailable_order_to_home');

// Add the custom column to the order admin page.
if (!function_exists('add_order_sats_column')) {
    function add_order_sats_column($columns) {
        $columns['coinos_invoice_received'] = __('Sats', 'coinos-woo');
        return $columns;
    }
}
add_filter('manage_edit-shop_order_columns', 'add_order_sats_column');

// Display the value of the custom field in the custom column.
if (!function_exists('show_order_sats')) {
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
}
add_action('manage_shop_order_posts_custom_column', 'show_order_sats', 10, 2);
