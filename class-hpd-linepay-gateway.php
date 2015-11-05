<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

require_once( dirname( __FILE__ ) . '/class-hpd-linepay-client.php' );

class HPD_LinePay_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'hpd_linepay';
        $this->icon = plugin_dir_url( __FILE__ ) . 'assets/linepay_logo_new.png';
        $this->has_fields = false;
        $this->method_title = __( 'LINE Pay', 'wc-payment-gateway-line-pay' );
        $this->method_description = __( 'Accept payments using LINE Pay.', 'wc-payment-gateway-line-pay' );
        $this->supports = array( 'products', 'refunds' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->channel_id = $this->get_option( 'channelId' );
        $this->channel_secret = $this->get_option( 'channelSecret' );
        $this->sandbox_mode = ( $this->get_option( 'sandboxMode' ) === 'yes' ) ? true : false;

        if ( $this->get_option( 'iconUrl' ) !== '' ) {
            $this->icon = $this->get_option( 'iconUrl' );
        }

        $product_image_url = $this->get_option( 'productImageUrl' );

        if ( empty( $product_image_url ) ) {
            $this->product_image_url = plugin_dir_url( __FILE__ ) . 'assets/product-image-fallback.png';
        } else {
            $this->product_image_url = $this->get_option( 'productImageUrl' );
        }

        if ( empty( $this->channel_id ) || empty( $this->channel_secret ) ) {
            $this->enabled = false;
        } else {
            $this->client = new HPD_LinePay_Client( $this->channel_id, $this->channel_secret, $this->sandbox_mode );
        }

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_linepay_confirm_url', array( $this, 'confirm_url_callback' ) );
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable', 'wc-payment-gateway-line-pay' ),
                'type' => 'checkbox',
            ),
            'title' => array(
                'title' => __( 'Title', 'wc-payment-gateway-line-pay' ),
                'default' => 'LINE Pay',
            ),
            'description' => array(
                'title' => __( 'Description', 'wc-payment-gateway-line-pay' ),
                'type' => 'textarea',
                'default' => __( 'Make your payment via LINE Pay on PC or mobile device.', 'wc-payment-gateway-line-pay' ),
            ),
            'channelId' => array(
                'title' => __( 'Channel ID', 'wc-payment-gateway-line-pay' ),
            ),
            'channelSecret' => array(
                'title' => __( 'Channel Secret Key', 'wc-payment-gateway-line-pay' ),
            ),
            'sandboxMode' => array(
                'title' => __( 'Run in Sandbox mode', 'wc-payment-gateway-line-pay' ),
                'type' => 'checkbox',
            ),
            'productImageUrl' => array(
                'title' => __( 'Product Image URL', 'wc-payment-gateway-line-pay' ),
                'description' => __( 'URL to the image that is shown on LINE Pay payment interface. (Best size: 84x84)', 'wc-payment-gateway-line-pay' ),
            ),
            'iconUrl' => array(
                'title' => __( 'Custom payment icon URL', 'wc-payment-gateway-line-pay' ),
                'description' => __( 'URL to the image that is shown on checkout page.', 'wc-payment-gateway-line-pay' ),
            ),
            'langCd' => array(
                'title' => __( 'Payment UI Language', 'wc-payment-gateway-line-pay' ),
                'description' => __( 'Language used in the LINE Pay payment interface.', 'wc-payment-gateway-line-pay' ),
                'type' => 'select',
                'options' => array(
                    '' => __( 'Auto detect', 'wc-payment-gateway-line-pay' ),
                    'zh-Hant' => __( 'Traditional Chinese (Taiwan)', 'wc-payment-gateway-line-pay' ),
                    'zh-Hans' => __( 'Simplified Chinese', 'wc-payment-gateway-line-pay' ),
                    'ja' => __( 'Japanese', 'wc-payment-gateway-line-pay' ),
                    'en' => __( 'English', 'wc-payment-gateway-line-pay' ),
                    'ko' => __( 'Korean', 'wc-payment-gateway-line-pay' ),
                    'th' => __( 'Thai', 'wc-payment-gateway-line-pay' ),
                ),
            ),
        );
    }

    public function is_currency_supported() {
        return in_array( get_woocommerce_currency(), array( 'TWD', 'JPY', 'USD', 'THB' ) );
    }

    public function process_payment( $order_id ) {
        if ( !$this->is_currency_supported() ) {
            throw new Exception( __( 'You cannot use this currency with LINE Pay.', 'wc-payment-gateway-line-pay' ) );
        }

        $order = new WC_Order( $order_id );

        $items = $order->get_items();
        reset( $items );

        $first_item = $items[ key( $items ) ];
        $item_name = $first_item[ 'name' ];
        $item_count = $order->get_item_count();

        $product_name = $item_name;
        if ( $item_count > 1 ) {
            $product_name .= sprintf(
                __( ' and %s others', 'wc-payment-gateway-line-pay' ),
                $item_count - 1
            );
        }

        $response_data = $this->client->reserve(
            $product_name,
            $this->product_image_url,
            $order->get_total(),
            get_woocommerce_currency(),
            add_query_arg( 'wc-api', 'linepay_confirm_url', home_url( '/' ) ),
            $order->get_checkout_payment_url(),
            $order_id,
            $this->get_option( 'langCd' ) );

        if ( $response_data->returnCode != '0000' ) {
            throw new Exception(
                sprintf(
                    __( 'Incorrect parameters were passed during checkout. Please contact site administrator. Return code: %s', 'wc-payment-gateway-line-pay' ),
                    $response_data->returnCode
                )
            );
        }

        update_post_meta( $order_id, '_hpd_linepay_transactionId', $response_data->info->transactionId );
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $response_data->info->paymentUrl->web,
        );
    }

    public function confirm_url_callback() {
        $transaction_id = $_GET[ 'transactionId' ];

        $results = get_posts( array(
            'post_type' => 'shop_order',
            'meta_query' => array(
                array( 'key' => '_hpd_linepay_transactionId', 'value' => $transaction_id ),
            ),
        ) );

        if ( !$results ) {
            http_response_code( 404 );
            exit;
        }

        $order_data = $results[0];
        $order_id = $order_data->ID;
        $order = new WC_Order( $order_id );

        $response_data = $this->client->confirm( $transaction_id, $order->get_total(), get_woocommerce_currency() );

        if ( $response_data->returnCode != '0000' ) {
            $order->update_status(
                'failed',
                sprintf(
                    __( 'Error return code: %1$s, message: %2$s', 'wc-payment-gateway-line-pay' ),
                    $response_data->returnCode,
                    $response_data->returnMessage
                )
            );
        } else {
            $order->payment_complete();
        }

        wp_redirect( $order->get_checkout_order_received_url() );
        exit;
    }

    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        $transaction_id = get_post_meta( $order_id, '_hpd_linepay_transactionId', true );

        $response_data = $this->client->refund( $transaction_id, $amount );

        if ( $response_data->returnCode !== '0000' ) {
            return false;
        }

        return true;
    }
}
