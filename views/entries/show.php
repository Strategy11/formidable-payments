<div class="postbox">
  <h3 class="hndle">
    <span>Transaction Data</span>
  </h3>
  <table cellspacing="0" class="frm-alt-table"><tbody>
    <tr><th>Transaction datetime</th><td><?php echo date_format($created_at, 'Y-m-d H:i:s T') ?></td></tr>
    <tr><th>Transaction amount</th><td><?php echo $transaction_data['amount'] ?></td></tr>
    <tr><th>Status code</th><td><?php echo $transaction_data['status_code'] ?></td></tr>
    <tr><th>Status description</th><td><?php echo $transaction_data['status_description'] ?></td></tr>
    <tr><th>Response code</th><td><?php echo $transaction_data['response_code'] ?></td></tr>
    <tr><th>Response text</th><td><?php echo $transaction_data['response_text'] ?></td></tr>
    <tr><th>Bank transaction ID</th><td><?php echo $transaction_data['bank_transaction_id'] ?></td></tr>
    <tr><th>Gateway mode</th><td><?php echo $transaction_data['gateway_mode'] ?></td></tr>
    <tr><th>Entry ID</th><td><?php echo $entry_id ?></td></tr>
  </table>
</div>
