<?php
/**
 * Load all the hooks to keep memory low.
 */
class FrmSecurePayHooksController {

  public static function queue_load() {
		add_action( 'plugins_loaded', 'FrmSecurePayHooksController::load_hooks' );
	}

	public static function load_hooks() {
    if ( ! self::is_formidable_compatible() ) {
			add_action( 'admin_notices', 'FrmSecurePayAppController::pro_not_installed_notice');
			return;
		}

    self::load_admin_hooks();

    register_activation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-securepay.php', 'FrmSecurePayAppController::install' );

    add_filter( 'frm_payment_gateways', 'FrmSecurePayAppController::add_securepay_gateway' );
    add_action( 'rest_api_init', 'FrmSecurePayAppController::add_api_routes' );

    add_action( 'frm_form_fields', 'FrmSecurePayAppController::show_frontend_form_field', 10, 3 );

//    add_filter( 'frm_filter_final_form', 'FrmSecurePayAPI::maybe_show_message' );
//		add_action( 'frm_entry_form', 'FrmSecurePayAPI::add_hidden_token_field' );

    add_action( 'wp_enqueue_scripts', 'FrmSecurePayHooksController::add_scripts' );
  }

	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmSecurePayAppController::install', 1 );
		add_action( 'admin_enqueue_scripts', 'FrmSecurePayHooksController::add_admin_scripts' );
		add_action( 'after_plugin_row_formidable-securepay/formidable-securepay.php', 'FrmSecurePayAppController::min_version_notice' );

		add_filter( 'frm_pay_action_defaults', 'FrmSecurePayActionsController::add_action_defaults' );
    add_action( 'frm_pay_show_securepay_options', 'FrmSecurePayActionsController::add_action_options' );
    add_action( 'frm_add_settings_section', 'FrmSecurePaySettingsController::add_settings_section' );
    add_filter( 'frm_before_field_created', 'FrmSecurePayAppController::set_field_defaults', 10 );
    add_filter( 'frm_pro_available_fields', 'FrmSecurePayAppController::add_available_fields', 10 );

    add_action( 'frm_after_show_entry', 'FrmSecurePayEntriesController::show_transaction_detail' );
	}

	public static function add_admin_scripts() {
    if ( FrmAppHelper::is_form_builder_page() ) {
			wp_register_script( 'frmsecurepay_actions', FrmSecurePayAppController::plugin_url() . '/js/_actions.js', array('jquery'), null, true );
      wp_enqueue_script( 'frmsecurepay_actions' );
		}
  }

	public static function add_scripts() {
    $url = FrmSecurePayAppController::plugin_url();
    $settings = new FrmSecurePaySettings();
    $test_mode = $settings->settings->test_mode;
    $merchant_code = $settings->settings->merchant_code;
    $client_id = $settings->settings->client_id;

    if ($test_mode) {
      $sdkUrl = 'https://payments-stest.npe.auspost.zone/v3/ui/client/securepay-ui.min.js';
    } else {
      $sdkUrl = 'https://payments.auspost.net.au/v3/ui/client/securepay-ui.min.js';
    }

    wp_register_script( 'securepay-ui', $sdkUrl, array(), null, true );
		wp_register_script( 'frmsecurepay', $url . '/js/frmsecurepay.js', array('securepay-ui', 'jquery'), null, true );
    wp_localize_script(
      'frmsecurepay',
      'frmsecurepayGlobal',
      array(
        'nonce' => wp_create_nonce( 'frmsecurepay_ajax' ),
        'merchantCode' => $merchant_code,
        'clientId' => $client_id,
        'paymentUrl' => esc_url_raw(rest_url('frmsecurepay/v1/payment')),
      )
    );

		wp_enqueue_script( 'frmsecurepay' );
		wp_enqueue_style( 'frmsecurepay', $url . '/css/frmsecurepay.css', array(), null );
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @since 2.01
	 *
	 * @return bool
	 */
	private static function is_form_settings_page() {
		if ( ! self::is_formidable_compatible() ) {
			return;
		}

		$is_form_settings_page = false;
		$page = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		if ( 'formidable' === $page && 'settings' === $action ) {
			$is_form_settings_page = true;
		}
		return $is_form_settings_page;
	}

	/**
	 * Check if the current version of Formidable is compatible with this add-on
	 *
	 * @since 1.04
	 * @return bool
	 */
	private static function is_formidable_compatible() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;
		return version_compare( $frm_version, FrmSecurePayAppController::$min_version, '>=' ) && FrmAppHelper::pro_is_installed();
	}
}
