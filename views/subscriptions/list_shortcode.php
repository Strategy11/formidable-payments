<table>
	<thead>
		<tr>
			<th><?php esc_html_e( 'Billing Cycle', 'formidable-payments' ); ?></th>
			<th><?php esc_html_e( 'Next Bill Date', 'formidable-payments' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach ( $subscriptions as $sub ) { ?>
		<tr>
			<td><?php echo esc_html( FrmTransAppHelper::get_payment_description( $sub ) . ' - ' . FrmTransAppHelper::format_billing_cycle( $sub ) ); ?></td>
			<td><?php echo esc_html( FrmTransAppHelper::format_the_date( date('Y-m-d H:i:s', strtotime( $sub->next_bill_date ) ) ) ); ?></td>
			<td><?php FrmTransSubscriptionsController::show_cancel_link( $sub ); ?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
