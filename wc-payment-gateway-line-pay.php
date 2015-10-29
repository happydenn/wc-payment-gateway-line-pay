<?php
/**
 * Plugin Name: LINE Pay Gateway for WooCommerce
 * Plugin URI: http://hpd.io
 * Description: 讓 WooCommerce 可以支援使用 LINE Pay 進行結帳！
 * Version: 0.3.5
 * Author: Denny Tsai
 * Author URI: http://hpd.io
 */

if ( !defined( 'ABSPATH' ) ) { exit; }

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

// LINE Pay API Settings
define( 'HPD_LINEPAY_API_ROOT', 'https://api-pay.line.me/v2' );
define( 'HPD_LINEPAY_SANDBOX_API_ROOT', 'https://sandbox-api-pay.line.me/v2' );


add_filter( 'woocommerce_payment_gateways', 'hpd_add_linepay_gateway_class' );

function hpd_add_linepay_gateway_class( $methods ) {
    $methods[] = 'HPD_LinePay_Gateway';
    return $methods;
}

add_action( 'plugins_loaded', 'hpd_init_linepay_gateway_class' );

function hpd_init_linepay_gateway_class() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        deactivate_plugins( __FILE__ );
        add_action( 'admin_notices', 'hpd_linepay_gateway_woocommerce_error' );

        return;
    }

    require_once( dirname( __FILE__ ) . '/class-hpd-linepay-gateway.php' );
}

function hpd_linepay_gateway_woocommerce_error() {
    echo '<div class="error"><p>本外掛需要先啟用 WooCommerce 之後再啟用！</p></div>';
}
