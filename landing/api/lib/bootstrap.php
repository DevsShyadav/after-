<?php
/**
 * Shared helpers for the landing backend: config loading, JSON I/O,
 * a tiny Razorpay REST client, lead logging, signed download tokens
 * and secure file streaming.
 *
 * @package APG_Landing
 */

if ( ! defined( 'APG_LANDING' ) ) {
	define( 'APG_LANDING', true );
}

/**
 * Load (and cache) the configuration array.
 *
 * @return array
 */
function apg_config() {
	static $cfg = null;
	if ( null === $cfg ) {
		$cfg = require __DIR__ . '/../config.php';
	}
	return $cfg;
}

/**
 * Send a JSON response and stop.
 *
 * @param array $data   Payload.
 * @param int   $status HTTP status code.
 * @return void
 */
function apg_json( array $data, $status = 200 ) {
	http_response_code( $status );
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'X-Content-Type-Options: nosniff' );
	echo json_encode( $data );
	exit;
}

/**
 * Read and decode a JSON request body.
 *
 * @return array
 */
function apg_input() {
	$raw     = file_get_contents( 'php://input' );
	$decoded = json_decode( (string) $raw, true );
	return is_array( $decoded ) ? $decoded : array();
}

/**
 * Basic field sanitizers.
 */
function apg_clean( $v ) {
	return trim( filter_var( (string) $v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW ) );
}
function apg_email( $v ) {
	$v = trim( (string) $v );
	return filter_var( $v, FILTER_VALIDATE_EMAIL ) ? $v : '';
}

/**
 * Validate the name/email/contact lead fields.
 *
 * @param array $in Raw input.
 * @return array{name:string,email:string,contact:string}|null
 */
function apg_validate_lead( array $in ) {
	$name    = apg_clean( $in['name'] ?? '' );
	$email   = apg_email( $in['email'] ?? '' );
	$contact = preg_replace( '/[^0-9+\-\s]/', '', (string) ( $in['contact'] ?? '' ) );
	$contact = trim( $contact );

	if ( '' === $name || strlen( $name ) > 120 ) {
		return null;
	}
	if ( '' === $email ) {
		return null;
	}
	if ( strlen( $contact ) < 6 || strlen( $contact ) > 20 ) {
		return null;
	}

	return array(
		'name'    => substr( $name, 0, 120 ),
		'email'   => $email,
		'contact' => $contact,
	);
}

/**
 * Make a minimal Razorpay REST API call using cURL (no Composer needed).
 *
 * @param string $method HTTP method.
 * @param string $path   API path (e.g. "orders").
 * @param array  $body   Request body.
 * @return array Decoded response (with possible 'error' key).
 */
function apg_razorpay( $method, $path, array $body = array() ) {
	$cfg = apg_config();
	$url = 'https://api.razorpay.com/v1/' . ltrim( $path, '/' );

	$ch = curl_init( $url );
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => strtoupper( $method ),
			CURLOPT_USERPWD        => $cfg['razorpay_key_id'] . ':' . $cfg['razorpay_key_secret'],
			CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_SSL_VERIFYPEER => true,
		)
	);

	if ( ! empty( $body ) ) {
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body ) );
	}

	$response = curl_exec( $ch );
	$err      = curl_error( $ch );
	curl_close( $ch );

	if ( false === $response ) {
		return array( 'error' => array( 'description' => 'Connection error: ' . $err ) );
	}

	$decoded = json_decode( $response, true );
	return is_array( $decoded ) ? $decoded : array( 'error' => array( 'description' => 'Invalid gateway response' ) );
}

/**
 * Create a signed, time-limited download token.
 *
 * @param string $type  'pro' or 'trial'.
 * @param string $email Customer email (bound to the token).
 * @return string
 */
function apg_make_token( $type, $email ) {
	$cfg     = apg_config();
	$type    = ( 'pro' === $type ) ? 'pro' : 'trial';
	$expires = time() + (int) $cfg['download_ttl'];
	$payload = $type . '|' . strtolower( $email ) . '|' . $expires;
	$sig     = hash_hmac( 'sha256', $payload, $cfg['download_secret'] );
	return rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' ) . '.' . $sig;
}

/**
 * Validate a download token.
 *
 * @param string $token Token from the URL.
 * @return array{type:string,email:string}|null
 */
