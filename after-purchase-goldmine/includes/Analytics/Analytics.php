<?php
/**
 * Dashboard analytics aggregation with caching.
 *
 * @package APG
 */

namespace APG\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Builds cached aggregate datasets for the admin dashboard.
 */
final class Analytics {

	/**
	 * Transient key for the cached dashboard payload.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'apg_dashboard_stats';

	/**
	 * Build the full dashboard dataset (cached for 5 minutes).
	 *
	 * @param int  $days  Range in days.
	 * @param bool $fresh Bypass cache when true.
	 * @return array
	 */
	public static function dashboard( $days = 30, $fresh = false ) {
		$days  = max( 1, min( 90, (int) $days ) );
		$cache = get_transient( self::CACHE_KEY );

		if ( ! $fresh && is_array( $cache ) && isset( $cache['range'] ) && (int) $cache['range'] === $days ) {
			return $cache;
		}

		$since   = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days', current_time( 'timestamp' ) ) );
		$totals  = EventRepository::totals_by_module( $since );
		$series  = EventRepository::daily_series( min( $days, 30 ) );
		$revenue = EventRepository::total_revenue( $since );

		$impressions = 0;
		$conversions = 0;

		foreach ( $totals as $mod ) {
			$impressions += (int) $mod['impression'];
			$conversions += (int) $mod['conversion'];
		}

		$conv_rate = $impressions > 0 ? round( ( $conversions / $impressions ) * 100, 1 ) : 0.0;

		$payload = array(
			'range'       => $days,
			'revenue'     => $revenue,
			'impressions' => $impressions,
			'conversions' => $conversions,
			'conv_rate'   => $conv_rate,
			'by_module'   => $totals,
			'series'      => $series,
			'generated'   => current_time( 'mysql' ),
		);

		set_transient( self::CACHE_KEY, $payload, 5 * MINUTE_IN_SECONDS );

		return $payload;
	}

	/**
	 * Invalidate the cached dashboard payload.
	 *
	 * @return void
	 */
	public static function flush() {
		delete_transient( self::CACHE_KEY );
	}
}
