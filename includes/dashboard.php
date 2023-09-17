<?php

if (!defined('ABSPATH')) {
    die;
}

// ? Is this feature helpful?

// Display callback for wallet Dashboard.
if ( !function_exists( 'wallet_dashboard_callback' ) ) {
    function wallet_dashboard_callback() {

        $coinos_gateway = new WC_Gateway_CoinosWoo();
        // Fetch balance data
        $balance_data = $coinos_gateway->request_balance();
        $payments_data = $coinos_gateway->request_payments();

        // Get the Coinos Payment Gateway instance
        // $coinos_gateway = wc()->payment_gateways->payment_gateways()['coinos_payment'];

        $wallet_icon = $coinos_gateway->icon;
        $wallet_send = $coinos_gateway->send_wallet;
        $wallet_network = $coinos_gateway->send_network;

        // Rest of your wallet_dashboard code
        $url_setting = get_admin_url() . "admin.php?page=wc-settings&tab=checkout&section=coinos_payment";
        $settings_link = '<a href="' . $url_setting  . '" target="_blank">' . __('WooCommerce Settings', 'coinos-woo') . '</a>';

        $url_wallet = "https://coinos.io/login?redirect=/" . $wallet_user;
        $dashboard_link = '<a href="' . $url_wallet . '" target="_blank">' . __('Coinos.io Dashboard', 'coinos-woo') . '</a>';

        $url_order = get_admin_url() . "edit.php?post_type=shop_order";
        $order_link = '<a href="' . $url_order  . '" target="_blank">' . __('Orders', 'coinos-woo') . '</a>';
        
        ?>
        <div class="wrap">
            <h1><?php _e('BTC Wallet', 'coinos-woo'); ?></h1>
            <img src="<?php echo $wallet_icon; ?>">
            <h3><?php _e('Dashboard', 'coinos-woo'); ?> ( <?php echo $balance_data['display']; ?> )</h3>
            <div class="theme-browser">
                <div class="theme btc-dashboard">
                    <h2><?php _e('Balance', 'coinos-woo'); ?></h2>
                    <h1><?php echo $balance_data['balance'] . 'sats'; ?></h1>
                    <p><strong><?php _e('Account', 'coinos-woo'); ?>:</strong> <?php echo $dashboard_link; ?></p>
                </div>
                <div class="theme btc-dashboard">
                    <h2><?php _e('Transactions', 'coinos-woo'); ?></h2>
                    <h1><?php echo $payments_data['count']; ?></h1>
                    <p><strong><?php _e('Orders', 'coinos-woo'); ?>:</strong> <?php echo $order_link; ?></p>
                </div>
        <?php

        if (!empty($wallet_send)) {
            ?>
                    <div class="theme btc-dashboard">
                        <h2><?php _e('Wallet', 'coinos-woo'); ?></h2>
                        <p><strong><?php _e('Address', 'coinos-woo'); ?>:</strong> <?php echo $wallet_send; ?></p>
                        <p><strong><?php _e('Network', 'coinos-woo'); ?>:</strong> <?php echo $wallet_network; ?></p>
                        <p><strong><?php _e('Settings', 'coinos-woo'); ?>:</strong> <?php echo $settings_link; ?></p>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

// Display callback for wallet Transactions.
if ( !function_exists( 'wallet_transactions_callback' ) ) {
    function wallet_settings_callback() {
        /**
       ?>
       <div class="wrap">
           <h1><?php _e('BTC Wallet', 'coinos-woo'); ?></h1>
           <img src="<?php echo $wallet_icon; ?>">
           <h3><?php _e('Transactions', 'coinos-woo'); ?> ( <?php echo 'N/A'; ?> )</h3>
           <div class="theme-browser">
                <div class="theme btc-dashboard">
                    <h2><?php _e('Transactions', 'coinos-woo'); ?></h2>
                    <p><strong><?php _e('Address', 'coinos-woo'); ?>:</strong> <?php echo 'N/A'; ?></p>
                    <p><strong><?php _e('Network', 'coinos-woo'); ?>:</strong> <?php echo 'N/A'; ?></p>
                    <p><strong><?php _e('Settings', 'coinos-woo'); ?>:</strong> <?php echo 'N/A'; ?></p>
                </div>
                <div class="theme btc-dashboard">
                   <h2><?php _e('Settings', 'coinos-woo'); ?></h2>
                   <h1><?php _e('#####', 'coinos-woo'); ?></h1>
                   <p><strong><?php _e('Orders', 'coinos-woo'); ?>:</strong> <?php echo 'N/A'; ?></p>
               </div>
           </div>
        </div>
        <?php
         */
    }
}