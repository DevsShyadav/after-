<?php
/**
 * Plugin deactivation routines.
 *
 * @package APG
 */

namespace APG\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Handles cleanup that must run on deactivation (non-destructive).
 */
final class Deactivator {

	/**
	 * Clear scheduled events and flush caches. Data is preserved.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'apg_daily_maintenance' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'apg_daily_maintenance' );
		}

		wp_clear_scheduled_hook( 'apg_daily_maintenance' );

		delete_transient( 'apg_activation_redirect' );
		delete_transient( 'apg_dashboard_stats' );

		flush_rewrite_rules();
	}
}
