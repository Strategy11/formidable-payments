<?php
if ( count( $field['options'] ) < 2 ) {
	?>
	<div class="frm-embed-field-placeholder">
		<div class="frm-embed-message">
			<?php esc_html_e( 'Do not delete this field. It will be hidden in your form but is required to process payments.', 'formidable-payments' ); ?>
		</div>
	</div>
	<?php
	return;
}

foreach ( $field['options'] as $opt_key => $opt ) {
	$checked = FrmAppHelper::check_selected( $field['value'], $opt_key ) ? 'checked="checked" ' : ' ';
?>
	<div class="frm_radio">
		<label for="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>">
			<input type="radio" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>" value="<?php echo esc_attr( $opt_key ); ?>" <?php echo $checked; ?> />
			<?php echo ' ' . esc_html( $opt ); ?>
		</label>
	</div>
<?php
}
?>
