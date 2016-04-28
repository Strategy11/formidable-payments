<?php

class FrmTransHooksController {

	public static function load_hooks() {
		//add_action( 'admin_init', 'FrmTransAppController::include_updater', 1 );
		add_action( 'plugins_loaded', 'FrmTransAppController::load_lang' );
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-payments.php', 'FrmTransAppController::install' );
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-payments.php', 'FrmTransAppController::remove_cron' );
		add_action( 'frm_payment_cron', 'FrmTransAppController::run_payment_cron' );

		if ( is_admin() ) {
			add_action( 'admin_menu', 'FrmTransPaymentsController::menu', 25 );
			add_action( 'admin_head', 'FrmTransListsController::add_list_hooks' );

			add_action( 'frm_show_entry_sidebar', 'FrmTransEntriesController::sidebar_list', 9 );

			add_action( 'wp_ajax_frm_trans_refund', 'FrmTransPaymentsController::refund_payment' );
			add_action( 'wp_ajax_frm_trans_cancel', 'FrmTransSubscriptionsController::cancel_subscription' );
		}

        add_action( 'frm_registered_form_actions', 'FrmTransActionsController::register_actions' );
		add_action( 'frm_add_form_option_section', 'FrmTransActionsController::actions_js' );

        add_action( 'frm_trigger_payment_action', 'FrmTransActionsController::trigger_action', 10, 3 );

		add_filter( 'frm_action_triggers', 'FrmTransActionsController::add_payment_trigger' );
		add_filter( 'frm_email_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_twilio_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_mailchimp_action_options', 'FrmTransActionsController::add_trigger_to_action' );

		add_filter( 'frm_csv_columns', 'FrmTransEntriesController::add_payment_to_csv', 20, 2 );

		add_shortcode( 'frm-subscriptions', 'FrmTransSubscriptionsController::list_subscriptions_shortcode' );

		add_action( 'frm_entry_form', 'FrmTransFieldsController::gateway_field' );
	}
}
