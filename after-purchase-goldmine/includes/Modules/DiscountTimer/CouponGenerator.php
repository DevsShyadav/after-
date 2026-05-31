<?php
/**
 * Generates unique, time-limited next-purchase coupons.
 *
 * @package APG
 */

namespace APG\Modules\DiscountTimer;

use APG\Install\Schema;
use APG\Support\Logger;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and persists WooCommerce coupons for the discount timer module.
 */
final class CouponGenerator {

	/**
	 * Order meta key used to remember the generated coupon code.
	 *
	 * @var string
	 */
	const ORDER_META = '_apg_timer_coupon';

	/**
	 * Get an existing coupon for the order or create a new one.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null Coupon data: code, amount, type, expires_at, expires_ts.
	 */
	public static function get_or_create_for_order( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return null;
		}

		$existing_code = $order->get_meta( self::ORDER_META );

		if ( $existing_code ) {
			$data = self::lookup( $existing_code );
			if ( $data ) {
				return $data;
			}
		}

		return self::create_for_order( $order );
	}

	/**
	 * Create a fresh coupon for the order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array|null
	 */
	private static function create_for_order( $order ) {
		$settings = Options::get( 'discount_timer' );
		$type     = 'percent' === $settings['discount_type'] ? 'percent' : 'fixed_cart';
		$amount   = (float) $settings['discount_amount'];

		if ( $amount <= 0 ) {
			return null;
		}

		$hours      = (int) $settings['expiry_hours'];
		$expires_ts = time() + ( $hours * HOUR_IN_SECONDS );
		$email      = $order->get_billing_email();
		$code       = self::unique_code();

		try {
			$coupon = new \WC_Coupon();
			$coupon->set_code( $code );
			$coupon->set_discount_type( $type );
			$coupon->set_amount( $amount );
			$coupon->set_individual_use( true );
			$coupon->set_usage_limit( 1 );
			$coupon->set_date_expires( $expires_ts );
			$coupon->set_description( __( 'After-Purchase Goldmine — next purchase reward.', 'after-purchase-goldmine' ) );

			if ( ! empty( $settings['min_spend'] ) ) {
				$coupon->set_minimum_amount( (float) $settings['min_spend'] );
			}

			if ( $email ) {
				$coupon->set_email_restrictions( array( $email ) );
			}

			$coupon->save();
		} catch ( \Exception $e ) {
			Logger::error( 'Failed to create timer coupon: ' . $e->getMessage(), array( 'order' => $order->get_id() ) );
			return null;
		}

		self::store(
			array(
				'code'            => $code,
				'source'          => 'discount_timer',
				'order_id'        => $order->get_id(),
				'customer_email'  => $email,
				'discount_type'   => $settings['discount_type'],
				'discount_amount' => $amount,
				'expires_at'      => gmdate( 'Y-m-d H:i:s', $expires_ts ),
			)
		);

		$order->update_meta_data( self::ORDER_META, $code );
		$order->save();

		return array(
			'code'       => $code,
			'amount'     => $amount,
			'type'       => $settings['discount_type'],
			'expires_at' => gmdate( 'Y-m-d H:i:s', $expires_ts ),
			'expires_ts' => $expires_ts,
		);
	}

	/**
	 * Create a generic reward coupon (used by the referral module).
	 *
	 * @param array $args code-less spec: email, type, amount, days, source, prefix.
	 * @return string|null The created coupon code or null on failure.
	 */
	public static function create_reward( array $args ) {
		$type   = ( isset( $args['type'] ) && 'percent' === $args['type'] ) ? 'percent' : 'fixed_cart';
		$amount = isset( $args['amount'] ) ? (float) $args['amount'] : 0;

		if ( $amount <= 0 ) {
			return null;
		}

		$days       = isset( $args['days'] ) ? max( 1, (int) $args['days'] ) : 30;
		$expires_ts = time() + ( $days * DAY_IN_SECONDS );
		$email      = isset( $args['email'] ) ? sanitize_email( $args['email'] ) : '';
		$prefix     = isset( $args['prefix'] ) ? $args['prefix'] : 'REWARD';
		$code       = self::unique_code( $prefix );

		try {
			$coupon = new \WC_Coupon();
			$coupon->set_code( $code );
			$coupon->set_discount_type( $type );
			$coupon->set_amount( $amount );
			$coupon->set_usage_limit( 1 );
			$coupon->set_date_expires( $expires_ts );
			$coupon->set_description( __( 'After-Purchase Goldmine reward.', 'after-purchase-goldmine' ) );

			if ( $email ) {
				$coupon->set_email_restrictions( array( $email ) );
			}

			$coupon->save();
		} catch ( \Exception $e ) {
			Logger::error( 'Failed to create reward coupon: ' . $e->getMessage() );
			return null;
		}

		self::store(
			array(
				'code'            => $code,
				'source'          => isset( $args['source'] ) ? sanitize_key( $args['source'] ) : 'referral',
				'order_id'        => isset( $args['order_id'] ) ? absint( $args['order_id'] ) : 0,
				'customer_email'  => $email,
				'discount_type'   => 'percent' === $type ? 'percent' : 'fixed',
				'discount_amount' => $amount,
				'expires_at'      => gmdate( 'Y-m-d H:i:s', $expires_ts ),
			)
		);

		return $code;
	}

	/**
	 * Generate a unique coupon code.
	 *
	 * @param string $prefix Code prefix.
	 * @return string
	 */
	private static function unique_code( $prefix = 'THANKS' ) {
		$prefix = strtoupper( preg_replace( '/[^A-Z0-9]/', '', strtoupper( $prefix ) ) );
		$prefix = '' !== $prefix ? $prefix : 'THANKS';

		do {
			$code = $prefix . strtoupper( wp_generate_password( 6, false, false ) );
		} while ( wc_get_coupon_id_by_code( $code ) );

		return $code;
	}

	/**
	 * Persist a coupon record in the plugin's tracking table.
	 *
	 * @param array $data Coupon fields.
	 * @return void
	 */
	private static function store( array $data ) {
		global $wpdb;

		$data['used']       = 0;
		$data['created_at'] = current_time( 'mysql' );

		$wpdb->insert( Schema::table( 'coupons' ), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Look up a stored coupon by code and confirm it still exists in WC.
	 *
	 * @param string $code Coupon code.
	 * @return array|null
	 */
	private static function lookup( $code ) {
		global $wpdb;

		$table = Schema::table( 'coupons' );

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( "SELECT * FROM {$table} WHERE code = %s", $code ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row || ! wc_get_coupon_id_by_code( $code ) ) {
			return null;
		}

		$ts = $row['expires_at'] ? strtotime( $row['expires_at'] . ' UTC' ) : 0;

		return array(
			'code'       => $row['code'],
			'amount'     => (float) $row['discount_amount'],
			'type'       => $row['discount_type'],
			'expires_at' => $row['expires_at'],
			'expires_ts' => $ts,
		);
	}
}
