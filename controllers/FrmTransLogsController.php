<?php

class FrmTransLogsController {

	public static function log_message( $text ) {

		$logged = false;
		$access_type = get_filesystem_method();

		if ( $access_type == 'direct' ) {
			$creds = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );

			// initialize the API
			if ( WP_Filesystem( $creds ) ) {
				global $wp_filesystem;

				$chmod_dir = defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : ( fileperms( ABSPATH ) & 0777 | 0755 );

				$log = $wp_filesystem->get_contents( $settings->settings->ipn_log_file );
				$log .= '[' . date( 'm/d/Y g:ia' ) . '] ' . $text . "\n\n";

				$wp_filesystem->put_contents( FrmTransAppHelper::plugin_path() . '/log/results.log', $log, 0600 );
				$logged = true;
			}
		}

		if ( ! $logged ) {
			error_log( $text );
		}

		return $logged;
	}
}
