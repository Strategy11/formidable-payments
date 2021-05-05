<?php
/**
 * Add and Save Global settings
 */
class FrmSecurePaySettingsController {

	public static function add_settings_section( $sections ) {
		$sections['securepay'] = array(
			'class'    => 'FrmSecurePaySettingsController',
			'function' => 'route',
			'name'     => 'SecurePay',
			'icon'     => 'frm_settings_icon frm_icon_font',
		);
		return $sections;
	}

	public static function display_form() {
		$settings = new FrmSecurePaySettings();
		$frm_version = FrmAppHelper::plugin_version();

		require_once FrmSecurePayAppController::path() . '/views/settings/form.php';
	}

	public static function process_form() {
		$settings = new FrmSecurePaySettings();
		$settings->update();
		$settings->store();
		$message = __( 'Settings Saved', 'frmsecurepay' );

		require_once FrmSecurePayAppController::path() . '/views/settings/form.php';
	}

	public static function route() {
		$action = FrmAppHelper::get_param( 'action' );
		if ( 'process-form' == $action ) {
			return self::process_form();
		} else {
			return self::display_form();
		}
	}

	public static function register_settings_scripts() {
	  $settings = new FrmSecurePaySettings();
    $merchant_code = $settings->settings->merchant_code;
    $client_id = $settings->settings->client_id;

	  $sdkUrl = 'https://payments-stest.npe.auspost.zone/v3/ui/client/securepay-ui.min.js'; // always use test mode for settings page
	  wp_register_script( 'securepay-ui', $sdkUrl, array(), null, true );
	  wp_register_script( 'frmsecurepay_settings', FrmSecurePayAppController::plugin_url() . '/js/_settings.js', array('securepay-ui', 'jquery'), null, true );
		wp_enqueue_script( 'frmsecurepay_settings' );

		wp_localize_script(
      'frmsecurepay_settings',
      'frmsecurepaySettings',
      array(
        'merchantCode' => $merchant_code,
        'clientId' => $client_id,
        'paymentUrl' => esc_url_raw(rest_url('frmsecurepay/v1/payment')),
      )
    );
  }
}
