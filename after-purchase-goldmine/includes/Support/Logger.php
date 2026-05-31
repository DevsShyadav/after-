<?php
/**
 * Lightweight logging wrapper around the WooCommerce logger.
 *
 * @package APG
 */

namespace APG\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized debug/error logging.
 */
final class Logger {

	/**
	 * Log source/context name.
	 *
	 * @var string
	 */
	const SOURCE = 'after-purchase-goldmine';

	/**
	 * Write a log entry.
	 *
	 * @param string $message Message to log.
	 * @param string $level   One of: emergency|alert|critical|error|warning|notice|info|debug.
	 * @param array  $context Optional structured context.
	 * @return void
	 */
	public static function log( $message, $level = 'info', $context = array() ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();

		if ( ! $logger ) {
			return;
		}

		if ( ! empty( $context ) ) {
			$message .= ' ' . wp_json_encode( $context );
		}

		$logger->log( $level, $message, array( 'source' => self::SOURCE ) );
	}

	/**
	 * Shortcut for error-level logging.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public static function error( $message, $context = array() ) {
		self::log( $message, 'error', $context );
	}

	/**
	 * Shortcut for debug-level logging.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	public static function debug( $message, $context = array() ) {
		self::log( $message, 'debug', $context );
	}
}
