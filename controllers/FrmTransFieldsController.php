<?php

class FrmTransFieldsController {

	public static function add_gateway_field_type( $fields ) {
		$fields['gateway'] = __( 'Gateway', 'formidable-payments' );
		return $fields;
	}

	public static function auto_add_gateway_field( $settings, $action ) {
		$form_id = $action['menu_order'];
		$gateway_field = FrmField::getAll( array( 'fi.form_id' => $form_id, 'type' => 'gateway' ) );
		if ( ! $gateway_field ) {
			$new_values = FrmFieldsHelper::setup_new_vars( 'gateway', $form_id );
			$new_values['name'] = __( 'Payment Method', 'formidable-payments' );
			FrmField::create( $new_values );
		}
		return $settings;
	}

	public static function add_gateway_options( $values, $field ) {
		if ( $field->type != 'gateway' ) {
			return $values;
		}

		$values['options'] = self::get_options_for_field( $field );
		$values['use_key'] = true;
		$values['value'] = self::get_first_value( $values['options'] );
		if ( count( $values['options'] ) < 2 && ! FrmAppHelper::is_admin_page( 'formidable' ) ) {
			do_action( 'frm_enqueue_' . $values['value'] . '_scripts', array( 'form_id' => $field->form_id ) );
			$values['type'] = 'hidden';
		}

		return $values;
	}

	public static function get_options_for_field( $field ) {
		$form_id = is_object( $field ) ? $field->form_id : $field['form_id'];
		$gateways = self::get_gateways_for_form( $form_id );
		$gateway_settings = FrmTransAppHelper::get_gateways();

		$options = array();
		foreach ( $gateways as $gateway ) {
			if ( isset( $gateway_settings[ $gateway ] ) ) {
				$options[ $gateway ] = $gateway_settings[ $gateway ]['user_label'];
			}
		}

		return $options;
	}

	public static function get_gateways_for_form( $form_id ) {
		$payment_actions = FrmTransActionsController::get_actions_for_form( $form_id );
		if ( empty( $payment_actions ) ) {
			return array();
		}

		$payment_action = reset( $payment_actions );
		$gateways = $payment_action->post_content['gateway'];
		return $gateways;
	}

	public static function show_in_form_builder( $field ) {
		// Generate field name and HTML id
		$field_name = 'item_meta[' . $field['id'] . ']';
		$html_id = 'field_' . $field['field_key'];

		$field['options'] = self::get_options_for_field( $field );
		if ( empty( $field['value'] ) ) {
			$field['value'] = self::get_first_value( $field['options'] );
		}

		include( FrmTransAppHelper::plugin_path() . '/views/fields/gateway-back-end.php' );
	}

	public static function show_in_form( $field, $field_name, $atts ) {
        $errors = isset( $atts['errors'] ) ? $atts['errors'] : array();
        $html_id = $atts['html_id'];

		echo '<input type="hidden" name="frm_gateway" value="' . esc_attr( $field['id'] ) . '" />';

		foreach ( $field['options'] as $gateway => $label ) {
			do_action( 'frm_enqueue_' . $gateway . '_scripts', array( 'form_id' => $field['form_id'] ) );
		}

		$field['type'] = 'radio';
		include( FrmAppHelper::plugin_path() . '/classes/views/frm-fields/input.php' );
	}

	private static function get_first_value( $options ) {
		reset( $options );
		return key( $options );
	}

	public static function field_type_for_logic( $type ) {
		return ( $type == 'gateway' ) ? 'radio' : $type;
	}
}
