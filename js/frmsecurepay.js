/*global jQuery:false, frmsecurepayGlobal, securePayUI, ajaxurl */

var frmSecurePayProcess;

function frmSecurePayProcessJS() {

	var $thisForm = false,
    mySecurePayUI;

	function initSecurePayUI() {
	  mySecurePayUI = new securePayUI.init({
        containerId: 'securepay-ui-container',
        scriptId: 'securepay-ui-js',
        clientId: frmsecurepayGlobal.clientId,
        merchantCode: frmsecurepayGlobal.merchantCode,
        card: { // card specific config options / callbacks
            onTokeniseSuccess: function(tokenisedCard) {
              // card was successfully tokenised
              if (!tokenisedCard) {
                console.log('Failed to tokenise card.')
                return;
              }

              tokenHandler(tokenisedCard);
            },
            onTokeniseError: function(errors) {
              console.log('Error while tokenising card.');
              enableSubmit();
            }
        },
        style: {
          label: {
            font: {
              family: 'var(--font)',
              color: 'var(--label-color)',
            }
          },
          input: {
            font: {
              family: 'var(--font)',
              color: 'var(--text-color)',
            },
          },
        },
      });
  }

	function tokenHandler( tokenisedCard ) {
    // insert the token into the form so it gets submitted to the server
    $thisForm.append( '<input type="hidden" name="securepayToken" value="' + tokenisedCard.token + '" />' );
    $thisForm.append( '<input type="hidden" name="securepayLast4" value="' + tokenisedCard.last4 + '" />' );
		$thisForm.append( '<input type="hidden" name="securepayScheme" value="' + tokenisedCard.scheme + '" />' );

		$thisForm.get( 0 ).submit();
	}

	function validateForm(e) {
	  // disable the submit button to prevent repeated clicks
		if ( typeof frmFrontForm.showSubmitLoading === 'function' ) {
			frmFrontForm.showSubmitLoading( $thisForm );
		} else {
			$thisForm.find( 'input[type="submit"],input[type="button"],button[type="submit"]' ).attr( 'disabled', 'disabled' );
		}

	  e.preventDefault();
	  e.stopPropagation();
	  mySecurePayUI.tokenise();
  }

	function enableSubmit() {
		if ( typeof frmFrontForm.removeSubmitLoading === 'function' ) {
			frmFrontForm.removeSubmitLoading( $thisForm, 'enable', 0 );
		} else {
			$thisForm.find( 'input[type="submit"],input[type="button"],button[type="submit"]' ).attr( 'disabled', false );
		}
	}

	return {
		init: function() {
			initSecurePayUI();

			$thisForm = jQuery('.frm-show-form');
			jQuery('.frm_button_submit.frm_final_submit').on('click', validateForm);
		}
	};
}

frmSecurePayProcess = frmSecurePayProcessJS();

jQuery( document ).ready(
	function( $ ) {
	  if ($('#securepay-ui-container').length > 0) {
      frmSecurePayProcess.init();
    }
	}
);
