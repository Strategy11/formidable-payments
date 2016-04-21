<tr id="<?php echo esc_attr( $id ) ?>" class="frmtrans_after_pay_row frmtrans_after_pay_row_<?php echo absint( $atts['form_action']->ID ) ?>">
	<td><?php echo $action_control->after_payment_status( $atts ) ?></td>
	<td><?php echo $action_control->after_payment_field_dropdown( $atts ) ?></td>
	<td>
		<input type="text" name="<?php echo esc_attr( $atts['name'] ) ?>[<?php echo absint( $atts['row_num'] ) ?>][value]" value="<?php echo esc_attr( $atts['form_action']->post_content['change_field'][ $atts['row_num'] ]['value'] ) ?>"/>
	</td>
	<td style="vertical-align:middle;">
		<a href="#" class="frm_remove_tag frm_icon_font" data-removeid="<?php echo esc_attr( $id ) ?>" data-showlast="#frmtrans_after_pay_<?php echo absint( $atts['form_action']->ID ) ?>"></a>
		<a href="#" class="frm_add_tag frm_icon_font frm_add_trans_logic" data-emailkey="<?php echo absint( $atts['form_action']->ID ) ?>"></a>
	</td>
</tr>
