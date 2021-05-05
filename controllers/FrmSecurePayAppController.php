<?php
/**
 * This class is the main controller to hook into formidable.
 */
class FrmSecurePayAppController {
	public static $min_version = '3.0';
	public static $transaction_table_name = 'svgb_fsp_transaction';

	private static function maybe_create_transaction_table() {
	  global $wpdb;
	  $table_name = $wpdb->prefix . self::$transaction_table_name;
	  $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      entry_id bigint(20) NOT NULL,
      form_id bigint(20) NOT NULL,
      meta_value longtext COLLATE utf8mb4_unicode_520_ci,
      PRIMARY KEY (id),
      KEY entry_id (entry_id),
      KEY form_id (form_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }

	public static function install( $old_db_version = false ) {
		FrmTransAppController::install( $old_db_version );
    self::maybe_create_transaction_table();
	}

	public static function pro_not_installed_notice() {
		?>
	<div class="error">
		<p><?php esc_html_e( 'Formidable SecurePay requires Formidable Forms Pro to be installed.', 'formidable-securepay' ); ?></p>
	</div>
		<?php
	}

	public static function min_version_notice() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;

		// Check if Formidable meets minimum requirements.
		if ( version_compare( $frm_version, self::$min_version, '>=' ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		echo '<tr class="plugin-update-tr active"><th colspan="' . (int) $wp_list_table->get_column_count() . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
			esc_html_e( 'You are running an outdated version of Formidable. This plugin needs Formidable v2.0 + to work correctly.', 'frmsecurepay' ) .
			'</div></td></tr>';
	}

	public static function path() {
		return dirname( dirname( __FILE__ ) );
	}

	/**
	 * Get the url to this plugin files.
	 *
	 * @since 2.01
	 */
	public static function plugin_url() {
		return plugins_url() . '/' . basename( self::path() );
	}

	public static function add_securepay_gateway() {
    $gateways['securepay'] = array(
      'label' => 'SecurePay',
      'user_label' => __( 'Credit Card', 'formidable-securepay' ),
      'class' => 'SecurePay',
      'recurring' => false,
			'include'   => array(
				'billing_first_name',
				'billing_last_name',
				'billing_address',
			),
    );

    return $gateways;
  }

	public static function add_api_routes() {
    register_rest_route(
      'frmsecurepay/v1',
      '/payment/',
			array(
				'methods'  => 'POST',
				'callback' => array( 'FrmSecurePayPaymentsController', 'create_payment' ),
//				'permission_callback' => 'is_user_logged_in', // enable this to prevent anonymous user calling the endpoint
			)
		);
	}

	public static function add_available_fields($fields) {
	  $fields['securepay-ui'] = array(
        'name' => 'SecurePay UI',
        'icon' => 'frm_icon_font frm_credit_card_icon', // Set the class for a custom icon here.
        'label' => 'Payment Detail',
    );

    return $fields;
  }

  public static function set_field_defaults($field_data) {
    if ( $field_data['type'] === 'securepay-ui' ) {
      $field_data['name'] = 'Payment Details';
    }

    return $field_data;
  }

  public static function show_frontend_form_field($field, $field_name, $attrs) {
	  if ( $field['type'] === 'securepay-ui' ) {
	    $field['value'] = stripslashes_deep($field['value']);
      ?>
      <div
        id="securepay-ui-container"
      ></div>
    <?php
    }
//    $field['value'] = stripslashes_deep($field['value']);
  }

}
