<?php
/**
 * Captures a trial lead and returns a signed download link for the
 * 24-hour trial ZIP. No payment required.
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

apg_log_lead( 'trial', $lead );

apg_mail(
	$cfg['notify_email'] ?? '',
	'New Goldmine trial download',
	"Name: {$lead['name']}\nEmail: {$lead['email']}\nContact: {$lead['contact']}"
);

$token = apg_make_token( 'trial', $lead['email'] );

$scheme   = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base     = $scheme . '://' . $host . rtrim( dirname( $_SERVER['SCRIPT_NAME'] ?? '/api' ), '/\\' );
$download = $base . '/download.php?token=' . rawurlencode( $token );

if ( ! empty( $cfg['email_customer'] ) ) {
	apg_mail(
		$lead['email'],
		'Your After-Purchase Goldmine trial',
		"Thanks for trying Goldmine!\n\nDownload the 24-hour trial here:\n" . $download . "\n\nThe trial removes itself automatically after 24 hours. Upgrade anytime at our site.\n\nSupport: " . $cfg['support_email']
	);
}

apg_json(
	array(
		'success'      => true,
		'download_url' => $download,
	)
);
