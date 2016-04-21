<?php

class FrmTransListHelper extends FrmListHelper {

    var $table = '';

	public function __construct( $args ) {
		$this->table = isset( $_REQUEST['trans_type'] ) ? $_REQUEST['trans_type'] : '';

		parent::__construct( $args );
	}

    function prepare_items() {
        global $wpdb;
        
    	$orderby = ( isset( $_REQUEST['orderby'] ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
		$order = ( isset( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

    	$page = $this->get_pagenum();
        $per_page = $this->get_items_per_page( 'formidable_page_formidable_payments_per_page');
		$start = ( isset( $_REQUEST['start'] ) ) ? absint( $_REQUEST['start'] ) : (( $page - 1 ) * $per_page);
		$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
		$table_name = ( $this->table == 'subscriptions' ) ? 'frm_subscriptions' : 'frm_payments';
		if ( $form_id ) {
			$query = $wpdb->prepare( "FROM {$wpdb->prefix}{$table_name} p LEFT JOIN {$wpdb->prefix}frm_items i ON (p.item_id = i.id) WHERE i.form_id = %d", $form_id );
		} else {
			$query = 'FROM ' . $wpdb->prefix . $table_name . ' p';
		}
		$this->items = $wpdb->get_results( 'SELECT * ' . $query . " ORDER BY p.{$orderby} $order LIMIT $start, $per_page");
		$total_items = $wpdb->get_var( 'SELECT COUNT(*) ' . $query );

    	$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page
		) );
    }

    function no_items() {
    	_e( 'No payments found.', 'formidable-payments' );
    }

	public function get_views() {

		$statuses = array(
		    'payments'      => __( 'Payments', 'formidable' ),
		    'subscriptions' => __( 'Subscriptions', 'formidable' ),
		);

	    $links = array();

		$frm_payment = new FrmTransPayment();
		$frm_sub = new FrmTransSubscription();
	    $counts = array(
			'payments'      => $frm_payment->get_count(),
			'subscriptions' => $frm_sub->get_count(),
		);
        $type = isset( $_REQUEST['trans_type'] ) ? sanitize_text_field( $_REQUEST['trans_type'] ) : 'payments';

	    foreach ( $statuses as $status => $name ) {

	        if ( $status == $type ) {
    			$class = ' class="current"';
    		} else {
    		    $class = '';
    		}

    		if ( $counts[ $status ] || 'published' == $status ) {
				$links[ $status ] = '<a href="' . esc_url( '?page=formidable-payments&trans_type=' . $status ) . '" ' . $class . '>' . sprintf( __( '%1$s <span class="count">(%2$s)</span>', 'formidable' ), $name, number_format_i18n( $counts[ $status ] ) ) . '</a>';
		    }

		    unset( $status, $name );
	    }

		return $links;
	}

	function get_columns() {
	    return FrmTransListsController::payment_columns();
	}

	function get_sortable_columns() {
		return array(
		    'item_id'    => 'item_id',
			'amount'     => 'amount',
			'created_at' => 'created_at',
			'receipt_id' => 'receipt_id',
			'sub_id'     => 'sub_id',
			'begin_date' => 'begin_date',
			'expire_date' => 'expire_date',
			'paysys'     => 'paysys',
			'status'     => 'status',
			'next_bill_date' => 'next_bill_date',
		);
	}
	
	function get_bulk_actions(){
	    $actions = array( 'bulk_delete' => __( 'Delete' ) );
            
        return $actions;
    }

	function extra_tablenav( $which ) {
		$footer = ( $which != 'top' );
		if ( ! $footer ) {
			$form_id = isset( $_REQUEST['form'] ) ? absint( $_REQUEST['form'] ) : 0;
			echo FrmFormsHelper::forms_dropdown( 'form', $form_id, array( 'blank' => __( 'View all forms', 'formidable' ) ) );
			echo '<input id="post-query-submit" class="button" type="submit" value="Filter" name="filter_action">';
		}
	}

    function display_rows() {
        global $wpdb;

		$date_format = FrmTransAppHelper::get_date_format();
		$gateways = FrmTransAppHelper::get_gateways();
		$frm_payment = new FrmTransPayment();

    	$alt = 0;
        $base_link = '?page=formidable-payments&action=';
        
        $entry_ids = array();
        foreach ( $this->items as $item ) {
			$entry_ids[] = absint( $item->item_id );
            unset($item);
        }
        
        $forms = $wpdb->get_results("SELECT fo.id as form_id, fo.name, e.id FROM {$wpdb->prefix}frm_items e LEFT JOIN {$wpdb->prefix}frm_forms fo ON (e.form_id = fo.id) WHERE e.id in (". implode(',', $entry_ids ).")");
        unset($entry_ids);
        
        $form_ids = array();
        foreach($forms as $form){
            $form_ids[$form->id] = $form;
            unset($form);
        }

		foreach ( $this->items as $item ) {
			$style = ( $alt++ % 2 ) ? '' : ' class="alternate"';

			$edit_link = $base_link . 'edit&id=' . $item->id;
			$view_link = $base_link . 'show&id=' . $item->id;
			$delete_link = $base_link . 'destroy&id=' . $item->id;
?>
    	    <tr id="payment-<?php echo esc_attr( $item->id ); ?>" valign="middle" <?php echo $style; ?>>
<?php

    		list( $columns, $hidden ) = $this->get_column_info();

    		foreach ( $columns as $column_name => $column_display_name ) {
    			$class = 'column-' . $column_name;

    			if ( in_array( $column_name, $hidden ) ) {
					$class .= ' frm_hidden';
				}

				$attributes = 'class="' . esc_attr( $class ) . '"';

    			switch ( $column_name ) {
    				case 'cb':
    					echo '<th scope="row" class="check-column"><input type="checkbox" name="item-action[]" value="' . esc_attr( $item->id ) . '" /></th>';
    				    break;

    				case 'receipt_id':
						$val = '<strong><a class="row-title" href="' . esc_url( $edit_link ) . ' title="' . esc_attr( __( 'Edit' ) ) . '">' . $item->receipt_id . '</a></strong><br />';

    					$actions = array();
						$actions['view'] = '<a href="' . esc_url( $view_link ) . '">' . __( 'View', 'formidable-payments' ) . '</a>';
						$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . __( 'Edit' ) . '</a>';
						$actions['delete'] = '<a href="' . esc_url( $delete_link ) . '">' . __( 'Delete' ) . '</a>';
    					$val .= $this->row_actions( $actions );

    					break;
    				case 'user_id':
    				    $val = FrmDb::get_var( $wpdb->prefix .'frm_items', array( 'id' => $item->item_id ), 'user_id' );
						$val = FrmTransAppHelper::get_user_link( $val );

                        break;
    				case 'item_id':
						$val = '<a href="' . esc_url( '?page=formidable-entries&frm_action=show&action=show&id=' . $item->item_id ) . '">' . $item->item_id . '</a>';
    					break;
    				case 'form_id':
						$val = isset( $form_ids[ $item->item_id ] ) ? $form_ids[ $item->item_id ]->name : '';
    				    break;
					case 'created_at':
						if ( empty( $item->$column_name ) || $item->$column_name == '0000-00-00 00:00:00' ) {
							$val = '';
						} else {
							$date = FrmAppHelper::get_localized_date( $date_format, $item->$column_name );
							$date_title = FrmAppHelper::get_localized_date( $date_format . ' g:i:s A', $item->$column_name );
							$val = '<abbr title="' . esc_attr( $date_title ) . '">' . $date . '</abbr>';
						}

    				    break;
	    			case 'begin_date':
	    			case 'expire_date':
					case 'next_bill_date':
						if ( empty( $item->$column_name ) || $item->$column_name == '0000-00-00' ) {
							$val = '';
						} else {
							$val = FrmTransAppHelper::format_the_date( $item->$column_name, $date_format );
						}
					break;
					case 'amount':
						if ( $this->table == 'subscriptions' ) {
							$val = FrmTransAppHelper::format_billing_cycle( $item );
						} else {
							$val = FrmTransAppHelper::formatted_amount( $item );
						}
					break;
					case 'end_count':
						$limit = ( $item->end_count >= 9999 ) ? __( 'unlimited', 'formidable-payments' ) : $item->end_count;
						$completed_payments = $frm_payment->get_all_by( $item->id, 'sub_id' );
						$count = 0;
						foreach ( $completed_payments as $completed_payment ) {
							if ( $completed_payment->status == 'complete' ) {
								$count++;
							}
						}
						$val = sprintf( __( '%1$s of %2$s', 'formidable-payments' ), $count, $limit );
					break;
					case 'paysys':
						$val = isset( $gateways[ $item->paysys ] ) ? $gateways[ $item->paysys ]['label'] : $item->paysys;
					break;
					case 'status':
						$val = $item->status ? FrmTransAppHelper::show_status( $item->status ) : '';
					break;
    				default:
						$val = $item->$column_name ? $item->$column_name : '';
    					break;
    			}

				if ( isset( $val ) ) {
					echo '<td '. $attributes . '>' . $val . '</td>';
					unset( $val );
				}
    		}
    ?>
    		</tr>
    <?php
        unset($item);
    	}
    }
}
