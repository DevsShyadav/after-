<?php
/**
 * Validates a signed download token and streams the correct plugin ZIP.
 *
 * @package APG_Landing
 */

require __DIR__ . '/lib/bootstrap.php';

$cfg   = apg_config();
$token = isset( $_GET['token'] ) ? (string) $_GET['token'] : '';
$data  = apg_verify_token( $token );

if ( null === $data ) {
	http_response_code( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "This download link is invalid or has expired.\nPlease contact " . $cfg['support_email'] . ' for help.';
	exit;
}

if ( 'pro' === $data['type'] ) {
	apg_stream_file( $cfg['pro_zip'], $cfg['pro_zip_name'] );
} else {
	apg_stream_file( $cfg['trial_zip'], $cfg['trial_zip_name'] );
}
