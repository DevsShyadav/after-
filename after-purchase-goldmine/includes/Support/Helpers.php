<?php
/**
 * Shared helper utilities.
 *
 * @package APG
 */

namespace APG\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless helper methods used across the plugin.
 */
final class Helpers {

	/**
	 * Verify that a provided order key matches the given order.
	 *
	 * This is the same secret WooCommerce uses to authorize the
	 * order-received page, so it is a safe ownership check for the
	 * unauthenticated thank-you context.
	 *
	 * @param int    $order_id  Order ID.
	 * @param string $order_key Order key to validate.
	 * @return \WC_Order|false The order on success, false otherwise.
	 */
	public static function verify_order_access( $order_id, $order_key ) {
		$order_id  = absint( $order_id );
		$order_key = is_string( $order_key ) ? sanitize_text_field( wp_unslash( $order_key ) ) : '';

		if ( ! $order_id || '' === $order_key ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		if ( ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			return false;
		}

		return $order;
	}

	/**
	 * Locate and render a template, allowing theme overrides.
	 *
	 * Themes may override templates by placing them in
	 * `your-theme/after-purchase-goldmine/{path}`.
	 *
	 * @param string $path Relative template path (without leading slash).
	 * @param array  $args Variables made available to the template.
	 * @return string Rendered HTML.
	 */
	public static function get_template( $path, array $args = array() ) {
		$path = ltrim( $path, '/' );

		$override = locate_template( array( 'after-purchase-goldmine/' . $path ) );
		$file     = $override ? $override : APG_PLUGIN_DIR . 'templates/' . $path;

		if ( ! is_readable( $file ) ) {
			return '';
		}

		// Make args available as named variables inside the template.
		if ( ! empty( $args ) ) {
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}

	/**
	 * Render a formatted price using WooCommerce currency settings.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function price( $amount ) {
		return function_exists( 'wc_price' ) ? wc_price( (float) $amount ) : number_format_i18n( (float) $amount, 2 );
	}

	/**
	 * Generate a deterministic, URL-safe referral code from an email.
	 *
	 * @param string $email     Customer email.
	 * @param int    $entity_id Optional order/customer id for uniqueness.
	 * @return string
	 */
	public static function referral_code( $email, $entity_id = 0 ) {
		$email = strtolower( trim( (string) $email ) );
		$seed  = $email . '|' . wp_salt( 'auth' );
		$hash  = substr( hash( 'sha256', $seed ), 0, 8 );
		$name  = strtoupper( preg_replace( '/[^a-z0-9]/', '', substr( $email, 0, 4 ) ) );
		$name  = '' !== $name ? $name : 'REF';

		return $name . strtoupper( $hash );
	}

	/**
	 * Build the referral cookie name.
	 *
	 * @return string
	 */
	public static function referral_cookie_name() {
		return 'apg_ref';
	}

	/**
	 * Sanitize a string that may contain merge tags, preserving them.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function clean_text( $value ) {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	/**
	 * Replace supported merge tags inside a string.
	 *
	 * @param string $text Text containing tags like {site}, {first_name}.
	 * @param array  $vars Replacement map keyed by tag name (without braces).
	 * @return string
	 */
	public static function merge_tags( $text, array $vars = array() ) {
		$defaults = array(
			'site' => get_bloginfo( 'name' ),
		);

		$vars = array_merge( $defaults, $vars );

		foreach ( $vars as $tag => $value ) {
			$text = str_replace( '{' . $tag . '}', (string) $value, $text );
		}

		return $text;
	}

	/**
	 * Get the customer first name from an order with a friendly fallback.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	public static function first_name( $order ) {
		$name = $order->get_billing_first_name();

		return '' !== $name ? $name : __( 'there', 'after-purchase-goldmine' );
	}

	/**
	 * Convert a hex color to an "r, g, b" string for rgba() usage.
	 *
	 * @param string $hex Hex color (#rrggbb).
	 * @return string
	 */
	public static function hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '99, 102, 241';
		}

		return hexdec( substr( $hex, 0, 2 ) ) . ', ' . hexdec( substr( $hex, 2, 2 ) ) . ', ' . hexdec( substr( $hex, 4, 2 ) );
	}
}
