<?php

class FrmTransPaymentsController {

	public static function menu() {
		$frm_settings = FrmAppHelper::get_settings();

		remove_action( 'admin_menu', 'FrmPaymentsController::menu', 26 );
		add_submenu_page( 'formidable', $frm_settings->menu . ' | Payments', 'Payments', 'frm_view_entries', 'formidable-payments', 'FrmTransPaymentsController::route' );
	}

	public static function route() {
		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action, '', 'get', 'sanitize_title' );
		$type = FrmAppHelper::get_param( 'type', '', 'get', 'sanitize_title' );

		$class_name = ( $type == 'subscriptions' ) ? 'FrmTransSubscriptionsController' : 'FrmTransPaymentsController';
		if ( $action == 'new' ) {
			self::new_payment();
		} elseif ( method_exists( $class_name, $action ) ) {
			$class_name::$action();
		} else {
			FrmTransListsController::route( $action );
		}
	}

	private static function show( $id = 0 ) {
		if ( ! $id ) {
			$id = FrmAppHelper::get_param( 'id', 0, 'get', 'sanitize_text_field' );
			if ( ! $id ) {
				wp_die( __( 'Please select a payment to view', 'formidable-payments' ) );
			}
		}
    
		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		$date_format = get_option('date_format');
		$user_name = FrmTransAppHelper::get_user_link( $payment->user_id );

		include( FrmTransAppHelper::plugin_path() . '/views/payments/show.php' );
	}

	private static function new_payment(){
		self::get_new_vars();
	}

	private static function create(){
		$message = $error = '';

		$frm_payment = new FrmTransPayment();
		if ( $id = $frm_payment->create( $_POST ) ) {
			$message = __( 'Payment was Successfully Created', 'formidable-payments' );
			self::get_edit_vars( $id, '', $message );
		} else {
			$error = __( 'There was a problem creating that payment', 'formidable-payments' );
			self::get_new_vars( $error );
		}
	}

	private static function edit() {
		$id = FrmAppHelper::get_param('id');
		self::get_edit_vars( $id );
	}

	private static function update() {
		FrmAppHelper::permission_check( 'administrator' );

		$id = FrmAppHelper::get_param('id');
		$message = $error = '';
		$frm_payment = new FrmTransPayment();
		if ( $frm_payment->update( $id, $_POST ) ) {
			$message = __( 'Payment was Successfully Updated', 'formidable-payments' );
		} else {
			$error = __( 'There was a problem updating that payment', 'formidable-payments' );
		}

		self::get_edit_vars( $id, $error, $message );
	}
    
	private static function destroy(){
		FrmAppHelper::permission_check( 'administrator' );

		$message = '';
		$frm_payment = new FrmTransPayment();
		if ( $frm_payment->destroy( FrmAppHelper::get_param('id') ) ) {
			$message = __( 'Payment was Successfully Deleted', 'formidable-payments' );
		}

		FrmTransListsController::display_list( $message );
	}

	private static function get_new_vars( $error = '' ) {
		global $wpdb;

		$frm_payment = new FrmTransPayment();
		$get_defaults = $frm_payment->get_defaults();
		$defaults = array();
		foreach ( $get_defaults as $name => $values ) {
			$defaults[ $name ] = $values['default'];
		}
		$defaults['paysys'] = 'manual';

		$payment = (object) array();
		foreach ( $defaults as $var => $default ) {
			$payment->$var = FrmAppHelper::get_param( $var, $default, 'post', 'sanitize_text_field' );
		}

		$frm_payment_settings = new FrmPaymentSettings();
		$currency = FrmTransAppHelper::get_currency( $frm_payment_settings->settings->currency );

		include( FrmTransAppHelper::plugin_path() . '/views/payments/new.php' );
	}
    
	private static function get_edit_vars( $id, $errors = '', $message = '' ) {
		if ( ! $id ) {
			die( __( 'Please select a payment to view', 'formidable-payments' ) );
		}
            
		if ( ! current_user_can('frm_edit_entries') ) {
			return self::show( $id );
		}
            
		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_payments p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		$currency = FrmTransAppHelper::get_action_setting( 'currency', array( 'payment' => $payment ) );
		$currency = FrmTransAppHelper::get_currency( $currency );
        
		if ( $_POST && isset( $_POST['receipt_id'] ) ) {
			foreach ( $payment as $var => $val ) {
				if ( $var == 'id' ) {
					continue;
				}
				$var = sanitize_text_field( $var );
				$val = sanitize_text_field( $val );
				$payment->$var = FrmAppHelper::get_param( $var, $val, 'post', 'sanitize_text_field' );
			}
		}

		include( FrmTransAppHelper::plugin_path() . '/views/payments/edit.php' );
	}

	public static function load_sidebar_actions( $payment ) {
		$icon = ( $payment->status == 'complete' ) ? 'yes' : 'no-alt';
		$date_format = __( 'M j, Y @ G:i' );
		$created_at = FrmAppHelper::get_localized_date( $date_format, $payment->created_at );

		FrmTransActionsController::actions_js();

		include( FrmTransAppHelper::plugin_path() . '/views/payments/sidebar_actions.php' );
	}

	public static function show_receipt_link( $payment ) {
		$link = apply_filters( 'frm_pay_' . $payment->paysys . '_receipt', $payment->receipt_id );
		echo wp_kses_post( $link );
	}

	public static function show_refund_link( $payment ) {
		$link = self::refund_link( $payment );

		echo wp_kses_post( $link );
	}

	public static function refund_link( $payment ) {
		if ( $payment->status == 'refunded' ) {
			$link = __( 'Refunded', 'formidable-stripe' );
		} else {
			$link = admin_url( 'admin-ajax.php?action=frm_trans_refund&payment_id=' . $payment->id . '&nonce=' . wp_create_nonce( 'frm_trans_ajax' ) );
			$link = '<a href="' . esc_url( $link ) . '" class="frm_trans_ajax_link" data-deleteconfirm="' . esc_attr__( 'Are you sure you want to refund that payment?', 'formidable-stripe' ) . '" data-tempid="' . esc_attr( $payment->id ) . '">';
			$link .= __( 'Refund', 'formidable-stripe' );
			$link .= '</a>';
		}
		$link = apply_filters( 'frm_pay_' . $payment->paysys . '_refund_link', $link, $payment );

		return $link;
	}

	public static function refund_payment() {
		FrmAppHelper::permission_check('frm_edit_entries');
		check_ajax_referer( 'frm_trans_ajax', 'nonce' );

		$payment_id = FrmAppHelper::get_param( 'payment_id', '', 'get', 'sanitize_text_field' );
		if ( $payment_id ) {
			$frm_payment = new FrmTransPayment();
			$payment = $frm_payment->get_one( $payment_id );

			$class_name = FrmTransAppHelper::get_setting_for_gateway( $payment->paysys, 'class' );
			$class_name = 'Frm' . $class_name . 'ApiHelper';
			$refunded = $class_name::refund_payment( $payment->receipt_id );
			if ( $refunded ) {
				self::change_payment_status( $payment, 'refunded' );
				$message = __( 'Refunded', 'formidable-payments' );
			} else {
				$message = __( 'Failed', 'formidable-payments' );
			}
		} else {
			$message = __( 'Oops! No payment was selected for refund.', 'formidable-payments' );
		}

		echo $message;
		wp_die();
	}

	public static function change_payment_status( $payment, $status ) {
		$frm_payment = new FrmTransPayment();
		if ( $status != $payment->status ) {
			$frm_payment->update( $payment->id, array( 'status' => $status ) );
			$atts = array( 'trigger' => $status, 'entry_id' => $payment->item_id );
			FrmTransActionsController::set_fields_after_payment( $payment->action_id, $atts );
			FrmTransAppHelper::trigger_actions_after_payment( $payment );
		}
	}
}
