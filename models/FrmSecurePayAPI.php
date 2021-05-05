<?php
/**
 * Communicate with SecurePay
 */
class FrmSecurePayAPI {

	public static function create_auth_object() {
	  $settings = new FrmSecurePaySettings();
    $test_mode = $settings->settings->test_mode;
    $client_id = $settings->settings->client_id;
    $client_secret = $settings->settings->client_secret;

    if ($test_mode) {
      $auth_url = 'https://hello.sandbox.auspost.com.au/oauth2/ausujjr7T0v0TTilk3l5/v1/token';
    } else {
      $auth_url = 'https://hello.auspost.com.au/oauth2/ausrkwxtmx9Jtwp4s356/v1/token';
    }

	  $response = wp_remote_post( $auth_url,
      array(
        'headers' => array(
          'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
        ),
        'body' => array(
          'grant_type' => 'client_credentials',
          'scope' => 'https://api.payments.auspost.com.au/payhive/payments/write',
        ),
      )
    );

    $json_response = json_decode(wp_remote_retrieve_body( $response ), TRUE );

    if ( isset($json_response['access_token']) && isset($json_response['expires_in']) ) {
      return $json_response;
    }
  }

  public static function maybe_show_message( $form ) {
		$intent_id = FrmAppHelper::simple_get( 'payment_intent', '', 'sanitize_text_field' );
		if ( ! isset( $_GET['frmstrp'] ) || ! $intent_id ) {
			return $form;
		}

		$frm_payment = new FrmTransPayment();
		$payment     = $frm_payment->get_one_by( $intent_id, 'receipt_id' );
		if ( ! $payment ) {
			return $form;
		}

		$entry_id = FrmAppHelper::simple_get( 'frmsecurepay', '', 'absint' );
		if ( $entry_id !== $payment->item_id || ! FrmStrpAppHelper::stripe_is_configured() ) {
			return $form;
		}

		// Check if intent was complete.
		$intent = FrmStrpAppHelper::call_stripe_helper_class( 'get_intent', $intent_id );

		// If failed, show last error message.
		$failed = array( 'requires_source', 'requires_payment_method', 'canceled' );
		if ( in_array( $intent->status, $failed, true ) ) {
			$message = '<div class="frm_error_style">' . $intent->last_payment_error->message . '</div>';
			self::insert_error_message( $message, $form );
			return $form;
		}

		$atts = array(
			'entry' => FrmEntry::getOne( $entry_id ),
		);
		self::prepare_success_atts( $atts );

		$atts['fields'] = FrmFieldsHelper::get_form_fields( $atts['form']->id );

		ob_start();
		FrmFormsController::run_success_action( $atts );
		$message = ob_get_contents();
		ob_end_clean();

		return $message;
	}

	public static function add_hidden_token_field( $form ) {
		$posted_form = FrmAppHelper::get_param( 'form_id', 0, 'post', 'absint' );
		if ( $posted_form != $form->id || FrmFormsController::just_created_entry( $form->id ) ) {
			// Check to make sure the correct form was submitted.
			// Was an entry already created and the form should be loaded fresh?
			return;
		}

		if ( isset( $_POST['securepayToken'] ) ) {
			echo '<input type="hidden" name="securepayToken" value="' . esc_attr( wp_unslash( $_POST['securepayToken'] ) ) . '"/>';
			return;
		}
	}

	private static function prepare_success_atts( &$atts ) {
		$atts['form']     = FrmForm::getOne( $atts['entry']->form_id );
		$atts['entry_id'] = $atts['entry']->id;

		$opt = 'success_action';
		$atts['conf_method'] = ( isset( $atts['form']->options[ $opt ] ) && ! empty( $atts['form']->options[ $opt ] ) ) ? $atts['form']->options[ $opt ] : 'message';
	}

	private static function insert_error_message( $message, &$form ) {
		$add_after = '<fieldset>';
		$pos = strpos( $form, $add_after );
		if ( $pos !== false ) {
			$form = substr_replace( $form, $add_after . $message, $pos, strlen( $add_after ) );
		}
	}
}
