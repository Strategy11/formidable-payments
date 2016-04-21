<?php
foreach ( $gateways as $gateway ) {
	if ( count( $gateways ) == 1 ) {
	?>
		<input type="hidden" name="frm_gateway" value="<?php echo esc_attr( $gateway ) ?>" />
	<?php
	} else { ?>
		<input type="radio" name="frm_gateway" value="<?php echo esc_attr( $gateway ) ?>" />
		<?php echo esc_html( $gateway_settings[ $gateway ]['user_label'] ); ?>
	<?php
	}
} ?>
