<?php
/**
 * Data access for the referral program.
 *
 * @package APG
 */

namespace APG\Modules\Referral;

use APG\Install\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Stores ambassador registrations and referral conversions.
 */
final class ReferralRepository {

	/**
	 * Reward status used for ambassador (code owner) rows.
	 *
	 * @var string
	 */
	const STATUS_AMBASSADOR = 'ambassador';

	/**
	 * Table name accessor.
	 *
	 * @return string
	 */
	private static function table() {
		return Schema::table( 'referrals' );
	}

	/**
	 * Ensure an ambassador mapping (code -> email) exists.
	 *
	 * @param string $email Referrer email.
	 * @param string $code  Referral code.
	 * @return void
	 */
	public static function register_ambassador( $email, $code ) {
		global $wpdb;

		$email = sanitize_email( $email );
		$code  = sanitize_text_field( $code );

		if ( ! $email || ! $code ) {
			return;
		}

		$table  = self::table();
		$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE referral_code = %s AND reward_status = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$code,
				self::STATUS_AMBASSADOR
			)
		);

		if ( $exists ) {
			return;
		}

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'referrer_email' => $email,
				'referral_code'  => $code,
				'reward_status'  => self::STATUS_AMBASSADOR,
				'created_at'     => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Resolve the referrer email for a referral code.
	 *
	 * @param string $code Referral code.
	 * @return string Empty string when not found.
	 */
	public static function referrer_email_by_code( $code ) {
		global $wpdb;

		$code  = sanitize_text_field( $code );
		$table = self::table();

		$email = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT referrer_email FROM {$table} WHERE referral_code = %s AND referrer_email <> '' ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$code
			)
		);

		return $email ? (string) $email : '';
	}

	/**
	 * Whether a conversion already exists for an order (idempotency guard).
	 *
	 * @param int $order_id Order id.
	 * @return bool
	 */
	public static function order_has_conversion( $order_id ) {
		global $wpdb;

		$order_id = absint( $order_id );
		$table    = self::table();

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE referred_order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Record a referral conversion.
	 *
	 * @param array $args referrer_email, referral_code, referred_order_id,
	 *                    referred_email, reward_coupon, order_total.
	 * @return int|false Inserted id or false.
	 */
	public static function record_conversion( array $args ) {
		global $wpdb;

		$data = array(
			'referrer_email'    => sanitize_email( $args['referrer_email'] ?? '' ),
			'referral_code'     => sanitize_text_field( $args['referral_code'] ?? '' ),
			'referred_order_id' => absint( $args['referred_order_id'] ?? 0 ),
			'referred_email'    => sanitize_email( $args['referred_email'] ?? '' ),
			'reward_coupon'     => sanitize_text_field( $args['reward_coupon'] ?? '' ),
			'reward_status'     => ! empty( $args['reward_coupon'] ) ? 'issued' : 'pending',
			'order_total'       => round( (float) ( $args['order_total'] ?? 0 ), 2 ),
			'created_at'        => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( self::table(), $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * List referral conversions for the admin screen.
	 *
	 * @param int $limit Max rows.
	 * @return array[]
	 */
	public static function list_conversions( $limit = 50 ) {
		global $wpdb;

		$limit = absint( $limit );
		$table = self::table();

		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE referred_order_id > 0 ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Aggregate referral statistics.
	 *
	 * @return array{ambassadors:int,conversions:int,rewards:int,revenue:float}
	 */
	public static function stats() {
		global $wpdb;

		$table = self::table();

		$ambassadors = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE reward_status = %s", self::STATUS_AMBASSADOR ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$conversions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE referred_order_id > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$rewards = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE reward_status = 'issued'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$revenue = (float) $wpdb->get_var( "SELECT SUM(order_total) FROM {$table} WHERE referred_order_id > 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'ambassadors' => $ambassadors,
			'conversions' => $conversions,
			'rewards'     => $rewards,
			'revenue'     => round( $revenue, 2 ),
		);
	}
}
