<?php
/**
 * This class is the main controller to hook into formidable.
 */
class FrmSecurePayEntriesController {

	public static function show_transaction_detail( $entry ) {
    $is_site_admin = in_array('administrator',  wp_get_current_user()->roles);
    if (!$is_site_admin) {
      return;
    }

		$entry_id = $entry->id;
		$form_id = $entry->form_id;

//		$user_ID = get_current_user_id();
    $transaction_data = self::get_transaction_data($entry_id, $form_id);
    $created_at = new DateTimeImmutable($transaction_data['created_at']);

    require_once FrmSecurePayAppController::path() . '/views/entries/show.php';
	}

	private static function get_transaction_data( $entry_id, $form_id ) {
	  global $wpdb;
	  $table_name = $wpdb->prefix . FrmSecurePayAppController::$transaction_table_name;

	  $query = "SELECT * FROM $table_name WHERE entry_id = %d AND form_id = %d LIMIT 1";
    $sql = $wpdb->prepare($query, array($entry_id, $form_id));
    $result = $wpdb->get_row($sql);

    $data = unserialize($result->meta_value);

//    echo '<pre>'; print_r($data); echo '</pre>'; die();

    return $data;
  }

}
