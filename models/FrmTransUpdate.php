<?php

class FrmTransUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Formidable Payments';
	public $download_id = 20834175;
	public $version = '1.14';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-payments.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmTransUpdate();
	}

}
