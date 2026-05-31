<?php
/**
 * Plugin activation routines.
 *
 * @package APG
 */

namespace APG\Install;

use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Handles everything that must happen when the plugin is activated.
 */
final class Activator {

	/**
	 * Run activation tasks: create tables, seed settings, schedule events.
	 *
	 * @return void
	 */
	public static function activate() {
		Schema::install();
		Options::seed_defaults();

		// Flag the onboarding wizard for first-time setup.
		if ( false === get_option( 'apg_onboarding_complete', false ) ) {
			add_option( 'apg_onboarding_complete', false );
			set_transient( 'apg_activation_redirect', 1, 60 );
		}

		update_option( 'apg_version', APG_VERSION );

		// In the trial build, start the 24-hour countdown.
		if ( defined( 'APG_TRIAL' ) && APG_TRIAL ) {
			TrialGuard::start();
		}

		// Schedule the daily maintenance task (event pruning / aggregation).
		if ( ! wp_next_scheduled( 'apg_daily_maintenance' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'apg_daily_maintenance' );
		}

		flush_rewrite_rules();
	}
}
