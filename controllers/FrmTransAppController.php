<?php

class FrmTransAppController {

    public static function load_lang() {
        load_plugin_textdomain( 'formidable-payments', false, FrmTransAppHelper::plugin_folder() . '/languages/' );
    }

    public static function include_updater() {
		$path = FrmTransAppHelper::plugin_path();
		$is_nested = substr_count( $path, 'formidable' ) > 1;
		if ( ! $is_nested ) {
			FrmTransUpdate::load_hooks();
		}
    }

	public static function install( $old_db_version = false ) {
		if ( ! wp_next_scheduled( 'frm_payment_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'frm_payment_cron' );
		}

		$db = new FrmTransDb();
		$db->upgrade( $old_db_version );
	}

	public static function remove_cron() {
		wp_clear_scheduled_hook( 'frm_payment_cron' );
	}

	public static function run_payment_cron() {
		$frm_sub = new FrmTransSubscription();
		$frm_payment = new FrmTransPayment();

		$overdue_subscriptions = $frm_sub->get_overdue_subscriptions();
		FrmTransLog::log_message( count( $overdue_subscriptions ) . ' subscriptions found to be processed.' );

		foreach ( $overdue_subscriptions as $sub ) {
			$last_payment = $frm_payment->get_one_by( $sub->id, 'sub_id' );

			$log_message = 'Subscription #' . $sub->id .': ';
			if ( $sub->status == 'future_cancel' ) {
				FrmTransSubscriptionController::change_subscription_status( array(
					'status' => 'canceled',
					'sub'    => $sub,
				) );

				$status = 'failed';
				$log_message .= 'Failed triggers run on canceled subscription. ';
			} else {
				// allow gateways to run their transactions
				do_action( 'frm_run_' . $sub->paysys . '_sub', $sub );

				// get the most recent payment after the gateway has a chance to create one
				$check_payment = $frm_payment->get_one_by( $sub->id, 'sub_id' );
				$new_payment = ( $check_payment->id != $last_payment->id );
				$last_payment = $check_payment;
				$status = 'no';

				if ( ! $last_payment ) {
					$log_message .= 'No payments found for subscription #' . $sub->id . '. ';
					self::add_one_fail( $sub );
				} elseif ( $new_payment ) {
					$status = $last_payment->status;
					self::update_sub_for_new_payment( $sub, $last_payment );
				} elseif ( $last_payment->expire_date < date('Y-m-d') ) {
					// the payment has expired, and no new payment was made
					$status = 'failed';
					self::add_one_fail( $sub );
				} else {
					// don't run any triggers
					$last_payment = false;
				}

				$log_message .= $status . ' triggers run ';
				if ( $last_payment ) {
					$log_message .= 'on payment #' . $last_payment->id;
				}
			}

			FrmTransLog::log_message( $log_message );

			self::maybe_trigger_changes( array( 'status' => $status, 'payment' => $last_payment ) );

			unset( $sub );
		}
	}

	private static function update_sub_for_new_payment( $sub, $last_payment ) {
		$frm_sub = new FrmTransSubscription();
		if ( $last_payment->status == 'complete' ) {
			$frm_sub->update( $sub->id, array( 'fail_count' => 0, 'next_bill_date' => $last_payment->expire_date ) );
		} elseif ( $last_payment->status == 'failed' ) {
			self::add_one_fail( $sub );
		}
	}

	/**
	 * Add to the fail count.
	 * If the subscription has failed > 3 times, set it to canceled
	 */
	private static function add_one_fail( $sub ) {
		$frm_sub = new FrmTransSubscription();
		$fail_count = $sub->fail_count + 1;
		$new_values = compact( 'fail_count' );
		$frm_sub->update( $sub->id, $new_values );

		if ( $fail_count > 3 ) {
			FrmTransSubscriptionController::change_subscription_status( array(
				'status' => 'canceled',
				'sub'    => $sub,
			) );
		}
	}

	private static function maybe_trigger_changes( $atts ) {
		if ( $atts['payment'] ) {
			FrmTransActionsController::trigger_payment_status_change( $atts );
		}
	}
}
