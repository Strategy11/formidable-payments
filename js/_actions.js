( function() {
	function setupSecurePayPaymentActionUI() {
		if (isOnNotificationTab()) {
		  // alert(123);
    }
	}

	function isOnNotificationTab() {
	  var el = document.getElementById('frm_notification_settings');
	  var style = window.getComputedStyle(el);

	  return (style.display !== 'none');
  }

	jQuery( document ).ready( setupSecurePayPaymentActionUI );
}() );
