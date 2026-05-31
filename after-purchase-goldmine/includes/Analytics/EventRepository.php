<?php
/**
 * Persistence layer for analytics events.
 *
 * @package APG
 */

namespace APG\Analytics;

use APG\Install\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Records and queries module events (impressions, clicks, conversions).
 */
final class EventRepository {

	/**
	 * Allowed event types.
	 *
	 * @var string[]
	 */
	const TYPES = array( 'impression', 'click', 'conversion' );

	/**
	 * Allowed module identifiers.
	 *
	 * @var string[]
	 */
	const MODULES = array( 'upsell', 'discount_timer', 'referral', 'review', 'social_share' );

	/**
	 * Record a single event.
	 *
	 * @param string $module   Module id.
	 * @param string $type     Event type.
	 * @param array  $args     Optional: order_id, offer_id, revenue, meta.
	 * @return int|false Inserted row id or false on failure.
	 */
	public static function record( $module, $type, array $args = array() ) {
		global $wpdb;

		$module = sanitize_key( $module );
		$type   = sanitize_key( $type );

		if ( ! in_array( $module, self::MODULES, true ) || ! in_array( $type, self::TYPES, true ) ) {
			return false;
		}

		$data = array(
			'module'     => $module,
			'event_type' => $type,
			'order_id'   => isset( $args['order_id'] ) ? absint( $args['order_id'] ) : 0,
			'offer_id'   => isset( $args['offer_id'] ) ? absint( $args['offer_id'] ) : 0,
			'revenue'    => isset( $args['revenue'] ) ? round( (float) $args['revenue'], 2 ) : 0,
			'meta'       => isset( $args['meta'] ) ? wp_json_encode( $args['meta'] ) : null,
			'created_at' => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%d', '%d', '%f', '%s', '%s' );

		$result = $wpdb->insert( Schema::table( 'events' ), $data, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Prevent duplicate impression events for the same order+module within a window.
	 *
	 * @param string $module   Module id.
	 * @param int    $order_id Order id.
	 * @return bool True if an impression already exists.
	 */
	public static function impression_exists( $module, $order_id ) {
		global $wpdb;

		$module   = sanitize_key( $module );
		$order_id = absint( $order_id );

		if ( ! $order_id ) {
			return false;
		}

		$table = Schema::table( 'events' );

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE module = %s AND event_type = %s AND order_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$module,
				'impression',
				$order_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Aggregate totals grouped by module within a date range.
	 *
	 * @param string $since SQL datetime lower bound (inclusive).
	 * @return array Keyed by module => [impression, click, conversion, revenue].
	 */
	public static function totals_by_module( $since ) {
		global $wpdb;

		$table = Schema::table( 'events' );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT module, event_type, COUNT(*) AS total, SUM(revenue) AS revenue
				FROM {$table} WHERE created_at >= %s GROUP BY module, event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$since
			),
			ARRAY_A
		);

		$out = array();

		foreach ( self::MODULES as $mod ) {
			$out[ $mod ] = array(
				'impression' => 0,
				'click'      => 0,
				'conversion' => 0,
				'revenue'    => 0.0,
			);
		}

		foreach ( (array) $rows as $row ) {
			$mod  = $row['module'];
			$type = $row['event_type'];
			if ( ! isset( $out[ $mod ][ $type ] ) ) {
				continue;
			}
			$out[ $mod ][ $type ] = (int) $row['total'];
			$out[ $mod ]['revenue'] += (float) $row['revenue'];
		}

		return $out;
	}

	/**
	 * Daily revenue + conversion series for charting.
	 *
	 * @param int $days Number of days back from today.
	 * @return array{labels:string[],revenue:float[],conversions:int[]}
	 */
	public static function daily_series( $days = 14 ) {
		global $wpdb;

		$days  = max( 1, min( 90, (int) $days ) );
		$table = Schema::table( 'events' );
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( '-' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT DATE(created_at) AS day,
					SUM(revenue) AS revenue,
					SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) AS conversions
				FROM {$table} WHERE created_at >= %s GROUP BY DATE(created_at)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$since
			),
			OBJECT_K
		);

		$labels      = array();
		$revenue     = array();
		$conversions = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$ts  = strtotime( '-' . $i . ' days', current_time( 'timestamp' ) );
			$key = gmdate( 'Y-m-d', $ts );

			$labels[]      = date_i18n( 'M j', $ts );
			$revenue[]     = isset( $rows[ $key ] ) ? round( (float) $rows[ $key ]->revenue, 2 ) : 0.0;
			$conversions[] = isset( $rows[ $key ] ) ? (int) $rows[ $key ]->conversions : 0;
		}

		return array(
			'labels'      => $labels,
			'revenue'     => $revenue,
			'conversions' => $conversions,
		);
	}

	/**
	 * Sum of all conversion revenue since a date.
	 *
	 * @param string $since SQL datetime lower bound.
	 * @return float
	 */
	public static function total_revenue( $since ) {
		global $wpdb;

		$table = Schema::table( 'events' );

		$value = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"SELECT SUM(revenue) FROM {$table} WHERE created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$since
			)
		);

		return round( (float) $value, 2 );
	}

	/**
	 * Delete events older than a retention threshold.
	 *
	 * @param int $days Retention window in days.
	 * @return int Rows deleted.
	 */
	public static function prune( $days = 365 ) {
		global $wpdb;

		$days   = max( 30, (int) $days );
		$table  = Schema::table( 'events' );
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days', current_time( 'timestamp' ) ) );

		return (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff
			)
		);
	}
}
