<?php

/**
 * Plugin Name: BTC Lightning Payment via Coinos.io (Reddatos)
 * Plugin URI: https://reddatos.com
 * Author Name: Reddatos
 * Author URI: https://reddatos.com
 * Description: The easiest way to get started with bitcoin. A free web wallet and payment page for everyone.
 * Version: 0.1.2
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: coinos-pay-woo
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die;
}


if ( !defined( 'RDCPG_PLUGIN_VERSION' ) ) {
    define( 'RDCPG_PLUGIN_VERSION', '0.1.2' );
}
if ( !defined( 'RDCPG_PLUGIN_URL' ) ) {
    define( 'RDCPG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( !defined( 'RDCPG_PLUGIN_BASENAME' ) ) {
    define( 'RDCPG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( !defined( 'RDCPG_PLUGIN_NAME' ) ) {
    define( 'RDCPG_PLUGIN_NAME', 'BTC Lightning Payment via Coinos.io (Reddatos)' );
}


// Check if Woocommerce plugin is active.
if ( !function_exists( 'rdcpg_initialize_plugin' ) ) {
    function rdcpg_initialize_plugin()
    {
        
        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) && (!function_exists( 'is_plugin_active_for_network' ) || !is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) ) {
            add_action( 'admin_notices', 'rdcpg_plugin_admin_notice' );
        }
        
        // Load the plugin text domain for translation.
        load_plugin_textdomain( 'coinos-pay-woo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

}
add_action( 'plugins_loaded', 'rdcpg_initialize_plugin' );

// Show admin notice in case of WooCommerce plugin is missing.
if ( !function_exists( 'rdcpg_plugin_admin_notice' ) ) {
    function rdcpg_plugin_admin_notice()
    {
        $wpa_plugin_name = esc_html__( RDCPG_PLUGIN_NAME, 'coinos-pay-woo' );
        $wc_plugin = esc_html__( 'WooCommerce', 'coinos-pay-woo' );
        ?>
        <div class="error">
            <p>
                <?php 
        echo  sprintf( esc_html__( '%1$s requires %2$s to be installed & activated!', 'coinos-pay-woo' ), '<strong>' . esc_html( $wpa_plugin_name ) . '</strong>', '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '" target="_blank"><strong>' . esc_html( $wc_plugin ) . '</strong></a>' ) ;
        ?>
            </p>
        </div>
        <?php 
    }

}

// Custom Style
add_action( 'wp_enqueue_scripts', 'enqueue_custom_styles' );
function enqueue_custom_styles() {
    wp_enqueue_style( 'rdcpg-custom-style', plugin_dir_url( __FILE__ ) . '/assets/style.css' );
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

// Coinos Payment Gateway Plugin
add_action('plugins_loaded', 'coinos_payment_init', 11);
function coinos_payment_init()
{
    if (class_exists('WC_Payment_Gateway')) {

        // require plugin_dir_path( __FILE__ ) . 'functions.php';

        class WC_Coinos_Pay_Gateway extends WC_Payment_Gateway
        {
            public $text_paid;
            public $text_received;

            public function __construct()
            {
                global $woocommerce;

                $this->id = 'coinos_payment';
                $this->icon = apply_filters('woocommerce_coinos_icon', plugins_url('/assets/btc-ln-preferred.png', __FILE__));
                $this->has_fields = false;
                $this->method_title = __('Bitcoin Payment via Coinos.io', 'coinos-pay-woo');
                $this->method_description = __('The easiest way to get started with bitcoin. A free web wallet and payment page for everyone.', 'coinos-pay-woo');

        		// todo: maybe make the button text configurable via settings.
        		$this->order_button_text  = $this->get_option('order_button_text');
                
                $this->supports = array(
                    'products'
                );

                // Load the settings.
                $this->init_form_fields();
                $this->init_settings();

        		// Define user facing set variables.
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->wallet_user = $this->get_option('wallet_user');
                $this->api_secret = $this->get_option('api_secret');
                $this->api_auth_token = (empty($this->get_option('api_auth_token')) ? $this->get_option('api_secret') : $this->get_option('api_auth_token'));
                $this->store_currency = $this->get_option('store_currency');
                $this->text_received = $this->get_option('text_received', $this->text_received);
                $this->indications = $this->get_option('indications');
                $this->text_paid = $this->get_option('text_paid', $this->text_paid);
                $this->ln_numbers = $this->get_option('ln_numbers');

                // Actions.
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_checkout_process', array($this, 'process_payment'));
                add_action('woocommerce_api_coinos_payment_redirect', array($this, 'process_redirect'));
                add_action('woocommerce_api_coinos_success', array($this, 'payment_callback'));
                add_action('woocommerce_api_coinos_success', array($this, 'coinos_success'));
                add_action('woocommerce_api_coinos_failure', array($this, 'coinos_failure'));
                add_filter('woocommerce_thankyou_order_text_received', array($this, 'thankyou_page_text'), 10, 2);
                add_action('woocommerce_thankyou', array($this, 'thankyou_page_qrcode'), 5);


            }

            public function admin_options() {
                ?>
                <h3><?php _e('Bitcoin Payment via Coinos.io', 'coinos-pay-woo'); ?></h3>
                <p><?php _e('Accept Bitcoin and Lightning Network payments instantly through coinos.io platform', 'coinos-pay-woo'); ?></p>
                <table class="form-table">
                    <?php $this->generate_settings_html(); ?>
                </table>
                <?php
        
            }

            public function init_form_fields() {
                $this->form_fields = apply_filters('woo_coinos_pay_fields', array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'coinos-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __('Enable or Disable Payment Method', 'coinos-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __('Payment Title', 'coinos-pay-woo'),
                        'type' => 'text',
                        'default' => __('Bitcoin Lightning Network via coinos.io', 'coinos-pay-woo'),
                        'description' => __('Add a new title for the Coinos Payment Gateway that customers will see when they are on the checkout page.', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'description' => array(
                        'title' => __('Payment Description', 'coinos-pay-woo'),
                        'type' => 'textarea',
                        'default' => __('Please remit your payment to the shop to allow for the delivery to be made', 'coinos-pay-woo'),
                        'description' => __('Add a description for the Coinos Payment Gateway that customers will see when they are on the checkout page.', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'wallet_user' => array(
                        'title' => __('Username', 'coinos-pay-woo'),
                        'type' => 'text',
                        'default' => '',
                        'description' => __('The username assigned by coinos.io right before @coinos.io', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'api_auth_token' => array(
                        'title' => __('API Auth Token', 'coinos-pay-woo'),
                        'type' => 'password',
                        'description' => __('Your personal API Auth Token. Get yours for your account <a href="https://coinos.io/docs" target="_blank">here</a>.  ', 'coinos-pay-woo'),
                        'default' => (empty($this->get_option('api_secret')) ? '' : $this->get_option('api_secret')),
                    ),
                    'api_secret' => array(
                        'title' => __('API Secret Key', 'coinos-pay-woo'),
                        'type' => 'password',
                        'description' => __('Your personal API Secret Key. Contact coinos.io for support <a href="https://coinos.io/support" target="_blank">here</a>.  ', 'coinos-pay-woo'),
                        'default' => '',
                    ),
                    'store_currency' => array(
                        'title' => __('Store Currency', 'coinos-pay-woo'),
                        'type' => 'text',
                        'default' => 'USD',
                        'description' => __('Put the currency in ISO format, you can find yours <a href="https://en.wikipedia.org/wiki/ISO_4217" target="_blank">here</a>.  ', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'text_received' => array(
                        'title' => __('Received Text', 'coinos-pay-woo'),
                        'type' => 'textarea',
                        'default' => __('Thank you. Your order has been received.', 'coinos-pay-woo'),
                        'description' => __('Thank you message that will be added to the thank you page and order email.', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'indications' => array(
                        'title' => __('Code indications', 'coinos-pay-woo'),
                        'type' => 'textarea',
                        'default' => __('Scan, pay and confirm, that\'s it!', 'coinos-pay-woo'),
                        'description' => __('Indication that will be added to the thank you page and order email.', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'text_paid' => array(
                        'title' => __('Paid Text Button', 'coinos-pay-woo'),
                        'type' => 'text',
                        'default' => __('I have paid the order!', 'coinos-pay-woo'),
                        'description' => __('Enter the text you want to show in the below the invoice QR code', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'ln_numbers' => array(
                        'title' => __('Lightning Hash', 'coinos-pay-woo'),
                        'type' => 'number',
                        'default' => 15,
                        'description' => __('Enter the amount of LN hash characters to show.', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                    'order_button_text' => array(
                        'title' => __('Order Button Text', 'coinos-pay-woo'),
                        'type' => 'text',
                        'default' => __('Pay with Lightning', 'coinos-pay-woo'),
                        'description' => __('Name of the button to proceed to pay', 'coinos-pay-woo'),
                        'desc_tip' => true
                    ),
                ));
            }

            // Transform the order amount to Satoshis.
            private function process_satoshi($order) {
                $url_rates = generate_coinos_api_url('/rates');

                $headers = array(
                    'Cache-Control: no-cache',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_auth_token,
                );

                $args = array(
                    'headers' => $headers,
                );

                $response = wp_remote_get($url_rates, $args);

                if (is_wp_error($response)) {
                    return false;
                }

                $body = wp_remote_retrieve_body($response);
                $result = json_decode($body, true);

                if (isset($result[$this->store_currency])) {
                    $usd_rate = (float) $result[$this->store_currency];
                    $order_total = $order->get_total();
                    $amount_sats = round(($order_total / $usd_rate) * 100000000);
                    return $amount_sats;
                }

                return false;
            }

            // Create an invoice using Coinos API.
            private function process_invoice($amount_sats, $order_id) {
                $url_invoice = generate_coinos_api_url('/invoice');
                // Request parameters for creating an invoice
                $invoice_data = array(
                    'invoice' => array(
                        'amount' => $amount_sats,
                        'currency' => $this->store_currency,
                        'type' => 'lightning',
                        'webhook' => home_url('/wc-api/coinos_success?order_id=' . $order_id), // Set the webhook to the designated callback URL with order_id parameter
                        'secret' => $this->api_secret, // Set the secret to the designated callback URL with order_id parameter
                    ),
                );

                // Create the invoice using POST /invoice endpoint
                $response = wp_remote_post($url_invoice, array(
                    'method' => 'POST',
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->api_auth_token,
                    ),
                    'body' => wp_json_encode($invoice_data),
                ));

                // Check if the request was successful
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $invoice = json_decode(wp_remote_retrieve_body($response), true);

                    // Store the field values in variables for later use
                    $amount = $invoice['amount'];
                    $created = $invoice['created'];
                    $currency = $invoice['currency'];
                    $hash = $invoice['hash'];
                    $rate = $invoice['rate'];
                    $pending = $invoice['pending'];
                    $received = $invoice['received'];
                    $text = $invoice['text'];
                    $tip = $invoice['tip'];
                    $type = $invoice['type'];
                    $uid = $invoice['uid'];
                    $webhook = $invoice_data['invoice']['webhook']; // Retrieve the webhook URL from the invoice_data array

                    // Perform further processing with the variables as needed
                    return array(
                        'amount' => $amount,
                        'created' => $created,
                        'currency' => $currency,
                        'hash' => $hash,
                        'rate' => $rate,
                        'pending' => $pending,
                        'received' => $received,
                        'text' => $text,
                        'tip' => $tip,
                        'type' => $type,
                        'uid' => $uid,
                        'webhook' => $webhook,
                    );
                } else {
                    // Handle the error case when the API request fails
                    $error_message = is_wp_error($response) ? $response->get_error_message() : 'Unknown error occurred';
                    // Log the error message for troubleshooting
                    error_log('coinos.io invoice creation failed: ' . $error_message);

                    return false;
                }
            }
            
            // Process Payment
            public function process_payment($order_id) {
                 $order = wc_get_order($order_id);
             
                 if ($order) {
                     $amount_sats = $this->process_satoshi($order);
             
                     if (!$amount_sats) {
                         return array(
                             'result' => 'fail',
                             'redirect' => wc_get_checkout_url(),
                         );
                     }
             
                     $invoice = $this->process_invoice($amount_sats, $order_id);
             
                     if (!$invoice) {
                         return array(
                             'result' => 'fail',
                             'redirect' => wc_get_checkout_url(),
                         );
                     }
                     
                // Set the initial creation time (UTC timestamp) in order meta
                $order_created_time_utc = current_time('timestamp', true);
                $order->update_meta_data('_coinos_initial_order_created_time', $order_created_time_utc);
                $order->save();

                $coinos_text = $invoice['text']; // Get the invoice text for the QR code
                $coinos_hash = $invoice['hash']; // Get the invoice hash for the QR code
                $qr_code = $this->generate_qr_code($coinos_text, $order_id, $coinos_hash); // Generate the QR code image
                $coinos_text = htmlspecialchars($coinos_text, ENT_QUOTES); // Convert special characters to HTML entities

                // Save the invoice hash, text, and QR code in the order meta for later reference
                $order->update_meta_data('coinos_invoice_amount', $invoice['amount']);
                $order->update_meta_data('coinos_invoice_created', $invoice['created']);
                $order->update_meta_data('coinos_invoice_currency', $invoice['currency']);
                $order->update_meta_data('coinos_invoice_hash', $invoice['hash']);
                $order->update_meta_data('coinos_invoice_rate', $invoice['rate']);
                $order->update_meta_data('coinos_invoice_pending', $invoice['pending']);
                $order->update_meta_data('coinos_invoice_received', $invoice['received']);
                $order->update_meta_data('coinos_invoice_text', $coinos_text);
                $order->update_meta_data('coinos_invoice_tip', $invoice['tip']);
                $order->update_meta_data('coinos_invoice_network', $invoice['type']);
                $order->update_meta_data('coinos_invoice_uid', $invoice['uid']);
                $order->update_meta_data('coinos_qr_code', $qr_code); // Save the QR code image
                $order->save();
        
                $redirect_url = $order->get_checkout_order_received_url(); // Redirect to the order received (thank you) page
                // $redirect_url = add_query_arg('order_id', $order_id, plugin_dir_url(__FILE__) . 'includes/order-pay.php');

                $order->add_order_note(__('Awaiting customer payment through coinos.io platform.', 'coinos-pay-woo'));
                $order->update_status('pending', __('Awaiting coinos.io payment', 'coinos-pay-woo'));
                
                // Send customer notification email Order Received
                // WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                
                // Send notification email New Order
                WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());

                // Reduce stock levels
                // $order->reduce_order_stock();
                wc_reduce_stock_levels($order_id);

                // Remove cart
                WC()->cart->empty_cart();

                return array(
                        'result' => 'success',
                        'redirect' => $redirect_url,
                    );
                }
            }

            
            // Show LN hash in the Order Received page.
            public function order_text($string) {
                $start_length = $this->ln_numbers;
                $end_length = $this->ln_numbers;

                $string_length = strlen($string);
                
                if ($string_length <= ($start_length + $end_length)) {
                    return $string;
                }
                
                $start = substr($string, 0, $start_length);
                $end = substr($string, -$end_length);
                
                return $start . '...' . $end;
            }
            
            // Show QR code image in the Order Received page.
            private function generate_qr_code($coinos_text, $order_id, $coinos_hash) {
                $order = wc_get_order($order_id); // Get the order ID
                $qrcode_url = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($coinos_text);
            
                // Common styles for hiding order details
                $common_styles = '<style>.header-hero { display:none } .woocommerce-thankyou-order-received, .woocommerce ul.order_details, .woocommerce-order-details, .woocommerce-customer-details { display: none }</style>';
            
                // Get the initial order creation time from the order meta
                $initial_order_created_time_utc = $order->get_meta('_coinos_initial_order_created_time');
            
                // Get current time in UTC timestamp
                $current_time_utc = current_time('timestamp', true);
            
                // Calculate time difference in seconds
                $time_difference_seconds = $current_time_utc - $initial_order_created_time_utc;
            
                // Calculate minutes elapsed
                $minutes_elapsed = floor($time_difference_seconds / 60);

                // Debugging: Print time-related values
                // echo 'Order Created Time (UTC): ' . date('Y-m-d H:i:s', $order_created_time_utc) . '<br>';
                // echo 'Current Time (UTC): ' . date('Y-m-d H:i:s', $current_time_utc) . '<br>';
                // echo 'Time Difference (seconds): ' . $time_difference_seconds . '<br>';
                // echo 'Expired Minutes Elapsed: ' . $minutes_elapsed . '<br>';

            
                if ($order->get_status() === 'pending') {
                    if ($minutes_elapsed > 15) {
                        // Order pending but exceeded 15 minutes, show reprocess button
                        $reprocess_url = wc_get_checkout_url() . 'order-pay/' . $order_id . '/?pay_for_order=true&key=' . $order->get_order_key();
                        echo '<div style="max-width:400px" class="pay_order_box">';
                        echo '<p>Your invoice code has expired. <br /> Click the button below to get a new one</p>';
                        echo '<a href="' . esc_url($reprocess_url) . '" class="button alt">' . esc_html__('Create new invoice', 'coinos-pay-woo') . '</a>';
                        echo '</div>';
                    } else {
                        // Order is still pending but within 15 minutes, show instructions
                        if ($coinos_text == '') {
                            echo $common_styles;
                            echo '<div class="pay-to-text" style="text-align: center;">';
                            echo '<h5>Waiting Pay Order # ' . $order_id . ' by clicking the button below.</h5>';
                            echo '<a class="pay-to-button" href="https://coinos.io/send/user/' . $this->wallet_user . '/' . $order->get_total() . '/' . $this->store_currency . '" target="_blank">';
                            echo 'Pay this order!';
                            echo '</a>';
                            echo '</div>';
                        } else {
                            echo $common_styles;
                            echo '<div class="pay_order_instructions">' . $this->indications . '</div>';
                            echo '<div class="pay_order_box">';
                            echo '<p class="pay_order_details">Your Order #' . $order_id . '</p>';
                            echo '<p class="pay_order_amount">Amount ' .$this->store_currency . ' ' .$order->get_total() . '</p>';
                            // echo '<p class="pay_order_amount">' . $this->store_currency . ' ' .$order->get_total() . '</p>';
                            echo '<img class="pay_order_logo" src="' . $this->icon . '" alt="Lightning Payment">';
                            echo '<a class="pay_invoice" href="https://coinos.io/' . $this->wallet_user . '/invoice/' . $coinos_hash . '" target="_blank">';
                            echo '<img class="pay_order_qrcode" src="'. $qrcode_url . '" alt="Order #' . $order_id . '">';
                            echo '</a>';
                            echo '<a class="pay_order_text" href="lightning:'. urlencode($coinos_text) .'" >' . $this->order_text($coinos_text) . '</a>';
                            echo '<a class="pay_order_paid" style="cursor:pointer" onclick="location.reload();">' . $this->text_paid . '</a>';
                            echo '</div>';
                        }
                    }
                } elseif ($order->get_status() === 'completed') {
                    // Order is completed, show "Order Paid" message
                    echo '<h3 style="text-align: center;">Order Paid</h3>';
                } elseif ($order->get_status() === 'cancelled') {
                    // Order is cancelled, show "Unpaid Order Cancelled" message and save metadata invoice URL
                    $invoice_unpaid = 'https://coinos.io/' . $this->wallet_user . '/invoice/' . $coinos_hash;
                    $order->update_meta_data('invoice_unpaid_url', $invoice_unpaid);
                    echo '<h3 style="text-align: center;">Unpaid Order Cancelled</h3>';
                }
            
                // Fetch the QR code image using wp_remote_get()
                $response = wp_remote_get($qrcode_url);
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $qrcode_image = 'data:image/png;base64,' . base64_encode($body);
            
                    // Save the QR code image in the order meta
                    $order->update_meta_data('coinos_qr_code', $qrcode_image);
                    $order->save();
                }
            }

            // Retrieve the LN and Invoice hash.
            public function thankyou_page_qrcode($order_id)
             {
                 $order = wc_get_order($order_id);
                 $coinos_text = $order->get_meta('coinos_invoice_text');
                 $coinos_hash = $order->get_meta('coinos_invoice_hash');
                 
                 $this->generate_qr_code($coinos_text, $order_id, $coinos_hash);
             }

            // Show Received text field information in the Order Received page
            public function thankyou_page_text($text, $order_id) {
                // Modify the thank you text as per your requirements
                $text = $this->text_received;
            
                return $text;
            }


            // Payment callback process updating order status and sending email
            public function payment_callback() {
                $request = $_REQUEST;
                $order_id = isset($request['order_id']) ? absint($request['order_id']) : 0;
                $order = wc_get_order($order_id);
            
                try {
                    if (!$order || !$order->get_id()) {
                        throw new Exception('Order #' . $request['order_id'] . ' does not exist');
                    }
            
                    // Retrieve the invoice hash from the order meta
                    $invoice_hash = $order->get_meta('coinos_invoice_hash');
            
                    if (empty($invoice_hash)) {
                        throw new Exception('Order does not have a coinos.io invoice associated');
                    }
            
                    $api_url = generate_coinos_api_url('/invoice/') . $invoice_hash;
                    $response = wp_remote_get($api_url);
            
                    if (is_wp_error($response)) {
                        throw new Exception('Failed to retrieve invoice details from coinos.io API');
                    }
            
                    $body = wp_remote_retrieve_body($response);
                    $invoice_details = json_decode($body, true);
            
                    if (!$invoice_details || !isset($invoice_details['amount']) || !isset($invoice_details['received'])) {
                        throw new Exception('Invalid invoice details received from coinos.io API.');
                    }
            
                    $invoice_amount = $invoice_details['amount'];
                    $invoice_received = $invoice_details['received'];
                    $invoice_tip = $invoice_details['tip'];
            
                    if ($invoice_amount != $invoice_received) {
                        throw new Exception('Invoice amount has not been received, payment is pending.');
                    }

                    // Update the invoice fields in order meta
                    $order->update_meta_data('coinos_invoice_received', $invoice_received);
                    $order->update_meta_data('coinos_invoice_tip', $invoice_tip);
                    $order->save();

                    // Invoice is paid, update order status and add order notes
                    $statusWas = $order->get_status();
                    $order->add_order_note(__('Payment is settled and has been credited to your coinos.io account. Order securely delivered to the customer.', 'coinos-pay-woo'));
                    $order->payment_complete();
                    $order->update_status('completed'); // Set the order status to "Completed"
            
                    if ($order->get_status() === 'processing' && ($statusWas === 'expired' || $statusWas === 'cancelled')) {
                        WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                    }
                    
                    //if (($order->get_status() === 'processing' || $order->get_status() === 'completed') && ($statusWas === 'expired' || $statusWas === 'cancelled')) {
                    //    WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
                    //}
                    
                    if ($order->get_status() === 'completed' && ($statusWas === 'processing')) {
                        WC()->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order->get_id());
                    }

                    // Redirect the customer to the thank you page
                    wp_redirect($order->get_checkout_order_received_url());
                    exit;

                } catch (Exception $e) {
                    die(get_class($e) . ': ' . $e->getMessage());
                }
            }
            
            // handling the redirection when a payment is initiated through the coinos.io platform
            public function process_redirect()
            {
                if (isset($_GET['order_id'])) {
                    $order_id = absint($_GET['order_id']);
                    $order = wc_get_order($order_id);
            
                    if ($order && $order->get_status() === 'pending') {
                        $order->update_status('on-hold', __('Awaiting coinos.io payment', 'coinos-pay-woo'));
                        wp_redirect(add_query_arg('order_id', $order_id, $this->get_return_url($order)));
                        exit;
                    }
                }
            }

            // Webhook notifications related to success payment events
            public function coinos_success()
            {
                // Retrieve the webhook payload
                $payload = file_get_contents('php://input');
                $data = json_decode($payload, true);
            
                // Check if the payload was successfully parsed
                if ($data) {
                    // Extract the necessary information from the payload
                    $invoiceHash = $data['hash']; // Assuming the hash field contains the invoice hash
                    $orderID = $data['order_id']; // Assuming the order ID is included in the payload
            
                    // Perform any necessary actions based on the received data
                    // For example, update the order status to "completed"
                    $order = wc_get_order($orderID);
                    if ($order) {
                        $order->update_status('completed', __('Payment received via coinos.io', 'coinos-pay-woo'));
                    }
                }
            
                // Send a response back to the coinos.io API
                status_header(200);
                exit;
            }

            // Webhook notifications related to failure payment events
            public function coinos_failure()
            {
                // Retrieve the webhook payload
                $payload = file_get_contents('php://input');
                $data = json_decode($payload, true);
            
                // Check if the payload was successfully parsed
                if ($data) {
                    // Extract the necessary information from the payload
                    $invoiceHash = $data['hash']; // Assuming the hash field contains the invoice hash
                    $orderID = $data['order_id']; // Assuming the order ID is included in the payload
            
                    // Perform any necessary actions based on the received data
                    // For example, update the order status to "failed"
                    $order = wc_get_order($orderID);
                    if ($order) {
                        $order->update_status('failed', __('Payment failed via coinos.io', 'coinos-pay-woo'));
                    }
                }
            
                // Send a response back to the coinos.io API
                status_header(200);
                exit;
            }
            
        }
    }
}

// Plugin Settings link
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'apd_settings_link' );
function apd_settings_link( array $links ) {
    $url = get_admin_url() . "admin.php?page=wc-settings&tab=checkout&section=coinos_payment";
    $settings_link = '<a href="' . $url . '">' . __('Settings', 'coinos-pay-woo') . '</a>';
      $links[] = $settings_link;
    return $links;
  }

// Deal with unavailable order in WooCommerce
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
add_action('template_redirect', 'redirect_unavailable_order_to_home');



// Add the custom column to the order admin page
function add_order_sats_column($columns) {
    $columns['coinos_invoice_received'] = __('Sats', 'coinos-pay-woo');
    return $columns;
}
add_filter('manage_edit-shop_order_columns', 'add_order_sats_column');

// Display the value of the custom field in the custom column
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
add_action('manage_shop_order_posts_custom_column', 'show_order_sats', 10, 2);
