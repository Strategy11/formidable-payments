<?php

class FrmSecurePayPaymentsController {

	public static function create_payment( $args ) {
	  $payment_url = 'https://payments-stest.npe.auspost.zone/v2/payments';
    $auth_token = self::get_auth_token();

    $body = array(
      'merchantCode' => $args['merchantCode'],
      'token' => $args['token'],
      'ip' => $args['ip'],
      'amount' => (int)$args['amount'],
    );

    $response = wp_remote_post( $payment_url,
      array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $auth_token,
          'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode($body),
        'data_format' => 'body',
      )
    );

    return wp_remote_retrieve_body( $response );
  }

	public static function get_auth_token() {
    $auth_token = get_transient('frm_securepay_auth_token');
    if (empty($auth_token)) {
      $auth_object = FrmSecurePayAPI::create_auth_object();
      set_transient('frm_securepay_auth_token', $auth_object['access_token'], $auth_object['expires_in']);
      return $auth_object['access_token'];
    }

    return $auth_token;
  }
}
