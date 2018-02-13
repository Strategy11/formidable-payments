<?php

class FrmTransSubscriptionsController extends FrmTransCRUDController {

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
		echo $link;
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
					self::change_subscription_status( array(
						'status' => 'future_cancel',
						'sub'    => $sub,
					) );

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

	/**
	 * @since 1.12
	 */
	public static function change_subscription_status( $atts ) {
		$frm_sub = new FrmTransSubscription();
		$frm_sub->update( $atts['sub']->id, array( 'status' => $atts['status'] ) );
		$atts['sub']->status = $atts['status'];

		FrmTransActionsController::trigger_subscription_status_change( $atts['sub'] );
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
