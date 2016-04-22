<?php

class FrmTransAppHelper {

	public static function plugin_path() {
		return dirname( dirname( __FILE__ ) );
	}

    public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-payments.php' );
    }

	public static function get_gateways() {
		$gateways = array(
			'manual' => array(
				'label' => __( 'Manual', 'formidable-payments' ),
				'user_label' => __( 'Manual', 'formidable-payments' ),
				'class' => 'Trans',
			),
		);
		$gateways = apply_filters( 'frm_payment_gateways', $gateways );
		return $gateways;
	}

	public static function get_setting_for_gateway( $gateway, $setting ) {
		$gateways = self::get_gateways();
		$value = '';
		if ( isset( $gateways[ $gateway ] ) ) {
			$value = $gateways[ $gateway ][ $setting ];
		}
		return $value;
	}

	public static function show_status( $status ) {
		$statuses = array_merge( self::get_payment_statuses(), self::get_subscription_statuses() );
		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
	}

	public static function get_payment_statuses() {
		return array(
			'pending'  => __( 'Pending', 'formidable-payments' ),
			'complete' => __( 'Completed', 'formidable-payments' ),
			'failed'   => __( 'Failed', 'formidable-payments' ),
			'refunded' => __( 'Refunded', 'formidable-payments' ),
		);
	}

	public static function get_subscription_statuses() {
		return array(
			'pending'  => __( 'Pending', 'formidable-payments' ),
			'active'   => __( 'Active', 'formidable-payments' ),
			'future_cancel' => __( 'Canceled', 'formidable-payments' ),
			'canceled' => __( 'Canceled', 'formidable-payments' ),
			'void'     => __( 'Void', 'formidable-payments' ),
		);
	}

	public function add_note_to_payment( &$payment_values ) {
		$payment_values['meta_value'] = isset( $payment_values['meta_value'] ) ? (array) $payment_values['meta_value'] : array();
		$payment_values['meta_value'][] = array(
			'message' => sprintf( __( 'Payment %s', 'formidable-payments' ), $status ),
			'date'    => date( 'Y-m-d H:i:s' ),
		);
	}

	public function trigger_actions_after_payment( $payment ) {
		if ( ! is_callable( 'FrmFormActionsController::trigger_actions' ) ) {
			return;
		}

		$entry = FrmEntry::getOne( $payment->item_id );
		$trigger_event = ( $payment->status == 'complete' ) ? 'payment-success' : 'payment-failed';
		FrmFormActionsController::trigger_actions( $trigger_event, $entry->form_id, $entry->id );
	}

	public static function get_currency( $currency ) {
		$currencies = self::get_currencies();
		if ( isset( $currencies[ $currency ] ) ) {
			$currency = $currencies[ $currency ];
		} else {
			$currency = $currencies['usd'];
		}
		return $currency;
	}

	public static function get_currencies( $currency = false ) {
		$currencies = array(
			'aud' => array(
				'name' => __( 'Australian Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'brl' => array(
				'name' => __( 'Brazilian Real', 'formidable-stripe' ),
				'symbol_left' => 'R$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'cad' => array(
				'name' => __( 'Canadian Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => 'CAD', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'czk' => array(
				'name' => __( 'Czech Koruna', 'formidable-stripe' ),
				'symbol_left' => '', 'symbol_right' => '&#75;&#269;', 'symbol_padding' => ' ',
				'thousand_separator' => ' ', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'dkk' => array(
				'name' => __( 'Danish Krone', 'formidable-stripe' ),
				'symbol_left' => 'Kr', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'eur' => array(
				'name' => __( 'Euro', 'formidable-stripe' ),
				'symbol_left' => '', 'symbol_right' => '&#8364;', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'hkd' => array(
				'name' => __( 'Hong Kong Dollar', 'formidable-stripe' ),
				'symbol_left' => 'HK$', 'symbol_right' => '', 'symbol_padding' => '',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'huf' => array(
				'name' => __( 'Hungarian Forint', 'formidable-stripe' ),
				'symbol_left' => '', 'symbol_right' => 'Ft', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'ils' => array(
				'name' => __( 'Israeli New Sheqel', 'formidable-stripe' ),
				'symbol_left' => '&#8362;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'jpy' => array(
				'name' => __( 'Japanese Yen', 'formidable-stripe' ),
				'symbol_left' => '&#165;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '', 'decimals' => 0,
			),
			'myr' => array(
				'name' => __( 'Malaysian Ringgit', 'formidable-stripe' ),
				'symbol_left' => '&#82;&#77;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'mxn' => array(
				'name' => __( 'Mexican Peso', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'nok' => array(
				'name' => __( 'Norwegian Krone', 'formidable-stripe' ),
				'symbol_left' => 'Kr', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'nzd' => array(
				'name' => __( 'New Zealand Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'php' => array(
				'name' => __( 'Philippine Peso', 'formidable-stripe' ),
				'symbol_left' => 'Php', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'pln' => array(
				'name' => __( 'Polish Zloty', 'formidable-stripe' ),
				'symbol_left' => '&#122;&#322;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'gbp' => array(
				'name' => __( 'Pound Sterling', 'formidable-stripe' ),
				'symbol_left' => '&#163;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'sgd' => array(
				'name' => __( 'Singapore Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'sek' => array(
				'name' => __( 'Swedish Krona', 'formidable-stripe' ),
				'symbol_left' => '', 'symbol_right' => 'Kr', 'symbol_padding' => ' ',
				'thousand_separator' => ' ', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'chf' => array(
				'name' => __( 'Swiss Franc', 'formidable-stripe' ),
				'symbol_left' => 'Fr.', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => "'", 'decimal_separator' => '.', 'decimals' => 2,
			),
			'twd' => array(
				'name' => __( 'Taiwan New Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'thb' => array(
				'name' => __( 'Thai Baht', 'formidable-stripe' ),
				'symbol_left' => '&#3647;', 'symbol_right' => '', 'symbol_padding' => ' ',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
			'try' => array(
				'name' => __( 'Turkish Liras', 'formidable-stripe' ),
				'symbol_left' => '', 'symbol_right' => '&#8364;', 'symbol_padding' => ' ',
				'thousand_separator' => '.', 'decimal_separator' => ',', 'decimals' => 2,
			),
			'usd' => array(
				'name' => __( 'U.S. Dollar', 'formidable-stripe' ),
				'symbol_left' => '$', 'symbol_right' => '', 'symbol_padding' =>  '',
				'thousand_separator' => ',', 'decimal_separator' => '.', 'decimals' => 2,
			),
		);

		$currencies = apply_filters( 'frm_currencies', $currencies );
            
		return $currencies;
	}

	public static function get_action_setting( $option, $atts ) {
		$settings = self::get_action_settings( $atts );
		$value = isset( $settings[ $option ] ) ? $settings[ $option ] : '';

		return $value;
	}

	public static function get_action_settings( $atts ) {
		$settings = array();
		if ( isset( $atts['payment'] ) ) {
			$atts['payment'] = (array) $atts['payment'];
			if ( ! empty( $atts['payment']['action_id'] ) ) {
				$form_action = FrmTransAction::get_single_action_type( $atts['payment']['action_id'], 'payment' );
				if ( $form_action ) {
					$settings = $form_action->post_content;
				}
			}
		}

		return $settings;
	}

	public static function format_billing_cycle( $sub ) {
		$amount = FrmTransAppHelper::formatted_amount( $sub );
		if ( $sub->interval_count == 1 ) {
			$amount = $amount . '/' . $sub->time_interval;
		} else {
			$amount = $amount . ' every ' . $sub->interval_count . ' ' . $sub->time_interval;
		}
		return $amount;
	}

	public static function get_repeat_times() {
		return array(
			'day'   => __( 'day(s)', 'formidable-stripe' ),
			'week'  => __( 'week(s)', 'formidable-stripe' ),
			'month' => __( 'month(s)', 'formidable-stripe' ),
			'year'  => __( 'year(s)', 'formidable-stripe' ),
		);
	}

	public static function formatted_amount( $payment ) {
		$currency = 'usd';
		$amount = $payment;

		if ( is_object( $payment ) || is_array( $payment ) ) {
			$payment = (array) $payment;
			$amount = $payment['amount'];
			$currency = self::get_action_setting( 'currency', array( 'payment' => $payment ) );
		}

		$currency = self::get_currency( $currency );

		self::format_amount_for_currency( $currency, $amount );

		return $amount;
	}

	public static function format_amount_for_currency( $currency, &$amount ) {
		$amount = number_format( $amount, $currency['decimals'], $currency['decimal_separator'], $currency['thousand_separator'] );
		$left_symbol = $currency['symbol_left'] . $currency['symbol_padding'];
		$right_symbol = $currency['symbol_padding'] . $currency['symbol_right'];
		$amount = $left_symbol . $amount . $right_symbol;
	}

	public static function get_date_format() {
		$date_format = 'm/d/Y';
		if ( class_exists('FrmProAppHelper') ){
			$frmpro_settings = FrmProAppHelper::get_settings();
			if ( $frmpro_settings ) {
				$date_format = $frmpro_settings->date_format;
			}
		} else {
			$date_format = get_option('date_format');
		}

		return $date_format;
	}

	public static function format_the_date( $date, $format = '' ) {
		if ( empty( $format ) ) {
			$format = self::get_date_format();
		}
		return date_i18n( $format, strtotime( $date ) );
	}

	public static function get_user_link( $user_id ) {
		$user_link = __( 'Guest', 'formidable-payments' );
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$user_link = '<a href="' . esc_url( admin_url('user-edit.php?user_id=' . $user_id ) ) . '">' . $user->display_name . '</a>';
			}
		}
		return $user_link;
	}
}
