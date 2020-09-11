<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '../../../../../tests/phpunit/';
}

$GLOBALS['wp_tests_options'] = array(
	'active_plugins'     => array(
		'formidable/formidable.php',
		'formidable-pro/formidable-pro.php',
		'formidable-stripe/formidable-payments/formidable-payments.php',
	),
	'frmpro-credentials' => array( 'license' => '87fu-uit7-896u-ihy8' ),
	'frmpro-authorized'  => true,
);

require $_tests_dir . 'includes/bootstrap.php';

if ( is_dir( dirname( __FILE__ ) . '/../../../formidable/tests' ) ) {
    // declare the factories
    require_once dirname( __FILE__ ) . '/../../../formidable/tests/base/frm_factory.php';

    // include unit test base class
    require_once dirname( __FILE__ ) . '/../../../formidable/tests/base/FrmUnitTest.php';
    require_once dirname( __FILE__ ) . '/../../../formidable/tests/base/FrmAjaxUnitTest.php';
}
