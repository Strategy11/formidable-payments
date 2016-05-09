<?php

class FrmTransUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Payments';
	//public $download_id = 180495;
	public $version = '1.03';

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/formidable-payments.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmTransUpdate();
	}

}
