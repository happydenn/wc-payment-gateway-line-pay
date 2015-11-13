<?php
/**
 * Plugin Name: LINE Pay Gateway for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/wc-payment-gateway-line-pay/
 * Description: Accept LINE Pay mobile payments on your WooCommerce powered store!
 * Version: 0.4.3
 * Author: Denny Tsai
 * Author URI: http://hpd.io/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.en.html
 * Text Domain: wc-payment-gateway-line-pay
 * Domain Path: /languages
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
    echo '<div class="error"><p>' . __( 'LINE Pay Gateway plugin requires WooCommerce to be activated.', 'wc-payment-gateway-line-pay' ) . '</p></div>';
}

add_action( 'plugins_loaded', 'hpd_linepay_load_plugin_textdomain' );

function hpd_linepay_load_plugin_textdomain() {
    load_plugin_textdomain( 'wc-payment-gateway-line-pay', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
