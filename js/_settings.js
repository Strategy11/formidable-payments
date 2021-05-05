/*global jQuery:false, frmsecurepaySettings, securePayUI, ajaxurl */

var frmSecurePaySetting;

function frmSecurePaySettingJS() {

  var mySecurePayUI;

  function initSecurePayUI() {
	  mySecurePayUI = new securePayUI.init({
        containerId: 'securepay-ui-container',
        scriptId: 'securepay-ui-js',
        clientId: frmsecurepaySettings.clientId,
        merchantCode: frmsecurepaySettings.merchantCode,
        card: { // card specific config options / callbacks
            onTokeniseSuccess: function(tokenisedCard) {
              // card was successfully tokenised
              if (!tokenisedCard) {
                return;
              }

              // send payment API
              postSecurePayPayment(tokenisedCard.token);
            },
            onTokeniseError: function(errors) {
              enableSubmit();
            }
        }
      });
  }

	function setupSettings() {
    initSecurePayUI();

	  jQuery('#start-simulation').on('click', handleSimulation);
	  jQuery('#frm_securepay_test_mode').on('change', handleSimulationDisplay);

    checkTestModeOn(document.getElementById('frm_securepay_test_mode'));
  }

  function handleSimulation(e) {
    jQuery(e.currentTarget).attr( 'disabled', 'disabled' );
	  e.preventDefault();

	  mySecurePayUI.tokenise();
  }

  function handleSimulationDisplay(e) {
	  var check = e.currentTarget;
	  checkTestModeOn(check);
  }

  function checkTestModeOn(el) {
	  var $simulator = jQuery('#frm_securepay_simulation_container');
	  if (el.checked) {
	    $simulator.show();
    } else {
	    $simulator.hide();
    }
  }

  function enableSubmit() {
		jQuery('#start-simulation').attr( 'disabled', false );
	}

	function postSecurePayPayment(token) {
    var successAmount = [1000, 1008, 1511];
    var failedAmount = [1005, 1031, 1051, 1033, 1061, 1007, 1061, 1009, 1030];
    var randomAmount = [1000, 1008, 1511, 1005, 1031, 1051];
    var amount = 0;
    var result = parseInt(jQuery('input[name="frm_simulate_result"]:checked').val(), 10);
    if (result === 0) {
      // simulate success payment
      var r = Math.floor(Math.random() * successAmount.length);
      amount = successAmount[r];
    } else if (result === 1) {
      // simulate failed payment
      var r = Math.floor(Math.random() * failedAmount.length);
      amount = failedAmount[r];
    } else if (result === 2) {
      // simulate random payment
      var r = Math.floor(Math.random() * randomAmount.length);
      amount = randomAmount[r];
    }

    var data = {
      merchantCode: frmsecurepaySettings.merchantCode,
      token: token,
      ip: '127.0.0.1',
      amount: amount,
    }

    jQuery.ajax({
      type: 'POST',
      url: frmsecurepaySettings.paymentUrl,
      data: data,
      success: function (result) {
        var $container = jQuery('#securepay_settings');
        result = JSON.parse(result); // re-parse first
        jQuery('#simulation-result').html('<pre>' + JSON.stringify(result, undefined, 2) + '</pre>').show();
        window.location.href = '#simulation-result';
      },
      error: function (xhr, ajaxOptions, thrownError) {
        console.log(xhr.responseText);
        console.log(thrownError);
      },
      complete: function() {
        enableSubmit();
      },
      dataType: 'json',
    });
  }

	return {
		init: function() {
			setupSettings();
		}
	};
}

frmSecurePaySetting = frmSecurePaySettingJS();

jQuery( document ).ready(
	function( $ ) {
	  if ($('#securepay_settings').length > 0) {
      frmSecurePaySetting.init();
    }
	}
);
