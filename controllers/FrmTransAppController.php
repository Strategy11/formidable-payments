<?php

class FrmTransAppController {

    public static function load_lang() {
        load_plugin_textdomain( 'formidable-payments', false, FrmStrpAppHelper::plugin_folder() . '/languages/' );
    }

    public static function include_updater() {
		FrmTransUpdate::load_hooks();
    }

	public static function install( $old_db_version = false ) {
		$db = new FrmTransDb();
		$db->upgrade( $old_db_version );
	}

	public static function filter_allowed_html( $allowed, $context ) {
		if ( $context === 'post' ) {
			$allowed['a']['data-deleteconfirm'] = true;
			$allowed['a']['data-tempid'] = true;
		}
		return $allowed;
	}
}
