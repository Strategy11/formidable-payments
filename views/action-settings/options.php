<table class="form-table frm-no-margin">
<tbody>
	<tr>
		<th>
			<label for="<?php echo esc_attr( $action_control->get_field_id( 'description' ) ) ?>">
				<?php _e( 'Payment Description', 'formidable-payments' ) ?>
			</label>
		</th>
		<td>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'description' ) ) ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'description' ) ) ?>" value="<?php echo esc_attr( $form_action->post_content['description'] ); ?>" class="frm_not_email_subject large-text" />
		</td>
	</tr>

	<tr>
		<th>
			<label for="<?php echo esc_attr( $action_control->get_field_id( 'email' ) ) ?>">
				<?php _e( 'Customer Email', 'formidable-payments' ) ?>
			</label>
		</th>
		<td>
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'email' ) ) ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'email' ) ) ?>" value="<?php echo esc_attr( $form_action->post_content['email'] ); ?>" class="frm_not_email_to large-text" />
		</td>
	</tr>

	<tr>
		<th>
			<label for="<?php echo esc_attr( $action_control->get_field_id( 'amount' ) ) ?>">
				<?php _e( 'Amount', 'formidable-payments') ?>
			</label>
		</th>
		<td>
			<input type="text" value="<?php echo esc_attr( $form_action->post_content['amount'] ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'amount' ) ) ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'amount' ) ) ?>" class="frm_not_email_subject large-text" />
		</td>
	</tr>

	<tr>
		<th>
			<label>
				<?php _e( 'Payment Type', 'formidable-payments' ) ?>
			</label>
		</th>
		<td>
			<select name="<?php echo esc_attr( $this->get_field_name( 'type' ) ) ?>" class="frm_trans_type">
				<option value="single" <?php selected( $form_action->post_content['type'], 'one_time' ) ?>><?php _e( 'One-time Payment', 'formidable-payments' ) ?></option>
				<option value="recurring" <?php selected( $form_action->post_content['type'], 'recurring' ) ?>><?php _e( 'Recurring', 'formidable-payments' ) ?></option>
			</select>
		</td>
	</tr>

	<tr class="frm_trans_sub_opts <?php echo $form_action->post_content['type'] == 'recurring' ? '' : 'frm_hidden'; ?>">
		<th>
			<label>
				<?php _e( 'Repeat Every', 'formidable-payments' ) ?>
			</label>
		</th>
		<td>
			<input type="number" name="<?php echo esc_attr( $this->get_field_name( 'interval_count' ) ) ?>" value="<?php echo esc_attr( $form_action->post_content['interval_count'] ) ?>" max="90" min="1" step="1" />
			<select name="<?php echo esc_attr( $this->get_field_name( 'interval' ) ) ?>">
				<?php foreach ( FrmTransAppHelper::get_repeat_times() as $k => $v ) { ?>
					<option value="<?php echo esc_attr($k) ?>" <?php selected( $form_action->post_content['interval'], $k ) ?>><?php echo esc_html( $v ) ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>

	<tr>
		<th>
			<label for="<?php echo esc_attr( $this->get_field_id( 'currency' ) ) ?>">
				<?php _e( 'Currency', 'formidable-payments' ) ?>
			</label>
		</th>
		<td>
			<select name="<?php echo esc_attr( $this->get_field_name( 'currency' ) ) ?>" id="<?php echo esc_attr( $this->get_field_id( 'currency' ) ) ?>">
				<?php foreach ( FrmTransAppHelper::get_currencies() as $code => $currency ) { ?>
					<option value="<?php echo esc_attr( $code ) ?>" <?php selected( $form_action->post_content['currency'], $code ) ?>><?php echo esc_html( $currency['name'] . ' (' . strtoupper( $code ) . ')' ); ?></option>
				<?php
					unset( $currency, $code );
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<th>
			<?php _e( 'Gateway(s)', 'formidable-payments' ) ?>
		</th>
		<td>
			<?php foreach ( $gateways as $gateway_name => $gateway ) { ?>
				<label for="<?php echo esc_attr( $this->get_field_id( 'gateways' ) . '_' . $gateway_name ) ?>">
					<?php if ( count( $gateways ) == 1 ) { ?>
						<input type="hidden" value="<?php echo esc_attr( $gateway_name ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'gateway' ) ) ?>[]" />
					<?php } else { ?>
						<input type="checkbox" value="<?php echo esc_attr( $gateway_name ) ?>" name="<?php echo esc_attr( $this->get_field_name( 'gateway' ) ) ?>[]" <?php FrmAppHelper::checked( $form_action->post_content['gateway'], $gateway_name ) ?>/>
					<?php } ?>
					<?php echo esc_html( $gateway['label'] ); ?>
				</label>
			<?php } ?>
		</td>
	</tr>
	<?php
	foreach ( $gateways as $gateway_name => $gateway ) {
		do_action( 'frm_pay_show_' . $gateway_name . '_options', array(
			'form_action' => $form_action, 'action_control' => $this,
		) );	
	}
	?>
</tbody>
</table>

<h3>
	<?php _e( 'After Payment', 'formidable-payments' ) ?>
	<span class="frm_help frm_icon_font frm_tooltip_icon" title="<?php esc_attr_e( 'Change a field value when the status of a payment changes.', 'formidable-payments' ) ?>" ></span>
</h3>

<div class="frm_add_remove">
	<p id="frmtrans_after_pay_<?php echo absint( $form_action->ID ) ?>" <?php echo empty( $form_action->post_content['change_field'] ) ? '' : 'class="frm_hidden"'; ?>>
		<a href="#" class="frm_add_trans_logic button" data-emailkey="<?php echo absint( $form_action->ID ) ?>">
			+ <?php _e( 'Add', 'formidable-payments' ) ?>
		</a>
	</p>
	<div id="postcustomstuff" class="frmtrans_after_pay_rows <?php echo empty( $form_action->post_content['change_field'] ) ? 'frm_hidden' : ''; ?>">
		<table id="list-table">
			<thead>
				<tr>
					<th><?php _e( 'Payment Status', 'formidable-payments' ) ?></th>
					<th><?php _e( 'Field', 'formidable-payments' ) ?></th>
					<th><?php _e( 'Value', 'formidable-payments' ) ?></th>
					<th style="max-width:60px;"></th>
				</tr>
			</thead>
			<tbody data-wp-lists="list:meta">
				<?php
				foreach ( $form_action->post_content['change_field'] as $row_num => $vals ) {
					$this->after_pay_row( array(
						'form_id' => $args['form']->id, 'row_num' => $row_num, 'form_action' => $form_action,
					) );
				}
				?>
			</tbody>
		</table>
	</div>
</div>

