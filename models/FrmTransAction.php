<?php

class FrmTransAction extends FrmFormAction {

	function __construct() {
		$action_ops = array(
			'classes'  => 'dashicons dashicons-cart',
			'limit'    => 99,
			'active'   => true,
			'priority' => 31, // after user registration
			'event'    => array( 'create' ),
		);
		
		$this->FrmFormAction( 'payment', __( 'Collect a Payment', 'formidable-payments' ), $action_ops );
		add_action( 'wp_ajax_frmtrans_after_pay', array( $this, 'add_new_pay_row' ) );
	}

	function form( $form_action, $args = array() ) {	    
		global $wpdb;

		$list_fields = self::get_defaults();

		$action_control = $this;
		$options = $form_action->post_content;
		$gateways = FrmTransAppHelper::get_gateways();
		unset( $gateways['manual'] );
	    
		include( FrmTransAppHelper::plugin_path() . '/views/action-settings/options.php' );
	}

	function get_defaults() {
		$defaults = array(
			'description' => '',
			'email'       => '',
			'amount'      => '',
			'type'        => '',
			'interval_count' => 1,
			'interval'    => 'month',
			'currency'    => 'usd',
			'gateway'     => array(),
			'change_field' => array(),
		);
		$defaults = apply_filters( 'frm_pay_action_defaults', $defaults );
		return $defaults;
	}

	function add_new_pay_row() {
		$form_id = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );
		$row_num = FrmAppHelper::get_post_param( 'row_num', '', 'absint' );
		$action_id = FrmAppHelper::get_post_param( 'email_id', '', 'absint' );

		$form_action = $this->get_single_action( $action_id );
		if ( empty( $form_action ) ) {
			$form_action = new stdClass();
			$form_action->ID = $action_id;
			$this->_set( $action_id );
		}

		$form_action->post_content['change_field'][ $row_num ] = array( 'id' => '', 'value' => '', 'status' => '' );
		$this->after_pay_row( compact( 'form_id', 'row_num', 'form_action' ) );

		wp_die();
	}

	function after_pay_row( $atts ) {
		$id = 'frmtrans_after_pay_row_' . absint( $atts['form_action']->ID ) . '_' . $atts['row_num'];
		$atts['name'] = $this->get_field_name( 'change_field' );
		$atts['form_fields'] = $this->get_field_options( $atts['form_id'] );
		$action_control = $this;

		include( FrmTransAppHelper::plugin_path() . '/views/action-settings/_after_pay_row.php' );
	}

	function after_payment_status( $atts ) {
		$status = array(
			'complete' => __( 'Completed', 'formidable-payments' ),
			'failed'   => __( 'Failed', 'formidable-payments' ),
			'refunded' => __( 'Refunded', 'formidable-payments' ),
		);

		$name = $this->get_field_name( 'change_field' );
		$input = '<select name="' . esc_attr( $name ) . '[' . absint( $atts['row_num'] ) . '][status]">';
		foreach ( $status as $value => $name ) {
			$selected_value = $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['status'];
			$selected = selected( $selected_value, $value, false );
			$input .= '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
		}
		$input .= '</select>';
		return $input;
	}

	function after_payment_field_dropdown( $atts ) {
		$name = $this->get_field_name( 'change_field' );
		$dropdown = '<select name="' . esc_attr( $name ) . '[' . absint( $atts['row_num'] ) . '][id]" >';
		$dropdown .= '<option value="">' . __( '&mdash; Select Field &mdash;', 'formidable-payments' ) . '</option>';

		$form_fields = $this->get_field_options( $atts['form_id'] );
		foreach ( $form_fields as $field ) {
			$selected_value = $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['id'];
			$selected = selected( $selected_value, $field->id, false );
			$label = FrmAppHelper::truncate( $field->name, 20 );
			$dropdown .= '<option value="' . esc_attr( $field->id ) . '" '. $selected . '>' . $label . '</option>';
		}
		$dropdown .= '</select>';
		return $dropdown;
	}

	private function get_field_options( $form_id ) {
		$form_fields = FrmField::getAll( array(
			'fi.form_id' => absint( $form_id ),
			'fi.type not' => array( 'divider', 'end_divider', 'html', 'break', 'captcha', 'rte', 'form' ),
		), 'field_order' );
		return $form_fields;
	}

	/**
	 * This is here for < v2.01
	 */
	public static function get_single_action_type( $action_id, $type = '' ) {
		$action_control = FrmFormActionsController::get_form_actions( 'payment' );
		return $action_control->get_single_action( $action_id );
	}
}
