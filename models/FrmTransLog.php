<?php

class FrmTransLog {

	public static function log_message( $text ) {

		if ( ! function_exists('get_filesystem_method') ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$logged = false;
		$access_type = get_filesystem_method();

		if ( $access_type == 'direct' ) {
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

			// initialize the API
			if ( WP_Filesystem( $creds ) ) {
				global $wp_filesystem;

				$log_file = FrmTransAppHelper::plugin_path() . '/log/results.log';
				$log = $wp_filesystem->get_contents( $log_file );
				$log .= '[' . date( 'm/d/Y g:ia' ) . '] ' . $text . "\n";

				$wp_filesystem->put_contents( $log_file, $log, 0600 );
				$logged = true;
			}
		}

		if ( ! $logged ) {
			error_log( $text );
		}

		return $logged;
	}
}
