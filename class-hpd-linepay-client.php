<?php

if ( !defined( 'ABSPATH' ) ) { exit; }

class HPD_LinePay_Client {
    public function __construct( $channel_id, $channel_secret, $sandbox_mode = false ) {
        $this->channel_id = $channel_id;
        $this->channel_secret = $channel_secret;
        $this->sandbox_mode = $sandbox_mode;
    }

    protected function get_api_root_url() {
        return $this->sandbox_mode ? HPD_LINEPAY_SANDBOX_API_ROOT : HPD_LINEPAY_API_ROOT;
    }

    public function reserve( $product_name, $product_image_url, $amount, $currency, $confirm_url, $cancel_url, $order_id, $lang_cd = '', $other_args = array() ) {
        $endpoint = '/payments/request';

        $postdata = array(
            'productName' => $product_name,
            'productImageUrl' => $product_image_url,
            'amount' => $amount,
            'currency' => $currency,
            'confirmUrl' => $confirm_url,
            'cancelUrl' => $cancel_url,
            'orderId' => $order_id,
            'langCd' => $lang_cd,
        );

        return $this->send_request( $endpoint, $postdata );
    }

    public function confirm( $transaction_id, $amount, $currency ) {
        $endpoint = "/payments/$transaction_id/confirm";

        $postdata = array(
            'amount' => $amount,
            'currency' => $currency,
        );

        return $this->send_request( $endpoint, $postdata );
    }

    public function refund( $transaction_id, $refund_amount ) {
        $endpoint = "/payments/$transaction_id/refund";

        $postdata = array(
            'refundAmount' => $refund_amount,
        );

        return $this->send_request( $endpoint, $postdata );
    }

    protected function send_request( $endpoint, $postdata ) {
        $r = wp_remote_post( $this->get_api_root_url() . $endpoint, array(
            'headers' => array(
                'Content-type' => 'application/json; charset=UTF-8',
                'X-LINE-ChannelId' => $this->channel_id,
                'X-LINE-ChannelSecret' => $this->channel_secret,
            ),
            'httpversion' => '1.1',
            'body' => json_encode( $postdata ),
            'timeout' => 30,
        ) );

        $body = wp_remote_retrieve_body( $r );
        return json_decode( $body );
    }
}
