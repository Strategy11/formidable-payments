<?php

class FrmTransSubscriptionsController {

	public static function show( $id = 0 ) {
		if ( ! $id ) {
			$id = FrmAppHelper::get_param( 'id', 0, 'get', 'sanitize_text_field' );
			if ( ! $id ) {
				wp_die( __( 'Please select a subscription to view', 'formidable-payments' ) );
			}
		}
    
		global $wpdb;
		$subscription = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_subscriptions p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

		$date_format = get_option('date_format');
		$user_name = FrmTransAppHelper::get_user_link( $subscription->user_id );

		include( FrmTransAppHelper::plugin_path() . '/views/subscriptions/show.php' );
	}

	public static function edit() {
		$id = FrmAppHelper::get_param('id');
		self::get_edit_vars( $id );
	}

	public static function update() {
		FrmAppHelper::permission_check( 'administrator' );

		$id = FrmAppHelper::get_param('id');
		$message = $error = '';
		$frm_payment = new FrmTransSubscription();
		if ( $frm_payment->update( $id, $_POST ) ) {
			$message = __( 'Subscription was Successfully Updated', 'formidable-payments' );
		} else {
			$error = __( 'There was a problem updating that subscription', 'formidable-payments' );
		}

		self::get_edit_vars( $id, $error, $message );
	}
    
	public static function destroy(){
		FrmAppHelper::permission_check( 'administrator' );

		$message = '';
		$frm_payment = new FrmTransSubscription();
		if ( $frm_payment->destroy( FrmAppHelper::get_param('id') ) ) {
			$message = __( 'Subscription was Successfully Deleted', 'formidable-payments' );
		}

		FrmTransListsController::display_list( $message );
	}
    
	private static function get_edit_vars( $id, $errors = '', $message = '' ) {
		if ( ! $id ) {
			die( __( 'Please select a subscription to view', 'formidable-payments' ) );
		}
            
		if ( ! current_user_can('frm_edit_entries') ) {
			return self::show( $id );
		}
            
		global $wpdb;
		$payment = $wpdb->get_row( $wpdb->prepare( "SELECT p.*, e.user_id FROM {$wpdb->prefix}frm_subscriptions p LEFT JOIN {$wpdb->prefix}frm_items e ON (p.item_id = e.id) WHERE p.id=%d", $id ) );

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

		include( FrmTransAppHelper::plugin_path() . '/views/subscriptions/edit.php' );
	}

	public static function load_sidebar_actions( $subscription ) {
		$date_format = __( 'M j, Y @ G:i' );

		FrmTransActionsController::actions_js();

		$frm_payment = new FrmTransPayment();
		$payments = $frm_payment->get_all_by( $subscription->id, 'sub_id' );

		include( FrmTransAppHelper::plugin_path() . '/views/subscriptions/sidebar_actions.php' );
	}

	public static function show_cancel_link( $sub ) {
		if ( ! isset( $sub->user_id ) ) {
			global $wpdb;
			$sub->user_id = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . $wpdb->prefix . 'frm_items WHERE id=%d', $sub->item_id ) );
		}

		$link = self::cancel_link( $sub );
		echo wp_kses_post( $link );
	}

	public static function cancel_link( $sub ) {
		if ( $sub->status == 'active' ) {
			$link = admin_url( 'admin-ajax.php?action=frm_trans_cancel&sub=' . $sub->id . '&nonce=' . wp_create_nonce( 'frm_trans_ajax' ) );
			$link = '<a href="' . esc_url( $link ) . '" class="frm_trans_ajax_link" data-deleteconfirm="' . esc_attr__( 'Are you sure you want to cancel that subscription?', 'formidable-payments' ) . '" data-tempid="' . esc_attr( $sub->id ) . '">';
			$link .= __( 'Cancel', 'formidable-payments' );
			$link .= '</a>';
		} else {
			$link = __( 'Canceled', 'formidable-payments' );
		}
		$link = apply_filters( 'frm_pay_' . $sub->paysys . '_cancel_link', $link, $sub );

		return $link;
	}

	public static function cancel_subscription() {
		check_ajax_referer( 'frm_trans_ajax', 'nonce' );

		$sub_id = FrmAppHelper::get_param( 'sub', '', 'get', 'sanitize_text_field' );
		if ( $sub_id ) {
			$frm_sub = new FrmTransSubscription();
			$sub = $frm_sub->get_one( $sub_id );
			if ( $sub ) {
				$class_name = FrmTransAppHelper::get_setting_for_gateway( $sub->paysys, 'class' );
				$class_name = 'Frm' . $class_name . 'ApiHelper';
				$canceled = $class_name::cancel_subscription( $sub->sub_id );
				if ( $canceled ) {
					$frm_sub->update( $sub->id, array( 'status' => 'future_cancel' ) );
					$message = __( 'Canceled', 'formidable-payments' );
				} else {
					$message = __( 'Failed', 'formidable-payments' );
				}
			} else {
				$message = __( 'That subscription was not found', 'formidable-payments' );
			}

		} else {
			$message = __( 'Oops! No subscription was selected for cancelation.', 'formidable-payments' );
		}

		echo $message;
		wp_die();
	}

	public static function list_subscriptions_shortcode() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$frm_sub = new FrmTransSubscription();
		$subscriptions = $frm_sub->get_all_for_user( get_current_user_id() );
		if ( empty( $subscriptions ) ) {
			return;
		}

		FrmTransActionsController::actions_js();

		ob_start();
		include( FrmTransAppHelper::plugin_path() . '/views/subscriptions/list_shortcode.php' );
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}
