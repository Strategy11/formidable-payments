<?php

class FrmSecurePayActionsController extends FrmTransActionsController {

	public static function trigger_gateway( $action, $entry, $form ) {
		$response = array(
			'success'      => false,
			'run_triggers' => false,
			'show_errors'  => true,
		);
		$atts = compact( 'action', 'entry', 'form' );

		$amount = self::prepare_amount( $action->post_content['amount'], $atts );
		if ( empty( $amount ) || $amount == 000 ) {
			$response['error'] = __( 'Please specify an amount for the payment', 'formidable-securepay' );
			return $response;
		}

		$paymentInfo = self::create_payment_info($atts);
		if ( empty($paymentInfo) ) {
		  $response['error'] = __( 'Error while creating payment info', 'formidable-securepay' );
		  return $response;
    }

		// attempt to charge the customer's card
		if ( 'recurring' === $action->post_content['type'] ) {
      // not implemented yet
      $response['error'] = 'Not implemented yet';
//      $charge = self::trigger_recurring_payment( compact( 'paymentInfo', 'entry', 'action', 'amount' ) );
		} else {
			$charge = self::trigger_one_time_payment( compact( 'paymentInfo', 'form', 'entry', 'action', 'amount' ) );
			$response['run_triggers'] = true;
		}

		if ( $charge === true ) {
			$response['success'] = true;
		} else {
			$response['error'] = $charge;
		}

		return $response;
	}

	private static function create_payment_info( $atts ) {
	  $payment_info = array(
			'user_id' => FrmTransAppHelper::get_user_id_for_current_payment(),
		);

	  if ( isset( $_POST['securepayToken'] ) ) {
			$payment_info['token'] = sanitize_text_field( $_POST['securepayToken'] );
		}

	  if ( isset( $_POST['securepayLast4'] ) ) {
			$payment_info['last4'] = sanitize_text_field( $_POST['securepayLast4'] );
		}

	  if ( isset( $_POST['securepayScheme'] ) ) {
			$payment_info['scheme'] = sanitize_text_field( $_POST['securepayScheme'] );
		}

		if ( ! empty( $atts['action']->post_content['email'] ) ) {
			$payment_info['email'] = apply_filters( 'frm_content', $atts['action']->post_content['email'], $atts['form'], $atts['entry'] );
			if ( $payment_info['email'] === '[email]' ) {
				$payment_info['email'] = FrmProAppHelper::get_current_user_value( 'user_email' );
			}
		}

		self::add_customer_name( $atts, $payment_info );

		return $payment_info;
  }

	private static function add_customer_name( $atts, &$payment_info ) {
		if ( empty( $atts['action']->post_content['billing_first_name'] ) ) {
			return;
		}

		$name = '[' . $atts['action']->post_content['billing_first_name'] . ']';
		if ( ! empty( $atts['action']->post_content['billing_last_name'] ) ) {
			$name .= ' [' . $atts['action']->post_content['billing_last_name'] . ']';
		}

		$payment_info['name'] = apply_filters( 'frm_content', $name, $atts['form'], $atts['entry'] );
	}

	private static function trigger_one_time_payment( $atts ) {
    $settings = new FrmSecurePaySettings();
    $test_mode = $settings->settings->test_mode;
    $merchant_code = $settings->settings->merchant_code;
    $payment_url = 'https://payments-stest.npe.auspost.zone/v2/payments';
    $auth_token = FrmSecurePayPaymentsController::get_auth_token();

    $body = array(
      'merchantCode' => $merchant_code,
      'token' => $atts['paymentInfo']['token'],
      'ip' => $atts['entry']->ip,
      'amount' => (int)$atts['amount'],
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

    $json_response = json_decode(wp_remote_retrieve_body( $response ), TRUE );

    if ($json_response['status'] !== 'paid') {
      // log $json_response['gatewayResponseMessage']
      // TODO: Gives better message as error response
      return $json_response['errorCode'];
    }

//    self::create_new_payment( $atts ); // pending feature
    self::add_transaction_data($atts['entry'], $json_response, $test_mode);

		return true;
	}

	private static function add_transaction_data( $entry, $data, $test_mode ) {
	  $entry_id = $entry->id;
	  $form_id = $entry->form_id;
    $transaction_data = array(
      'created_at' => $data['createdAt'],
      'amount' => $data['amount'],
      'status_code' => $data['errorCode'] ?? 0,
      'status_description' => $data['status'],
      'response_code' => $data['gatewayResponseCode'],
      'response_text' => $data['gatewayResponseMessage'],
      'bank_transaction_id' => $data['bankTransactionId'],
      'gateway_mode' => $test_mode === 1 ? 'test' : 'live',
    );

    self::insert_transaction_data_to_db($entry_id, $form_id, $transaction_data);
  }

  private static function insert_transaction_data_to_db($entry_id, $form_id, $data) {
	  global $wpdb;
	  $table_name = $wpdb->prefix . FrmSecurePayAppController::$transaction_table_name;

	  $wpdb->insert(
      $table_name,
      array(
        'entry_id' => $entry_id,
        'form_id' => $form_id,
        'meta_value' => serialize($data),
      )
    );
  }

	private static function create_new_payment( $atts ) {
		$new_values = array(
			'amount'     => number_format( $atts['amount'], 2, '.', '' ),
			'status'     => $atts['status'],
			'paysys'     => 'securepay',
			'item_id'    => $atts['entry']->id,
			'action_id'  => $atts['action']->ID,
//			'receipt_id' => $atts['charge']->id,
//			'sub_id'     => isset( $atts['charge']->sub_id ) ? $atts['charge']->sub_id : '',
		);

//		if ( isset( $atts['charge']->current_period_end ) ) {
//			$new_values['begin_date']  = date( 'Y-m-d', $atts['charge']->current_period_start );
//			$new_values['expire_date'] = date( 'Y-m-d', $atts['charge']->current_period_end );
//		}
//
//		if ( isset( $atts['meta_value'] ) ) {
//			$new_values['meta_value'] = $atts['meta_value'];
//		}

		$frm_payment = new FrmTransPayment();
		$payment_id  = $frm_payment->create( $new_values );
		return $payment_id;
	}

	public static function add_action_defaults( $defaults ) {
	  $defaults['currency'] = 'aud';
//	  echo '<pre>'; var_dump($defaults); echo '</pre>';
//		$defaults['card_token'] = '';
//		$defaults['capture'] = '';
		return $defaults;
	}

	public static function add_action_options( $atts ) {
		$form_action    = $atts['form_action'];
		$action_control = $atts['action_control'];
		include FrmSecurePayAppController::path() . '/views/action-settings/options.php';
	}

	public static function load_scripts( $params ) {
		if ( FrmAppHelper::is_admin_page( 'formidable-entries' ) ) {
			return;
		}

		if ( wp_script_is( 'frmsecurepay_actions', 'enqueued' ) ) {
			return;
		}

		wp_register_script( 'frmsecurepay_actions', FrmSecurePayAppController::plugin_url() . '/js/_actions.js', array('jquery'), null, true );
		wp_enqueue_script( 'frmsecurepay_actions' );
	}

}