function apg_verify_token( $token ) {
	$cfg = apg_config();
	if ( ! is_string( $token ) || false === strpos( $token, '.' ) ) {
		return null;
	}

	list( $b64, $sig ) = explode( '.', $token, 2 );
	$payload = base64_decode( strtr( $b64, '-_', '+/' ) );
	if ( false === $payload ) {
		return null;
	}

	$expected = hash_hmac( 'sha256', $payload, $cfg['download_secret'] );
	if ( ! hash_equals( $expected, $sig ) ) {
		return null;
	}

	$parts = explode( '|', $payload );
	if ( count( $parts ) !== 3 ) {
		return null;
	}

	list( $type, $email, $expires ) = $parts;
	if ( (int) $expires < time() ) {
		return null;
	}

	return array(
		'type'  => ( 'pro' === $type ) ? 'pro' : 'trial',
		'email' => $email,
	);
}

/**
 * Append a lead/sale record to a CSV log (best-effort).
 *
 * @param string $kind 'trial' or 'sale'.
 * @param array  $data Lead data.
 * @return void
 */
function apg_log_lead( $kind, array $data ) {
	$dir = __DIR__ . '/../data';
	if ( ! is_dir( $dir ) ) {
		@mkdir( $dir, 0775, true );
	}
	// Protect the data directory.
	$ht = $dir . '/.htaccess';
	if ( ! file_exists( $ht ) ) {
		@file_put_contents( $ht, "Require all denied\n" );
	}

	$file = $dir . '/leads.csv';
	$row  = array(
		gmdate( 'Y-m-d H:i:s' ),
		$kind,
		$data['name'] ?? '',
		$data['email'] ?? '',
		$data['contact'] ?? '',
		$data['ref'] ?? '',
	);
	$fh = @fopen( $file, 'a' );
	if ( $fh ) {
		@fputcsv( $fh, $row );
		@fclose( $fh );
	}
}

/**
 * Send a notification email (best-effort, never fatal).
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $message Plain text body.
 * @return void
 */
function apg_mail( $to, $subject, $message ) {
	if ( empty( $to ) ) {
		return;
	}
	$cfg     = apg_config();
	$headers = 'From: Goldmine <' . ( $cfg['support_email'] ?? 'no-reply@localhost' ) . ">\r\n";
	@mail( $to, $subject, $message, $headers );
}

/**
 * Stream a plugin ZIP as a download, then stop.
 *
 * @param string $path     Absolute file path.
 * @param string $filename Download file name.
 * @return void
 */
function apg_stream_file( $path, $filename ) {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		http_response_code( 404 );
		header( 'Content-Type: text/plain' );
		echo 'Download file not found. Please contact support: ' . apg_config()['support_email'];
		exit;
	}

	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', '1' );
	}
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="' . basename( $filename ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'Cache-Control: private, no-store' );
	header( 'X-Content-Type-Options: nosniff' );

	$fp = fopen( $path, 'rb' );
	if ( $fp ) {
		fpassthru( $fp );
		fclose( $fp );
	}
	exit;
}


/**
 * Path to the order→email map (used to bind downloads to the buyer).
 *
 * @return string
 */
function apg_orders_path() {
	$dir = __DIR__ . '/../data';
	if ( ! is_dir( $dir ) ) {
		@mkdir( $dir, 0775, true );
	}
	$ht = $dir . '/.htaccess';
	if ( ! file_exists( $ht ) ) {
		@file_put_contents( $ht, "Require all denied\n" );
	}
	return $dir . '/orders.json';
}

/**
 * Remember which email an order belongs to.
 *
 * @param string $order_id Razorpay order id.
 * @param array  $lead     Lead data (name/email/contact).
 * @return void
 */
function apg_remember_order( $order_id, array $lead ) {
	$path  = apg_orders_path();
	$store = array();
	if ( is_file( $path ) ) {
		$decoded = json_decode( (string) file_get_contents( $path ), true );
		if ( is_array( $decoded ) ) {
			$store = $decoded;
		}
	}
	// Keep the store from growing unbounded.
	if ( count( $store ) > 5000 ) {
		$store = array_slice( $store, -2000, null, true );
	}
	$store[ $order_id ] = array(
		'email'   => $lead['email'],
		'name'    => $lead['name'],
		'contact' => $lead['contact'],
		'time'    => time(),
	);
	@file_put_contents( $path, json_encode( $store ), LOCK_EX );
}

/**
 * Recall the lead tied to an order.
 *
 * @param string $order_id Razorpay order id.
 * @return array|null
 */
function apg_recall_order( $order_id ) {
	$path = apg_orders_path();
	if ( ! is_file( $path ) ) {
		return null;
	}
	$store = json_decode( (string) file_get_contents( $path ), true );
	return ( is_array( $store ) && isset( $store[ $order_id ] ) ) ? $store[ $order_id ] : null;
}
