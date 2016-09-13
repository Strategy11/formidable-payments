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

		$gateway = self::get_gateway_for_entry( $action, $entry );

		if ( ! empty( $gateway ) ) {
			$class_name = FrmTransAppHelper::get_setting_for_gateway( $gateway, 'class' );
			if ( empty( $class_name ) ) {
				return;
			}

			self::prepare_description( $action, compact( 'entry', 'form' ) );

			$class_name = 'Frm' . $class_name . 'ActionsController';
			$response = $class_name::trigger_gateway( $action, $entry, $form );

			if ( ! $response['success'] ) {
				// the payment failed
				if ( $response['show_errors'] ) {
					self::show_failed_message( compact( 'action', 'entry', 'form', 'response' ) );
				}
			} elseif ( $response['run_triggers'] ) {
				$status = 'complete';
				self::trigger_payment_status_change( compact( 'status', 'action', 'entry' ) );
			}
		}
	}

	private static function get_gateway_for_entry( $action, $entry ) {
		$gateway_field = FrmAppHelper::get_post_param( 'frm_gateway', '', 'absint' );
		if ( empty( $gateway_field ) ) {
			$field = FrmField::getAll( array( 'fi.form_id' => $action->menu_order, 'type' => 'gateway' ) );
			if ( ! empty( $field ) ) {
				$field = reset( $field );
				$gateway_field = $field->id;
			}
		}

		$gateway = '';
		if ( ! empty( $gateway_field ) ) {
			$posted_value = ( isset( $_POST['item_meta'][ $gateway_field ] ) ? sanitize_text_field( $_POST['item_meta'][ $gateway_field ] ) : '' );
			$gateway = isset( $entry->metas[ $gateway_field ] ) ? $entry->metas[ $gateway_field ] : $posted_value;
		}

		return $gateway;
	}

	public static function trigger_gateway( $action, $entry, $form ) {
		// This function must be overridden in a subclass
		return array( 'success' => false, 'run_triggers' => false, 'show_errors' => true );
	}

	public static function show_failed_message( $args ) {
		global $frm_vars;
		$frm_vars['frm_trans'] = array(
			'pay_entry' => $args['entry'],
			'error'     => isset( $args['response']['error'] ) ? $args['response']['error'] : '',
		);

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
		global $frm_vars;
		$message = isset( $frm_vars['frm_trans']['error'] ) ? $frm_vars['frm_trans']['error'] : '';
		if ( empty( $message ) ) {
			$message = __( 'There was an error processing your payment.', 'formidable-payment' );
		}

		$message = '<div class="frm_error_style">' . $message . '</div>';

		return $message;
	}

	public static function fill_entry_from_previous( $values, $field ) {
		global $frm_vars;
		$previous_entry = isset( $frm_vars['frm_trans']['pay_entry'] ) ? $frm_vars['frm_trans']['pay_entry'] : false;
		if ( empty( $previous_entry ) || $previous_entry->form_id != $field->form_id ) {
			return $values;
		}

		if ( is_array( $previous_entry->metas ) && isset( $previous_entry->metas[ $field->id ] ) ) {
			$values['value'] = $previous_entry->metas[ $field->id ];
		}

		return $values;
	}

	public static function trigger_payment_status_change( $atts ) {
		$action = isset( $atts['action'] ) ? $atts['action'] : $atts['payment']->action_id;
		$entry_id = isset( $atts['entry'] ) ? $atts['entry']->id : $atts['payment']->item_id;
		$atts = array( 'trigger' => $atts['status'], 'entry_id' => $entry_id );

		if ( ! isset( $atts['payment'] ) ) {
			$frm_payment = new FrmTransPayment();
			$atts['payment'] = $frm_payment->get_one_by( $entry_id, 'item_id' );
		}

		self::set_fields_after_payment( $action, $atts );
		if ( $atts['payment'] ) {
			self::trigger_actions_after_payment( $atts['payment'] );
		}
	}

	public static function trigger_actions_after_payment( $payment ) {
		if ( ! is_callable( 'FrmFormActionsController::trigger_actions' ) ) {
			return;
		}

		$entry = FrmEntry::getOne( $payment->item_id );
		$trigger_event = ( $payment->status == 'complete' ) ? 'payment-success' : 'payment-failed';
		FrmFormActionsController::trigger_actions( $trigger_event, $entry->form_id, $entry->id );
	}

	public static function set_fields_after_payment( $action, $atts ) {
		do_action( 'frm_payment_status_' . $atts['trigger'], $atts );

		if ( ! is_callable( 'FrmProEntryMeta::update_single_field' ) || empty( $action ) ) {
			return;
		}

		if ( is_numeric( $action ) ) {
			$action = FrmTransAction::get_single_action_type( $action, 'payment' );
		}

		self::change_fields( $action, $atts );
	}

	private static function change_fields( $action, $atts ) {
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
	 * Filter fields in description
	 */
	public static function prepare_description( &$action, $atts ) {
		$description = $action->post_content['description'];
		if ( ! empty( $description ) ) {
			$description = apply_filters( 'frm_content', $description, $atts['form'], $atts['entry'] );
			$action->post_content['description'] = $description;
		}
	}

	/**
	 * Convert the amount into 10.00
	 */
	public static function prepare_amount( $amount, $atts = array() ) {
		if ( isset( $atts['form'] ) ) {
			$amount = apply_filters( 'frm_content', $amount, $atts['form'], $atts['entry'] );
		}

		if ( $amount[0] == '[' && substr( $amount, -1 ) == ']' ) {
			// make sure we don't use a field id as the amount
			$amount = 0;
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

	public static function prepare_settings_for_js( $form_id ) {
		$payment_actions = self::get_actions_for_form( $form_id );

		$action_settings = array();
		foreach ( $payment_actions as $payment_action ) {
			$action_settings[] = array(
				'id'         => $payment_action->ID,
				'address'    => $payment_action->post_content['billing_address'],
				'first_name' => $payment_action->post_content['billing_first_name'],
				'last_name'  => $payment_action->post_content['billing_last_name'],
				'gateways'   => $payment_action->post_content['gateway'],
			);
		}

		return $action_settings;
	}

	public static function get_actions_for_form( $form_id ) {
		$payment_actions = FrmFormAction::get_action_for_form( $form_id, 'payment' );
		if ( empty( $payment_actions ) ) {
			$payment_actions = array();
		}
		return $payment_actions;
	}
}
