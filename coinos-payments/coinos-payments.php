<?php

/**
 * Plugin Name: BTC Lightning Payment via Coinos.io (Unofficial)
 * Plugin URI: https://github.com/reddatos/coinos-woo
 * Author Name: Reddatos
 * Author URI: https://reddatos.com
 * Description: The easiest way to get started with bitcoin. A free web wallet and payment page for everyone.
 * Version: 0.1.3
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: coinos-pay-woo
 * 
 * @package rdcpg
 */

// If this file is called directly, abort.
if ( !defined( 'ABSPATH' ) ) {
    die;
}

if ( !defined( 'RDCPG_PLUGIN_VERSION' ) ) {
    define( 'RDCPG_PLUGIN_VERSION', '0.1.3' );
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

// Load core packages.
require __DIR__ . '/includes/gateway.php';
require __DIR__ . '/includes/functions.php';

// Plugin Settings link
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'apd_settings_link' );
function apd_settings_link( array $links )
{
    $url = get_admin_url() . "admin.php?page=wc-settings&tab=checkout&section=coinos_payment";
    $settings_link = '<a href="' . $url . '">' . __('Settings', 'coinos-pay-woo') . '</a>';
    $links[] = $settings_link;
    return $links;
}

// Register a custom admin menu page.
add_action( 'admin_menu', 'coinos_payment_menu_page' );
function coinos_payment_menu_page() {
	add_menu_page(
		__( 'BTC Lightning', 'coinos-pay-woo' ),
		'BTC Lightning',
		'manage_options',
		'admin.php?page=wc-settings&tab=checkout&section=coinos_payment',
		'',
        'dashicons-superhero',
		// plugins_url('/assets/bitcoin-logo-w.svg', __FILE__),
		30
	);
}

// Check if Woocommerce plugin is active.
add_action( 'plugins_loaded', 'rdcpg_initialize_plugin' );
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
    wp_enqueue_style( 'rdcpg-custom-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
}

