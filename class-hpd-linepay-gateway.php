<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

require_once( dirname( __FILE__ ) . '/class-hpd-linepay-client.php' );

class HPD_LinePay_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'hpd_linepay';
        $this->icon = plugin_dir_url( __FILE__ ) . 'assets/linepay_logo_74x24.png';
        $this->has_fields = false;
        $this->method_title = 'LINE Pay';
        $this->method_description = '使用 LINE Pay 進行付款';
        $this->supports = array( 'products', 'refunds' );

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option( 'enabled' );
        $this->title = $this->get_option( 'title' );
        $this->channel_id = $this->get_option( 'channelId' );
        $this->channel_secret = $this->get_option( 'channelSecret' );
        $this->sandbox_mode = ( $this->get_option( 'sandboxMode' ) === 'yes' ) ? true : false;

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
                'title' => '啟用',
                'type' => 'checkbox',
            ),
            'title' => array(
                'title' => '標題',
                'default' => 'LINE Pay',
            ),
            'channelId' => array(
                'title' => 'Channel ID',
            ),
            'channelSecret' => array(
                'title' => 'Channel Secret Key',
            ),
            'sandboxMode' => array(
                'title' => '使用Sandbox模式',
                'type' => 'checkbox',
            ),
            'productImageUrl' => array(
                'title' => '結帳圖檔URL',
                'description' => '顯示於付款畫面上的影像 URL (建議大小 84x84)',
            ),
            'langCd' => array(
                'title' => '付款頁面語言',
                'description' => 'LINE Pay等待付款頁面的語言',
                'type' => 'select',
                'options' => array(
                    '' => '自動選擇',
                    'zh-Hant' => '繁體中文',
                    'zh-Hans' => '簡體中文',
                    'ja' => '日文',
                    'en' => '英文',
                    'ko' => '韓文',
                    'th' => '泰文',
                ),
            ),
        );
    }

    public function is_currency_supported() {
        return in_array( get_woocommerce_currency(), array( 'TWD', 'JPY', 'USD', 'THB' ) );
    }

    public function process_payment( $order_id ) {
        if ( !$this->is_currency_supported() ) {
            throw new Exception( 'LINE Pay並不接受目前所使用的幣值' );
        }

        $order = new WC_Order( $order_id );

        $items = $order->get_items();
        reset( $items );

        $first_item = $items[ key( $items ) ];
        $item_name = $first_item[ 'name' ];
        $item_count = $order->get_item_count();

        $product_name = $item_name;
        if ( $item_count > 1 ) {
            $product_name .= ' 和另外 ' . ( $item_count - 1 ) . ' 件商品';
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
            throw new Exception( '結帳參數有誤，請聯絡網站管理員。錯誤代碼: ' . $response_data->returnCode );
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
            $order->update_status( 'failed', "錯誤代碼: $response_data->returnCode, 錯誤訊息: $response_data->returnMessage" );
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
