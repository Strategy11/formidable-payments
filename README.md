## Features
* List subscriptions and payments on the Formidable -> Payments page
* Adds a form action as a base to cover all gateways
* Adds payments to the CSV export for entries
* Adds a [frm-subscriptions] shortcode for listing the logged-in user's subscriptions with links to cancel and see the next bill dates

## Included hooks
* frm_payment_gateways (filter): Add a new gateway.

* frm_pay_action_defaults (filter): Add default values for any options added to the form action
* frm_pay_show_{gateway}_options (action): Show extra options in the form action

* frm_pay_{gateway}_refund_link (filter): Customize the HTML for a link to refund the payment
* frm_pay_{gateway}_cancel_link (filter): Customize the HTML for a link to cancel the subscription
* frm_pay_{gateway}_receipt (filter): Add a direct link to the payment on the gateway site

* frm_enqueue_{gateway}_scripts (action): Add scripts to the front-end form when this gateway is selected in the form action

## Add a Gateway
`public static function add_gateway( $gateways ) {
	$gateways['stripe'] = array(
		'label' => 'Stripe',
		'user_label' => __( 'Credit Card', 'formidable-stripe' ),
		'class' => 'Strp',
		
	);
	return $gateways;
}`

Required methods:
* Frm{class}ActionsController::trigger_gateway( $action, $entry, $form )
* Frm{class}ApiHelper::refund_payment( $gateway_transaction_id ) (when using the default refund link without changes)
* Frm{class}ApiHelper::cancel_subscription( $gateway_subscription_id ) (when using the default cancel link without changes)