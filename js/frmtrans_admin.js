function frmTransAdminJS(){

	function toggleSub(){
		var val = this.value;
		var show = (val == 'recurring');
		slideOpts(this, show, '.frm_trans_sub_opts');
		toggleOpts(this, !show, '.frm_gateway_no_recur');
	}

	function slideOpts(opt, show, c){
		var opts = jQuery(opt).closest('.frm_form_action_settings').find(c);
		if(show){
			opts.slideDown('fast');
		}else{
			opts.slideUp('fast');
		}
	}

	function toggleOpts(opt, show, c){
		var opts = jQuery(opt).closest('.frm_form_action_settings').find(c);
		if(show){
			opts.show();
		}else{
			opts.hide();
		}
	}

	function toggleGateway(){
		var gateway = this.value;
		var checked = this.checked;
		if ( checked ) {
			toggleOpts(this, checked, '.show_'+gateway);
		} else {
			var settings = jQuery(this).closest('.frm_form_action_settings');
			var showClasses = settings.find('.frm_gateway_opt input:checked').map(function() {
    			return 'show_' + this.value;
			}).get();
			var gatewaySettings = settings.find('.show_' + gateway);
			for ( var i = 0; i < gatewaySettings.length; i++ ) {
				if ( ! hasClass( gatewaySettings[i].className, showClasses ) ) {
					gatewaySettings[i].style.display = 'none';
				}
			}
		}
	}

	function hasClass( thisClass, showClasses ) {
		var theseClasses = thisClass.split(/\s+/);
		theseClasses = theseClasses.filter(function(n) {
		    return showClasses.indexOf(n) != -1;
		});

		return theseClasses.length >= 1;
	}

	function toggleShipping(){
		slideOpts(this, this.checked, '.frm_trans_shipping');
	}

	function addAfterPayRow(){
		var id = jQuery(this).data('emailkey');
		var rowNum = 0;
		var form_id = document.getElementById('form_id').value;
		if(jQuery('#frm_form_action_'+id+' .frmtrans_after_pay_row').length){
			rowNum = 1 + parseInt(jQuery('#frm_form_action_'+id+' .frmtrans_after_pay_row:last').attr('id').replace('frmtrans_after_pay_row_'+id+'_', ''));	
		}
		jQuery.ajax({
			type:'POST',url:ajaxurl,
			data:{action:'frmtrans_after_pay', email_id:id, form_id:form_id, row_num:rowNum, nonce:frmGlobal.nonce},
			success:function(html){
				var addButton = jQuery(document.getElementById('frmtrans_after_pay_'+id));
				addButton.fadeOut('slow', function(){
					var $logicRow = addButton.next('.frmtrans_after_pay_rows');
					$logicRow.find('tbody').append(html);
					$logicRow.fadeIn('slow');
				});
			}
		});
		return false;
	}

	function runAjaxLink( e ) {
		e.preventDefault();

		var $link = jQuery(this);
		var confirmText = $link.data('deleteconfirm');
		if ( typeof confirmText === 'undefined' || confirm( confirmText ) ) {
			var href = $link.attr('href');
			var id = $link.data('tempid');

			$link.replaceWith('<span class="frm-loading-img" id="'+ id +'"></span>');
			jQuery.ajax({
				type:'GET', url:href,
				data:{nonce:frm_trans_vars.nonce},
				success:function(html){
					jQuery('#'+id).replaceWith(html);
				}
			});
		}

		return false;
	}

	return{
		init: function(){
			var actions = document.getElementById('frm_notification_settings');
			if ( actions !== null ) {
				jQuery(actions).on('change', '.frm_trans_type', toggleSub);
				jQuery('.frm_form_settings').on('click', '.frm_add_trans_logic', addAfterPayRow);
				jQuery('.frm_form_settings').on('click', '.frm_gateway_opt input', toggleGateway);
				jQuery('.frm_form_settings').on('click', '.frm_trans_shipping_box', toggleShipping);
			}

			jQuery('.frm_trans_ajax_link').click( runAjaxLink );
		}
	};
}

var frmTransAdmin = frmTransAdminJS();

jQuery(document).ready(function($){
	frmTransAdmin.init();
});