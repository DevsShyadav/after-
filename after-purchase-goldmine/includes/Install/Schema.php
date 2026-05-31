<?php
/**
 * Database schema definitions and table name helpers.
 *
 * @package APG
 */

namespace APG\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Central authority for custom database table names and their schema.
 */
final class Schema {

	/**
	 * Get the prefixed table name for a logical table.
	 *
	 * @param string $name Logical table name (offers|events|referrals|coupons).
	 * @return string Fully prefixed table name.
	 */
	public static function table( $name ) {
		global $wpdb;

		return $wpdb->prefix . 'apg_' . $name;
	}

	/**
	 * Create or update all custom tables via dbDelta.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$offers    = self::table( 'offers' );
		$events    = self::table( 'events' );
		$referrals = self::table( 'referrals' );
		$coupons   = self::table( 'coupons' );

		$sql = array();

		$sql[] = "CREATE TABLE {$offers} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(191) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			trigger_type VARCHAR(20) NOT NULL DEFAULT 'any',
			trigger_ids LONGTEXT NULL,
			product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			discount_type VARCHAR(20) NOT NULL DEFAULT 'percent',
			discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			headline VARCHAR(191) NOT NULL DEFAULT '',
			description TEXT NULL,
			image_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			priority INT(11) NOT NULL DEFAULT 0,
			impressions BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			conversions BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
			created_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY priority (priority)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$events} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			module VARCHAR(40) NOT NULL DEFAULT '',
			event_type VARCHAR(20) NOT NULL DEFAULT '',
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			offer_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			revenue DECIMAL(14,2) NOT NULL DEFAULT 0,
			meta LONGTEXT NULL,
			created_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY module_type (module, event_type),
			KEY created_at (created_at),
			KEY order_id (order_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$referrals} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			referrer_email VARCHAR(191) NOT NULL DEFAULT '',
			referral_code VARCHAR(64) NOT NULL DEFAULT '',
			referred_order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			referred_email VARCHAR(191) NOT NULL DEFAULT '',
			reward_coupon VARCHAR(64) NOT NULL DEFAULT '',
			reward_status VARCHAR(20) NOT NULL DEFAULT 'pending',
			order_total DECIMAL(14,2) NOT NULL DEFAULT 0,
			created_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY referral_code (referral_code),
			KEY referrer_email (referrer_email),
			KEY reward_status (reward_status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$coupons} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			code VARCHAR(64) NOT NULL DEFAULT '',
			source VARCHAR(40) NOT NULL DEFAULT 'discount_timer',
			order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			customer_email VARCHAR(191) NOT NULL DEFAULT '',
			discount_type VARCHAR(20) NOT NULL DEFAULT 'percent',
			discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
			expires_at DATETIME NULL,
			used TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY code (code),
			KEY order_id (order_id),
			KEY customer_email (customer_email)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/**
	 * Drop all custom tables. Used only on opt-in uninstall.
	 *
	 * @return void
	 */
	public static function drop() {
		global $wpdb;

		$tables = array(
			self::table( 'offers' ),
			self::table( 'events' ),
			self::table( 'referrals' ),
			self::table( 'coupons' ),
		);

		foreach ( $tables as $table ) {
			// Table name is built from a hard-coded whitelist, safe to interpolate.
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
