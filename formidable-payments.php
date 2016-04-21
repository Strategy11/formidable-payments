<?php
/*
Plugin Name: Formidable Payments
Description: Setup one-time and recurring subscriptions using your Formidable Forms
Version: 1.0
Plugin URI: http://formidablepro.com/
Author URI: http://formidablepro.com/
Author: Strategy11
*/

function frm_trans_autoloader( $class_name ) {
    // Only load Frm classes here
	if ( ! preg_match( '/^FrmTrans.+$/', $class_name ) ) {
        return;
    }

    $filepath = dirname(__FILE__);

	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
        $filepath .= '/helpers/';
	} else if ( preg_match( '/^.+Controller$/', $class_name ) ) {
        $filepath .= '/controllers/';
    } else {
        $filepath .= '/models/';
    }

    $filepath .= $class_name . '.php';

    if ( file_exists( $filepath ) ) {
        include( $filepath );
    }
}

// Add the autoloader
spl_autoload_register('frm_trans_autoloader');

FrmTransHooksController::load_hooks();
