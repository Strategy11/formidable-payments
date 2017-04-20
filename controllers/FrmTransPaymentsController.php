<?php

class FrmTransPaymentsController extends FrmTransCRUDController {

	public static function menu() {
		if ( ! class_exists('FrmAppHelper') ) {
			return;
		}

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

	private static function new_payment(){
		self::get_new_vars();
	}

	private static function create() {
		$frm_payment = new FrmTransPayment();
		if ( $id = $frm_payment->create( $_POST ) ) {
			$message = __( 'Payment was Successfully Created', 'formidable-payments' );
			self::get_edit_vars( $id, '', $message );
		} else {
			$message = __( 'There was a problem creating that payment', 'formidable-payments' );
			self::get_new_vars( $message );
		}
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

		$currency = FrmTransAppHelper::get_currency( 'usd' );

		include( FrmTransAppHelper::plugin_path() . '/views/payments/new.php' );
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
		echo $link;
	}

	public static function show_refund_link( $payment ) {
		$link = self::refund_link( $payment );
		echo $link;
	}

	public static function refund_link( $payment ) {
		if ( $payment->status == 'refunded' ) {
			$link = __( 'Refunded', 'formidable-payments' );
		} else {
			$link = admin_url( 'admin-ajax.php?action=frm_trans_refund&payment_id=' . $payment->id . '&nonce=' . wp_create_nonce( 'frm_trans_ajax' ) );
			$confirm = __( 'Are you sure you want to refund that payment?', 'formidable-payments' );
			$link = '<a href="' . esc_url( $link ) . '" class="frm_trans_ajax_link" data-deleteconfirm="' . esc_attr( $confirm ) . '" data-tempid="' . esc_attr( $payment->id ) . '">';
			$link .= __( 'Refund', 'formidable-payments' );
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
			$refunded = $class_name::refund_payment( $payment->receipt_id, compact( 'payment' ) );
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
			FrmTransActionsController::trigger_payment_status_change( compact( 'status', 'payment' ) );
		}
	}

	/**
	 * Get the receipt ID for a given entry ID
	 *
	 * @since 1.09
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function do_frm_receipt_id_shortcode( $atts ) {
		if ( ! isset( $atts['entry'] ) ) {
			return '';
		}

		if ( is_numeric( $atts['entry'] ) ) {
			$entry_id = $atts['entry'];
		} else {
			$entry_id = FrmEntry::get_id_by_key( $atts['entry'] );

			if ( ! is_numeric( $entry_id ) ) {
				return '';
			}
		}

		$where = array( 'item_id' => $entry_id );
		$receipt_id = FrmDb::get_var( 'frm_payments', $where, 'receipt_id' );

		return $receipt_id;
	}
}
