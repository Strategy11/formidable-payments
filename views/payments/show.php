<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2><?php _e( 'Payments', 'formidable-payments' ) ?></h2>

    <?php include( FrmAppHelper::plugin_path() . '/classes/views/shared/errors.php' ); ?>
    
    <div id="poststuff" class="metabox-holder has-right-sidebar">
        <div class="inner-sidebar">
        <div id="submitdiv" class="postbox ">
			<h3 class="hndle"><span><?php _e( 'Payment Details', 'formidable-payments' ) ?></span></h3>
            <div class="inside">
                <div class="submitbox">
	                <div id="minor-publishing" style="border:none;">
	                <div class="misc-pub-section">
						<?php FrmTransPaymentsController::load_sidebar_actions( $payment ); ?>
	                    <div class="clear"></div>
	                </div>
	                </div>

            	<div id="major-publishing-actions">
            	    <div id="delete-action">                	    
						<a class="submitdelete deletion" href="<?php echo esc_url( add_query_arg( 'frm_action', 'destroy' ) ) ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete that payment?', 'formidable-payments' ) ?>');" title="<?php esc_attr_e( 'Delete' ) ?>">
							<?php _e( 'Delete' ) ?>
						</a>
            	    </div>
            	    
            	    <div id="publishing-action">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=formidable-payments&action=edit&id=' . $payment->id ) ) ?>" class="button-primary"><?php _e( 'Edit' ) ?></a>
                    </div>
                    <div class="clear"></div>
                </div>
                </div>
            </div>
        </div>
        </div>
        
        <div id="post-body">
        <div id="post-body-content">

            <div class="postbox">
                <div class="handlediv"><br/></div>
				<h3 class="hndle"><span><?php _e( 'Payment', 'formidable-payments' ) ?></span></h3>
                <div class="inside">
                    <table class="form-table">
						<tbody>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Status', 'formidable-payments' ) ?>:</th>
                            <td><?php echo FrmTransAppHelper::show_status( $payment->status ); ?></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e( 'User', 'formidable-payments') ?>:</th>
                            <td>
								<?php echo wp_kses_post( $user_name ) ?>
							</td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Entry', 'formidable-payments') ?>:</th>
							<td>
								<a href="?page=formidable-entries&amp;action=show&amp;frm_action=show&amp;id=<?php echo absint( $payment->item_id ) ?>">
									<?php echo absint( $payment->item_id ) ?>
								</a>
							</td>
                        </tr>

						<?php if ( ! empty( $payment->receipt_id ) ) { ?>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Receipt', 'formidable-payments' ) ?>:</th>
							<td>
								<?php FrmTransPaymentsController::show_receipt_link( $payment ); ?>
							</td>
                        </tr>
						<?php } ?>

						<?php FrmTransAppHelper::show_in_table( $payment->invoice_id, __( 'Invoice #', 'formidable-payments' ) ); ?>

						<?php if ( ! empty( $payment->sub_id ) ) { ?>
	                        <tr valign="top">
	                            <th scope="row"><?php _e( 'Subscription', 'formidable-payments' ) ?>:</th>
								<td>
									<a href="?page=formidable-payments&amp;action=show&amp;type=subscriptions&amp;id=<?php echo absint( $payment->sub_id ) ?>">
										<?php esc_html_e( 'View Subscription', 'formidable-payments' ) ?>
									</a>
								</td>
	                        </tr>
						<?php } ?>

                        <tr valign="top">
                            <th scope="row"><?php _e( 'Amount', 'formidable-payments' ) ?>:</th>
                            <td><?php echo FrmTransAppHelper::formatted_amount( $payment ) ?></td>
                        </tr>

						<?php if ( $payment->expire_date && $payment->expire_date != '0000-00-00' ) { ?>
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Payment Dates', 'formidable-payments' ) ?>:</th>
							<td>
								<?php echo FrmTransAppHelper::format_the_date( $payment->begin_date, $date_format ) ?> -
								<?php echo FrmTransAppHelper::format_the_date( $payment->expire_date, $date_format ) ?>
							</td>
						</tr>
						<?php } ?>
                        
                        <?php
						if ( $payment->meta_value ) {
							$payment->meta_value = maybe_unserialize( $payment->meta_value );
                        ?>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Payment Status Updates', 'formidable-payments' ) ?>:</th>
                            <td>
                            
							<?php foreach ( $payment->meta_value as $k => $metas ) {
								if ( empty( $metas ) ) {
									continue;
								}
							?>
                                <table class="widefat" style="border:none;">
                                <?php

								foreach ( $metas as $key => $meta ) { ?>
                                <tr>
									<th><?php echo sanitize_text_field( $key ) ?></th>
									<td><?php echo sanitize_text_field( $meta ) ?></td>
                                </tr>
								<?php
								} ?>
                                </table>
								<br/>
                            <?php } ?>
                            
                            </td>
                        </tr>
						<?php
						} ?>
                    </tbody>
					</table>
                </div>
            </div>
        </div>
        </div>
        
    </div>
</div>