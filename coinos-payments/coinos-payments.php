<?php
/**
 * Plugin Name: BTC Payments via Coinos.io (Unofficial)
 * Plugin URI: https://github.com/reddatos/coinos-woo
 * Author: Reddatos
 * Author URI: https://reddatos.com
 * Description: The easiest way to get started with Bitcoin. A free web wallet and payment page for everyone.
 * Version: 0.1.5
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: coinos-woo
 * 
 * @package CoinosWoo
 */

// If this file is called directly, abort
if (!defined('ABSPATH')) {
    die;
}

class CoinosWoo
{
    function activate() {
        flush_rewrite_rules();
    }
    
    function deactivate() {
        flush_rewrite_rules();
    }
}

if ( class_exists('CoinosWoo')) {
    $coinosWoo = new CoinosWoo();
}

register_activation_hook(__FILE__, array($coinosWoo,'activate'));

register_deactivation_hook(__FILE__, array($coinosWoo,'deactivate'));

function coinoswoo_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    };

    define('PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)) . '/');

    require_once(__DIR__ . '/includes/dashboard.php');
    require_once(__DIR__ . '/includes/functions.php');
    require_once(__DIR__ . '/includes/gateway.php');
    require_once(__DIR__ . '/includes/utility.php');

    // ? shall we go this way?
    // if (is_admin()) {
    //     require_once(__DIR__ . '/includes/admin_page.php');

    //     $services = [
    //         \WC_CoinosWoo_Admin_Page::class,
    //     ];

    //     foreach ($services as $service) {
    //         $service = new $service();
    //         $service->boot();
    //     }
    // }

    function add_coinoswoo_gateway($methods) {
        $methods[] = 'WC_Gateway_CoinosWoo';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_coinoswoo_gateway');
}
add_action('plugins_loaded', 'coinoswoo_init');

// Plugin Settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=coinos_payment');
    
    return array_merge(['<a href="' . $url . '">' . __('Settings', 'coinos-woo') . '</a>'], $links);

});

// ? is this feature necessary?
// Register a dashboard admin menu page.
if ( !function_exists( 'coinos_payment_menu_page' ) ) {
    function coinos_payment_menu_page() {
        
        $capability = 'edit_shop_orders'; // Change this to your desired capability

        add_menu_page( 
            __( 'BTC Wallet', 'coinos-woo' ),
            __( 'BTC Wallet', 'coinos-woo' ),
            $capability, // 'manage_options',
            'wallet', // Menu slug
            'wallet_dashboard_callback', // Callback
            'dashicons-superhero', // Icon
            30
        );

        add_submenu_page(
            'wallet',
            __( 'BTC Wallet Transactions', 'coinos-woo' ),
            __( 'Transactions', 'coinos-woo' ),
            $capability, //'manage_options',
            'wallet_transactions', // Menu slug
            'wallet_transactions_callback' // Callback
        );
    }
}
add_action( 'admin_menu', 'coinos_payment_menu_page' );

// Custom CSS for admin menu page
if ( !function_exists( 'btc_wallet_admin_script' ) ) {
    function btc_wallet_admin_script() {

        if ( ! did_action( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }
        wp_enqueue_style( 'btc-wallet-admin', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/admin.css' ) );
    }
}
add_action( 'admin_enqueue_scripts', 'btc_wallet_admin_script' );

// Custom CSS for front page
if ( !function_exists( 'btc_wallet_front_script' ) ) {
    function btc_wallet_front_script() {
        wp_enqueue_style( 'btc-wallet-front', plugins_url( '/assets/css/style.css', __FILE__ ), array(), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/style.css' ) );
    }
}
add_action( 'wp_enqueue_scripts', 'btc_wallet_front_script' );

// We require PHP version 7.2+ for the whole plugin to work.
if ( version_compare( phpversion(), '7.2', '<' ) ) {

	if ( ! function_exists( 'coinoswoo_php52_notice' ) ) {

		// Display the notice about incompatible PHP version after deactivation.
		function coinoswoo_php52_notice() {

			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						wp_kses(
							/* translators: %s - CoinoWoo URL for recommended WordPress hosting. */
							__( 'Your site is running an <strong>insecure version</strong> of PHP that is no longer supported. Please contact your web hosting provider to update your PHP version or switch to a <a href="%s" target="_blank" rel="noopener noreferrer">recommended WordPress hosting company</a>.', 'coinos-woo' ),
							[
								'a'      => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
								'strong' => [],
							]
						),
						'https://www.namecheap.com/wordpress/'
					);
					?>
					<br><br>
					<?php
					printf(
						wp_kses(
							/* translators: %s - reddatos.com URL for documentation with more details. */
							__( '<strong>Note:</strong> The coinoswoo plugin is disabled on your site until you fix the issue. <a href="%s" target="_blank" rel="noopener noreferrer">Read more for additional information.</a>', 'coinos-woo' ),
							[
								'a'      => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
								'strong' => [],
							]
						),
						'https://reddatos.com/'
					);
					?>
				</p>
			</div>

			<?php
			// In case this is on plugin activation.
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
	}

	add_action( 'admin_notices', 'coinoswoo_php52_notice' );

	// Do not process the plugin code further.
	return;
}

// We require WP version 7.2+ for the whole plugin to work.
if ( version_compare( $GLOBALS['wp_version'], '7.2', '<' ) ) {

	if ( ! function_exists( 'coinoswoo_wp_notice' ) ) {

		// Display the notice about incompatible WP version after deactivation.
		function coinoswoo_wp_notice() {

			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %s - WordPress version. */
						esc_html__( 'The Coinos Payment plugin is disabled because it requires WordPress %s or later.', 'coinos-woo' ),
						'5.2'
					);
					?>
				</p>
			</div>

			<?php
			// In case this is on plugin activation.
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
	}

	add_action( 'admin_notices', 'coinoswoo_wp_notice' );

	// Do not process the plugin code further.
	return;
}