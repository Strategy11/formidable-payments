<?php

class FrmTransListsController {

	public static function add_list_hooks() {
		if ( ! class_exists('FrmAppHelper') ) {
			return;
		}

		$frm_settings = FrmAppHelper::get_settings();

		add_filter( 'manage_' . sanitize_title( $frm_settings->menu ) . '_page_formidable-payments_columns', 'FrmTransListsController::payment_columns' );
		add_filter( 'frm_entries_payments_column', 'FrmTransListsController::entry_payment_column', 10, 2 );
		add_filter( 'frm_entries_current_payment_column', 'FrmTransListsController::entry_current_payment_column', 10, 2 );
		add_filter( 'frm_entries_payment_expiration_column', 'FrmTransEntriesController::entry_payment_expiration_column', 10, 2 );
	}

	public static function payment_columns( $columns = array() ) {
		add_screen_option( 'per_page', array(
			'label'   => __( 'Payments', 'formidable-payments' ),
			'default' => 20,
			'option'  => 'formidable_page_formidable_payments_per_page',
		) );

        $type = isset( $_REQUEST['trans_type'] ) ? $_REQUEST['trans_type'] : 'payments';

	    $columns['cb'] = '<input type="checkbox" />';
		$columns['user_id'] = __( 'Customer', 'formidable-payments' );

        if ( 'subscriptions' == $type ) {
			$add_columns = array(
				'sub_id'     => __( 'Profile ID', 'formidable-payments' ),
				'item_id'    => __( 'Entry', 'formidable-payments' ),
				'form_id'    => __( 'Form', 'formidable-payments' ),
				'amount'     => __( 'Billing Cycle', 'formidable-payments' ),
				//'first_amount'   => __( 'Initial Amount', 'formidable-payments' ),
				'end_count'      => __( 'Payments Made', 'formidable-payments' ),
				'next_bill_date' => __( 'Next Bill Date', 'formidable-payments' ),
			);
		} else {
			$add_columns = array(
				'receipt_id' => __( 'Receipt ID', 'formidable-payments' ),
				'item_id'    => __( 'Entry', 'formidable-payments' ),
				'form_id'    => __( 'Form', 'formidable-payments' ),
				'amount'     => __( 'Amount', 'formidable-payments' ),
				'sub_id'     => __( 'Subscription', 'formidable-payments' ),
				'begin_date' => __( 'Begin Date', 'formidable-payments' ),
				'expire_date' => __( 'Expire Date', 'formidable-payments' ),
			);
		}

		$columns = $columns + $add_columns;

		$columns['status']  = __( 'Status', 'formidable-payments' );
		$columns['created_at'] = __( 'Date', 'formidable-payments' );
		$columns['paysys']     = __( 'Processor', 'formidable-payments' );

		return $columns;
	}

	public static function save_per_page( $save, $option, $value ) {
		if ( $option == 'formidable_page_formidable_payments_per_page' ) {
			$save = absint( $value );
		}
		return $save;
	}

	public static function route( $action ) {
		if ( empty( $action ) || $action == 'list' ) {
			$bulk_action = self::get_bulk_action();
            
			if ( ! empty( $bulk_action ) ) {
				if ( $_GET && $bulk_action ) {
					$_SERVER['REQUEST_URI'] = str_replace( '&action=' . $bulk_action, '', $_SERVER['REQUEST_URI'] );
				}

				return self::bulk_actions( $bulk_action );
			} else {
				return self::display_list();
			}
		}
	}

	private static function get_bulk_action() {
		$action = FrmAppHelper::get_param( 'action', '', 'get', 'sanitize_text_field' );
		if ( $action == -1 ) {
			$action = FrmAppHelper::get_param( 'action2', '', 'get', 'sanitize_text_field' );
		}
        
		if ( strpos( $action, 'bulk_' ) === 0 ) {
			return $action;
		} else {
			return false;
		}
	}

	private static function bulk_actions( $action ) {
		$response = array( 'errors' => array(), 'message' => '' );

		$items = FrmAppHelper::get_param('item-action', '');
		if ( empty( $items ) ) {
			$response['errors'][] = __( 'No payments were selected', 'formidable-payments' );
		} else {
			if ( ! is_array( $items ) ) {
				$items = explode( ',', $items );
			}

			$bulkaction = str_replace( 'bulk_', '', $action );
			if ( $bulkaction == 'delete' ) {
				self::bulk_delete( $items, $response );
			}
		}

		self::display_list( $response );
	}

	private static function bulk_delete( $items, &$response ) {
		if ( ! current_user_can('frm_delete_entries') ) {
			$frm_settings = FrmAppHelper::get_settings();
			$response['errors'][] = $frm_settings->admin_permission;
			return;
		}

		if ( is_array( $items ) ) {
			$frm_payment = new FrmTransPayment();
			foreach ( $items as $item_id ) {
				if ( $frm_payment->destroy( absint( $item_id ) ) ) {
					$response['message'] = __( 'Payments were Successfully Deleted', 'formidable-payments' );
				}
			}
		}
	}

	public static function list_page_params() {
		$values = array();
		foreach ( array( 'id' => '', 'paged' => 1, 'form' => '', 'search' => '', 'sort' => '', 'sdir' => '' ) as $var => $default ) {
			$values[ $var ] = FrmAppHelper::get_param( $var, $default );
		}

		return $values;
	}

	public static function display_list( $response = array() ) {
		$defaults = array( 'errors' => array(), 'message' => '' );
		$response = array_merge( $defaults, $response );
		$errors = $response['errors'];
		$message = $response['message'];

		$title = __( 'Downloads', 'formidable-payments' );
		$wp_list_table = new FrmTransListHelper( self::list_page_params() );
    
		$pagenum = $wp_list_table->get_pagenum();
    
		$wp_list_table->prepare_items();

		$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );
		if ( $pagenum > $total_pages && $total_pages > 0 ) {
			// if the current page is higher than the total pages,
			// reset it and prepare again to get the right entries
			$_GET['paged'] = $_REQUEST['paged'] = $total_pages;
			$pagenum = $wp_list_table->get_pagenum();
			$wp_list_table->prepare_items();
		}

		include( FrmTransAppHelper::plugin_path() . '/views/lists/list.php' );
	}
}
