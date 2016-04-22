<?php

class FrmTransActionsController {

	public static function register_actions( $actions ) {
		$actions['payment'] = 'FrmTransAction';
		return $actions;
	}

	public static function actions_js() {
		wp_enqueue_script( 'frmtrans_admin', FrmTransAppHelper::plugin_url() . '/js/frmtrans_admin.js' );
		wp_localize_script( 'frmtrans_admin', 'frm_trans_vars', array(
			'nonce'   => wp_create_nonce( 'frm_trans_ajax' ),
		) );
	}

	public static function add_payment_trigger( $triggers ) {
		$triggers['payment-success'] = __( 'Successful Payment', 'formidable-payments' );
		$triggers['payment-failed'] = __( 'Failed Payment', 'formidable-payments' );
		return $triggers;
	}

	public static function add_trigger_to_action( $options ) {
		$options['event'][] = 'payment-success';
		$options['event'][] = 'payment-failed';
		return $options;
	}

	public static function trigger_action( $action, $entry, $form ) {
		// get the gateway for this payment
        $gateway = FrmAppHelper::get_post_param( 'frm_gateway', '', 'sanitize_text_field' );
		if ( ! empty( $gateway ) ) {
			$class_name = FrmTransAppHelper::get_setting_for_gateway( $gateway, 'class' );
			$class_name = 'Frm' . $class_name . 'ActionsController';
			$response = $class_name::trigger_gateway( $action, $entry, $form );

			if ( ! $response['success'] ) {
				// the payment failed
				if ( $response['show_errors'] ) {
					self::show_failed_message( compact( 'action', 'entry', 'form' ) );
				}
			} elseif ( $response['run_triggers'] ) {
				$after_pay_atts = array( 'trigger' => 'complete', 'entry_id' => $entry->id );
				self::set_fields_after_payment( $action, $after_pay_atts );
			}
		}
	}

	public static function trigger_gateway( $action, $entry, $form ) {
		// This function must be overridden in a subclass
		return array( 'success' => false, 'run_triggers' => false, 'show_errors' => true );
	}

	public static function show_failed_message( $args ) {
		global $frm_vars;
		$frm_vars['pay_entry'] = $args['entry'];

		add_filter( 'frm_success_filter', 'FrmTransActionsController::force_message_after_create' );
		add_filter( 'frm_pre_display_form', 'FrmTransActionsController::include_form_with_sucess' );
		add_filter( 'frm_main_feedback', 'FrmTransActionsController::replace_success_message', 5 );
		add_filter( 'frm_setup_new_fields_vars', 'FrmTransActionsController::fill_entry_from_previous', 20, 2 );
	}

	public static function force_message_after_create() {
		return 'message';
	}

	public static function include_form_with_sucess( $form ) {
		$form->options['show_form'] = 1;
		return $form;
	}

	public static function replace_success_message() {
		$message = __( 'There was an error processing your payment.', 'formidable-payment' );
		$message = '<div class="frm_error_style">' . $message . '</div>';

		return $message;
	}

	public static function fill_entry_from_previous( $values, $field ) {
		global $frm_vars;
		$previous_entry = isset( $frm_vars['pay_entry'] ) ? $frm_vars['pay_entry'] : false;
		if ( empty( $previous_entry ) || $previous_entry->form_id != $field->form_id ) {
			return $values;
		}

		if ( is_array( $previous_entry->metas ) && isset( $previous_entry->metas[ $field->id ] ) ) {
			$values['value'] = $previous_entry->metas[ $field->id ];
		}

		return $values;
	}

	public static function set_fields_after_payment( $action, $atts ) {
		if ( ! is_callable( 'FrmProEntryMeta::update_single_field' ) || empty( $action ) ) {
			return;
		}

		if ( is_numeric( $action ) ) {
			$action = FrmTransAction::get_single_action_type( $action, 'payment' );
		}

		if ( empty( $action->post_content['change_field'] ) ) {
			return;
		}

		foreach ( $action->post_content['change_field'] as $change_field ) {
			$is_trigger_for_field = $change_field['status'] == $atts['trigger'];
			if ( $is_trigger_for_field ) {
				FrmProEntryMeta::update_single_field( array(
					'entry_id' => $atts['entry_id'],
					'field_id' => $change_field['id'],
					'value'    => $change_field['value'],
				) );
			}
		}
	}

	/**
	 * Convert the amount into 10.00
	 */
	public static function prepare_amount( $amount, $atts = array() ) {
		if ( isset( $atts['form'] ) ) {
			$amount = apply_filters( 'frm_content', $amount, $atts['form'], $atts['entry'] );
		}

		$currency = self::get_currency_for_action( $atts );

		$total = 0;
		foreach ( (array) $amount as $a ) {
			$this_amount = self::get_amount_from_string( $a );
			self::maybe_use_decimal( $this_amount, $currency );
			self::normalize_number( $this_amount, $currency );

			$total += $this_amount;
			unset( $a, $this_amount, $matches );
		}

		return number_format ( $total, $currency['decimals'], '.', '' );
	}

	public static function get_currency_for_action( $atts ) {
		$currency = 'usd';
		if ( isset( $atts['form'] ) ) {
			$currency = $atts['action']->post_content['currency'];
		} elseif ( isset( $atts['currency'] ) ) {
			$currency = $atts['currency'];
		}

		return FrmTransAppHelper::get_currency( $currency );
	}

	private static function get_amount_from_string( $amount ) {
		$amount = trim( $amount );
		preg_match_all( '/[0-9,.]*\.?\,?[0-9]+/', $amount, $matches );
		$amount = $matches ? end( $matches[0] ) : 0;
		return $amount;
	}

	private static function maybe_use_decimal( &$amount, $currency ) {
		if ( $currency['thousand_separator'] == '.' ) {
			$amount_parts = explode( '.', $amount );
			$used_for_decimal = ( count( $amount_parts ) == 2 && strlen( $amount_parts[1] ) == 2 );
			if ( $used_for_decimal ) {
				$amount = str_replace( '.', $currency['decimal_separator'], $amount );
			}
		}
	}

	private static function normalize_number( &$amount, $currency ) {
		$amount = str_replace( $currency['thousand_separator'], '', $amount );
		$amount = str_replace( $currency['decimal_separator'], '.', $amount );
		$amount = number_format( (float) $amount, $currency['decimals'], '.', '' );
	}
}
