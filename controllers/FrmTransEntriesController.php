<?php

class FrmTransEntriesController {

	public static function entry_payment_expiration_column( $value, $atts ) {
		if ( empty( $value ) ) {
			$expiration = FrmTransEntry::get_entry_expiration( $atts['item'] );
			$value = $expiration ? $expiration : '';
		}

		return $value;
	}

	public static function add_payment_to_csv( $headings, $form_id ) {
		if ( FrmFormAction::form_has_action_type( $form_id, 'payment' ) ) {
			$headings['payments'] = __( 'Payments', 'formidable-payments' );
			$headings['payments_expiration'] = __( 'Expiration Date', 'formidable-payments' );
			$headings['payments_status'] = __( 'Status', 'formidable-payments' );
			add_filter( 'frm_csv_row', 'FrmTransEntriesController::add_payment_to_csv_row', 20, 2 );
		}
		return $headings;
	}

	public static function add_payment_to_csv_row( $row, $atts ) {
		$row['payments'] = 0;
		$atts['item'] = $atts['entry'];
		$row['payments_expiration'] = '';
		$row['payments_complete'] = '';

		$payments = FrmTransEntry::get_completed_payments( $atts['entry'] );
		if ( $payments ) {
			$row['payments_expiration'] = self::entry_payment_expiration_column( '', $atts );
			$row['payments_status'] = 'complete';
			foreach ( $payments as $payment ) {
				$row['payments'] += $payment->amount;
			}
		}

		return $row;
	}

	public static function sidebar_list( $entry ) {
		remove_action( 'frm_show_entry_sidebar', 'FrmPaymentsController::sidebar_list' );

		$frm_payment = new FrmTransPayment();
		$payments = $frm_payment->get_all_for_entry( $entry->id );
		if ( ! $payments ) {
			return;
		}

		$frm_sub = new FrmTransSubscription();
		$subscriptions = $frm_sub->get_all_for_entry( $entry->id );
		$entry_total = 0;

		$date_format = get_option('date_format');

		include( FrmTransAppHelper::plugin_path() . '/views/payments/sidebar_list.php' );
	}
}
