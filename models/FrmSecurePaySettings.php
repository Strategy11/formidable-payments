<?php
/**
 * Save and retrive the Global settings.
 */
class FrmSecurePaySettings {

	public $settings;
	private $option = 'frm_securepay_options';

	public function __construct() {
		$this->get_options();
	}

	public function default_options() {
		return array(
		  'test_mode' => 1,
			'merchant_code' => '',
			'client_id' => '',
			'client_secret' => '',
		);
	}

	public function get_settings() {
		return $this->settings;
	}

	private function set_default_options() {
		$default_settings = $this->default_options();

		if ( empty( $this->settings ) ) {
			$this->settings = new stdClass();
		} else {
			$this->settings = (object) $this->settings;
		}

		foreach ( $default_settings as $setting => $default ) {
			if ( ! isset( $this->settings->{$setting} ) ) {
				$this->settings->{$setting} = $default;
			}
		}
	}

	public function get_options() {
		$this->settings = get_option( $this->option );
		$this->set_default_options();

		return $this->settings;
	}

	public function update() {
		$settings = $this->default_options();

		foreach ( $settings as $setting => $default ) {
			if ( isset( $_POST[ 'frm_securepay_' . $setting ] ) ) {
				$this->settings->{$setting} = sanitize_text_field( wp_unslash( $_POST[ 'frm_securepay_' . $setting ] ) );
			}
			unset( $setting, $default );
		}

		$this->settings->test_mode = isset( $_POST['frm_securepay_test_mode'] ) ? absint( $_POST['frm_securepay_test_mode'] ) : 0;
	}

	/**
	 * Save the posted value in the database
	 */
	public function store() {
		update_option( $this->option, $this->settings );
	}
}
