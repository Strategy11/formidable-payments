<?php

class FrmTransHooksController {

	public static function load_hooks() {
		add_action( 'plugins_loaded', 'FrmTransAppController::load_lang' );
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-payments.php', 'FrmTransAppController::install' );
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/formidable-payments.php', 'FrmTransAppController::remove_cron' );
		add_action( 'frm_payment_cron', 'FrmTransAppController::run_payment_cron' );

		self::load_admin_hooks();

        add_action( 'frm_registered_form_actions', 'FrmTransActionsController::register_actions' );
		add_action( 'frm_add_form_option_section', 'FrmTransActionsController::actions_js' );

        add_action( 'frm_trigger_payment_action', 'FrmTransActionsController::trigger_action', 10, 3 );

		add_filter( 'frm_action_triggers', 'FrmTransActionsController::add_payment_trigger' );
		add_filter( 'frm_email_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_twilio_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_mailchimp_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_api_action_options', 'FrmTransActionsController::add_trigger_to_action' );
		add_filter( 'frm_register_action_options', 'FrmTransActionsController::add_trigger_to_register_user_action' );

		add_filter( 'frm_csv_columns', 'FrmTransEntriesController::add_payment_to_csv', 20, 2 );

		add_shortcode( 'frm-subscriptions', 'FrmTransSubscriptionsController::list_subscriptions_shortcode' );

		add_shortcode( 'frm-receipt-id', 'FrmTransPaymentsController::do_frm_receipt_id_shortcode' );

		add_filter( 'frm_available_fields', 'FrmTransFieldsController::add_gateway_field_type' );
		add_filter( 'frm_setup_new_fields_vars', 'FrmTransFieldsController::add_gateway_options', 20, 2 );
		add_filter( 'frm_setup_edit_fields_vars', 'FrmTransFieldsController::add_gateway_options', 20, 2 );
		add_filter( 'frm_logic_gateway_input_type', 'FrmTransFieldsController::field_type_for_logic' );
		add_action( 'frm_form_field_gateway', 'FrmTransFieldsController::show_in_form', 10, 3 );
	}

	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmTransAppController::include_updater', 1 );

		add_action( 'admin_menu', 'FrmTransPaymentsController::menu', 25 );
		add_action( 'admin_head', 'FrmTransListsController::add_list_hooks' );

		add_action( 'frm_show_entry_sidebar', 'FrmTransEntriesController::sidebar_list', 9 );

		add_action( 'wp_ajax_frm_trans_refund', 'FrmTransPaymentsController::refund_payment' );
		add_action( 'wp_ajax_frm_trans_cancel', 'FrmTransSubscriptionsController::cancel_subscription' );

		add_filter( 'frm_before_save_payment_action', 'FrmTransFieldsController::auto_add_gateway_field', 10, 2 );
		add_action( 'frm_display_added_gateway_field', 'FrmTransFieldsController::show_in_form_builder' );
	}
}
