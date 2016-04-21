<?php

class FrmTransFieldsController {

	public static function gateway_field( $form ) {
		$payment_actions = FrmFormAction::get_action_for_form( $form->id, 'payment' );
		if ( empty( $payment_actions ) ) {
			return;
		}

		$payment_action = reset( $payment_actions );
		$gateways = $payment_action->post_content['gateway'];
		$gateway_settings = FrmTransAppHelper::get_gateways();

		foreach ( $gateways as $gateway ) {
			do_action( 'frm_enqueue_' . $gateway . '_scripts', array( 'form_id' => $form->id ) );
		}

		include( FrmTransAppHelper::plugin_path() . '/views/fields/gateway.php' );
	}
}
