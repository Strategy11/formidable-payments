<style type="text/css">
  #securepay-ui-container {
    position: relative;
    width: 100%;
  }

  #securepay-ui-container iframe {
    background: white;
    width: 100%;
    height: 100%;
    min-height: 180px;
  }

  #start-simulation:disabled {
    background-color: var(--grey)!important;
  }
</style>
<?php FrmSecurePaySettingsController::register_settings_scripts(); ?>

  <h3 class="frm-no-border frm_no_top_margin">SecurePay Credentials</h3>
	<table class="form-table">
		 <tr class="form-field" valign="top">
			<td width="200px">
				<label for="frm_securepay_merchant_code">
					<?php esc_html_e( 'SecurePay Merchant Code', 'frmsecurepay' ); ?>
				</label>
			</td>
			<td>
				<input type="text" name="frm_securepay_merchant_code" id="frm_securepay_merchant_code" value="<?php echo esc_attr( $settings->settings->merchant_code ); ?>" class="frm_long_input" />
			</td>
		</tr>
    <tr class="form-field" valign="top">
			<td width="200px">
				<label for="frm_securepay_client_id">
					<?php esc_html_e( 'SecurePay Client ID', 'frmsecurepay' ); ?>
				</label>
			</td>
			<td>
				<input type="text" name="frm_securepay_client_id" id="frm_securepay_client_id" value="<?php echo esc_attr( $settings->settings->client_id ); ?>" class="frm_long_input" />
			</td>
		</tr>
    <tr class="form-field" valign="top">
			<td width="200px">
				<label for="frm_securepay_client_secret">
					<?php esc_html_e( 'SecurePay Client Secret', 'frmsecurepay' ); ?>
				</label>
			</td>
			<td>
				<input type="text" name="frm_securepay_client_secret" id="frm_securepay_client_secret" value="<?php echo esc_attr( $settings->settings->client_secret ); ?>" class="frm_long_input" />
			</td>
		</tr>
    <tr class="form-field" valign="top">
      <td>
        <label><?php esc_html_e( 'Test Mode', 'frmsecurepay' ); ?></label>
      </td>
      <td>
        <label for="frm_securepay_test_mode">
          <input type="checkbox" name="frm_securepay_test_mode" id="frm_securepay_test_mode" value="1" <?php checked( $settings->settings->test_mode, 1 ); ?> />
          <?php esc_html_e( 'Use the SecurePay test mode', 'frmsecurepay' ); ?>
        </label>
        <?php if ( ! is_ssl() ) { ?>
          <br/><em><?php esc_html_e( 'Your site is not using SSL. Before using SecurePay to collect live payments, you will need to install an SSL certificate on your site.', 'frmsecurepay' ); ?></em>
        <?php } ?>
      </td>
    </tr>
	</table>

  <div id="frm_securepay_simulation_container">
    <h3>Payment Simulation</h3>
    <div class="instruction">
      <p><label>Use below card detail</label></p>
      <table class="form-table" style="margin-bottom: .5em">
        <tr>
          <th style="padding: 0;">Card Type</th>
          <th style="padding: 0;">Card Number</th>
        </tr>
        <tr>
          <td>Visa</td>
          <td>4111111111111111</td>
        </tr>
        <tr>
          <td>Visa</td>
          <td>4242424242424242</td>
        </tr>
        <tr>
          <td>MasterCard</td>
          <td>5555555555554444</td>
        </tr>
        <tr>
          <td>American Express</td>
          <td>378282246310005</td>
        </tr>
      </table>
      <p><label><strong>Expiry date</strong>: any date greater than today<br>
          <strong>CCV</strong>: 123</label></p>
    </div>
    <div id="securepay-ui-container"></div>
    <p>
      <label class="frm_left_label">Simulate Result</label>
      <label class="frm-example-icon">
        <input type="radio" name="frm_simulate_result" value="0" checked="checked">
        Success
      </label>
      <label class="frm-example-icon">
        <input type="radio" name="frm_simulate_result" value="1">
        Failed
      </label>
      <label class="frm-example-icon">
        <input type="radio" name="frm_simulate_result" value="2">
        Random
      </label>
    </p>
    <p>
      <label class="frm_left_label">&nbsp;</label>
      <input id="start-simulation" class="button-primary frm-button-primary" type="submit" value="Simulate Payment">
    </p>
    <p id="simulation-result" class="hidden"></p>
  </div>
