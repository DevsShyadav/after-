<?php
/**
 * Creates a Razorpay order for the Pro purchase and returns the public
 * details the browser needs to open Razorpay Checkout. The key secret
 * never leaves the server.
 *
 * @package APG_Landing
 */

require __DIR__ . '/lib/bootstrap.php';

if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	apg_json( array( 'success' => false, 'message' => 'Method not allowed.' ), 405 );
}

$cfg  = apg_config();
$lead = apg_validate_lead( apg_input() );

if ( null === $lead ) {
	apg_json( array( 'success' => false, 'message' => 'Please enter a valid name, email and mobile number.' ) );
}

if ( false !== strpos( $cfg['razorpay_key_id'], 'XXXX' ) ) {
	apg_json( array( 'success' => false, 'message' => 'Payments are not configured yet. Please contact ' . $cfg['support_email'] . '.' ) );
}

$order = apg_razorpay(
	'POST',
	'orders',
	array(
		'amount'          => (int) $cfg['price'],
		'currency'        => $cfg['currency'],
		'receipt'         => 'apg_' . time() . '_' . substr( md5( $lead['email'] ), 0, 8 ),
		'payment_capture' => 1,
		'notes'           => array(
			'product' => 'goldmine-pro',
			'email'   => $lead['email'],
			'name'    => $lead['name'],
		),
	)
);

if ( empty( $order['id'] ) ) {
	$msg = isset( $order['error']['description'] ) ? $order['error']['description'] : 'Could not start checkout.';
	apg_json( array( 'success' => false, 'message' => $msg ) );
}

apg_remember_order( $order['id'], $lead );

apg_json(
	array(
		'success'  => true,
		'key_id'   => $cfg['razorpay_key_id'],
		'order_id' => $order['id'],
		'amount'   => (int) $cfg['price'],
		'currency' => $cfg['currency'],
	)
);
