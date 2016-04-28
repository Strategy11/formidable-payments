[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Strategy11/formidable-payments/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Strategy11/formidable-payments/?branch=master)

## Features
* List subscriptions and payments on the Formidable -> Payments page
* Adds a form action as a base to cover all gateways
* Adds payments to the CSV export for entries
* Adds a [frm-subscriptions] shortcode for listing the logged-in user's subscriptions with links to cancel and see the next bill dates

## Included hooks
* `frm_payment_gateways` (filter): Add a new gateway.

* `frm_pay_action_defaults` (filter): Add default values for any options added to the form action
* `frm_pay_show_{gateway}_options` (action): Show extra options in the form action

* `frm_pay_{gateway}_refund_link`` (filter): Customize the HTML for a link to refund the payment
* `frm_pay_{gateway}_cancel_link` (filter): Customize the HTML for a link to cancel the subscription
* `frm_pay_{gateway}_receipt` (filter): Add a direct link to the payment on the gateway site

* `frm_enqueue_{gateway}_scripts` (action): Add scripts to the front-end form when this gateway is selected in the form action

## Add a Gateway
```php
public static function add_gateway( $gateways ) {
	$gateways['stripe'] = array(
		'label' => 'Stripe',
		'user_label' => __( 'Credit Card', 'formidable-stripe' ),
		'class' => 'Strp',
	);
	return $gateways;
}
```

###Required methods
`Frm{class}ActionsController::trigger_gateway( $action, $entry, $form )`
* Always required. This is where the payment is processed
* Return array( 'success' => false, 'run_triggers' => false, 'show_errors' => true );

`Frm{class}ApiHelper::refund_payment( $gateway_transaction_id )`
* Required when using the default refund link without changes
* Return boolean - true if refunded, false if fail

`Frm{class}ApiHelper::cancel_subscription( $gateway_subscription_id )`
* Required when using the default cancel link without changes
* Return boolean - true if canceled, false if fail

###Running Triggers
There are two types of settings that can be triggered after the status on a payment changes: other form actions, and changing values in the entry. When a one-time payment is created in the trigger_gateway function, there is no need to add extra code to trigger changes. But if changes are made to a payment later, it will need extra code.

```php
FrmTransActionsController::trigger_payment_status_change( array( 'status' => 'complete', 'payment' => $payment ) );
```

### Payment Statuses
* pending
* complete
* failed
* refunded

### Subscription Statuses
* pending
* active
* future_cancel: Canceled, but the next bill date is in the future
* canceled
* void
