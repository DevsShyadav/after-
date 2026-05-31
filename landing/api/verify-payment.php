<?php
/**
 * Verifies the Razorpay payment signature server-side. On success it
 * issues a signed, time-limited download link for the Pro ZIP and emails
 * a copy to the buyer.
 *
 * @package APG_Landing
 */

require __DIR__ . '/lib/bootstrap.php';

if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
	apg_json( array( 'success' => false, 'message' => 'Method not allowed.' ), 405 );
}

$cfg = apg_config();
$in  = apg_input();

$payment_id = isset( $in['razorpay_payment_id'] ) ? preg_replace( '/[^A-Za-z0-9_]/', '', $in['razorpay_payment_id'] ) : '';
$order_id   = isset( $in['razorpay_order_id'] ) ? preg_replace( '/[^A-Za-z0-9_]/', '', $in['razorpay_order_id'] ) : '';
$signature  = isset( $in['razorpay_signature'] ) ? (string) $in['razorpay_signature'] : '';

if ( '' === $payment_id || '' === $order_id || '' === $signature ) {
	apg_json( array( 'success' => false, 'message' => 'Missing payment details.' ) );
}

// Razorpay signature = HMAC_SHA256(order_id + "|" + payment_id, key_secret).
$expected = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $cfg['razorpay_key_secret'] );

if ( ! hash_equals( $expected, $signature ) ) {
	apg_json( array( 'success' => false, 'message' => 'Payment could not be verified.' ), 400 );
}

// Bind the download to the buyer's email captured at order creation.
$lead  = apg_recall_order( $order_id );
$email = $lead['email'] ?? '';
$token = apg_make_token( 'pro', $email );

// Build an absolute download URL.
$scheme   = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = $scheme . '://' . $host . rtrim( dirname( $_SERVER['SCRIPT_NAME'] ?? '/api' ), '/\\' );
$download = $base . '/download.php?token=' . rawurlencode( $token );

// Record the sale + notify.
apg_log_lead( 'sale', array_merge( (array) $lead, array( 'ref' => $payment_id ) ) );
apg_mail(
	$cfg['notify_email'] ?? '',
	'New Goldmine Pro sale',
	"A new Pro license was purchased.\n\nName: " . ( $lead['name'] ?? '' ) . "\nEmail: " . $email . "\nContact: " . ( $lead['contact'] ?? '' ) . "\nPayment: " . $payment_id
);

if ( ! empty( $cfg['email_customer'] ) && $email ) {
	apg_mail(
		$email,
		'Your After-Purchase Goldmine Pro download',
		"Thank you for your purchase!\n\nDownload your plugin here (valid for 24 hours):\n" . $download . "\n\nNeed help? Reply to this email or contact " . $cfg['support_email'] . "."
	);
}

apg_json(
	array(
		'success'      => true,
		'download_url' => $download,
	)
);
