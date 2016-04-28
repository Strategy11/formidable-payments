<?php

class FrmTransAppController {

    public static function load_lang() {
        load_plugin_textdomain( 'formidable-payments', false, FrmStrpAppHelper::plugin_folder() . '/languages/' );
    }

    public static function include_updater() {
		FrmTransUpdate::load_hooks();
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
		foreach ( $overdue_subscriptions as $sub ) {
			$last_payment = false;

			$log_message = 'Subscription #' . $sub->id .' run. ';
			if ( $sub->status == 'future_cancel' ) {
				$last_payment = $frm_payment->get_one_by( $sub->id, 'sub_id' );
				$frm_sub->update( $sub->id, array( 'stauts' => 'canceled' ) );
				$status = 'failed';
				$log_message .= 'Failed triggers run on canceled subscription.';
			} else {
				// allow gateways to run their transactions
				do_action( 'frm_run_' . $sub->paysys . '_sub', $sub );

				// get the most recent payment after the gateway has a chance to create one
				$last_payment = $frm_payment->get_one_by( $sub->id, 'sub_id' );
				if ( $last_payment->expire_date < date('Y-m-d') || $last_payment->status != 'complete' ) {
					// the payment has either expired or failed
					$status = 'failed';
				} elseif ( $last_payment->created_at > date('Y-m-d H:i:s', strtotime('-5 minutes') ) ) {
					// a successful payment was just run
					$status = 'complete';
				} else {
					// don't run any triggers
					$status = 'no';
					$last_payment = false;
				}

				$log_message .= $status . ' triggers run ';
				if ( $last_payment ) {
					$log_message .= 'on payment #' . $last_payment->id;
				}
			}

			FrmTransLogsController::log_message( $log_message );

			if ( $last_payment ) {
				FrmTransActionsController::trigger_payment_status_change( array(
					'status' => $status, 'payment' => $last_payment,
				) );
			}

			unset( $sub );
		}
	}
}
