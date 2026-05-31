<?php
/**
 * 24-hour trial enforcement.
 *
 * Active only in the trial build (when a `trial.flag` file is present in the
 * plugin root, which defines the APG_TRIAL constant). Shows a live countdown
 * banner and cleanly self-deletes the plugin when the 24-hour window ends.
 *
 * @package APG
 */

namespace APG\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Enforces the time-limited trial.
 */
final class TrialGuard {

	/**
	 * Option storing the trial start timestamp.
	 *
	 * @var string
	 */
	const OPT_START = 'apg_trial_started';

	/**
	 * Trial duration in seconds (24 hours).
	 *
	 * @var int
	 */
	const DURATION = 86400;

	/**
	 * Record the trial start time (idempotent).
	 *
	 * @return void
	 */
	public static function start() {
		if ( ! get_option( self::OPT_START ) ) {
			add_option( self::OPT_START, time() );
		}
	}

	/**
	 * Register trial hooks.
	 *
	 * @return void
	 */
	public function register() {
		self::start();
		add_action( 'admin_init', array( $this, 'enforce' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Seconds remaining in the trial (0 when expired).
	 *
	 * @return int
	 */
	public function remaining() {
		$start = (int) get_option( self::OPT_START, 0 );

		if ( ! $start ) {
			$start = time();
			update_option( self::OPT_START, $start );
		}

		return max( 0, ( $start + self::DURATION ) - time() );
	}

	/**
	 * The upgrade URL (filterable).
	 *
	 * @return string
	 */
	private function upgrade_url() {
		$url = defined( 'APG_UPGRADE_URL' ) ? APG_UPGRADE_URL : '';

		/**
		 * Filter the trial upgrade URL shown in the admin banner.
		 *
		 * @param string $url Upgrade/landing page URL.
		 */
		return apply_filters( 'apg_upgrade_url', $url );
	}

	/**
	 * When the trial has expired, deactivate and delete the plugin.
	 *
	 * @return void
	 */
	public function enforce() {
		if ( $this->remaining() > 0 ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Deactivate first so nothing keeps running.
		deactivate_plugins( APG_PLUGIN_BASENAME, true );

		// Clean our own options/data.
		self::cleanup_data();

		// Attempt to delete the plugin files entirely.
		$deleted = false;
		if ( function_exists( 'delete_plugins' ) ) {
			$result  = delete_plugins( array( APG_PLUGIN_BASENAME ) );
			$deleted = ( true === $result );
		}

		set_transient( 'apg_trial_expired', $deleted ? 'deleted' : 'deactivated', 120 );

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Remove trial data on expiry.
	 *
	 * @return void
	 */
	private static function cleanup_data() {
		delete_option( self::OPT_START );
		delete_option( 'apg_settings' );
		delete_option( 'apg_version' );
		delete_option( 'apg_onboarding_complete' );
		delete_transient( 'apg_activation_redirect' );
		delete_transient( 'apg_dashboard_stats' );

		if ( class_exists( Schema::class ) ) {
			Schema::drop();
		}
	}

	/**
	 * Render the trial countdown banner (and the expiry message).
	 *
	 * @return void
	 */
	public function notice() {
		$expired = get_transient( 'apg_trial_expired' );
		if ( $expired ) {
			delete_transient( 'apg_trial_expired' );
			$msg = ( 'deleted' === $expired )
				? __( 'Your After-Purchase Goldmine trial has ended and the plugin was removed. Upgrade to Pro to continue.', 'after-purchase-goldmine' )
				: __( 'Your After-Purchase Goldmine trial has ended and the plugin was deactivated. You can delete it or upgrade to Pro.', 'after-purchase-goldmine' );
			printf( '<div class="notice notice-warning"><p><strong>%s</strong></p></div>', esc_html( $msg ) );
			return;
		}

		$remaining = $this->remaining();
		if ( $remaining <= 0 ) {
			return;
		}

		$hours   = floor( $remaining / 3600 );
		$minutes = floor( ( $remaining % 3600 ) / 60 );
		$url     = $this->upgrade_url();

		$countdown = sprintf(
			/* translators: 1: hours, 2: minutes. */
			__( '%1$dh %2$dm left', 'after-purchase-goldmine' ),
			$hours,
			$minutes
		);

		$button = '';
		if ( $url ) {
			$button = sprintf(
				' <a href="%s" target="_blank" rel="noopener" style="margin-left:8px;font-weight:600;">%s</a>',
				esc_url( $url ),
				esc_html__( 'Upgrade to Pro →', 'after-purchase-goldmine' )
			);
		}

		printf(
			'<div class="notice notice-info" style="border-left-color:#10b981;"><p><strong>%s</strong> %s%s</p></div>',
			esc_html__( 'After-Purchase Goldmine — Trial', 'after-purchase-goldmine' ),
			esc_html(
				sprintf(
					/* translators: %s: time remaining. */
					__( 'You are on the 24-hour trial (%s). The plugin will remove itself when the trial ends.', 'after-purchase-goldmine' ),
					$countdown
				)
			),
			wp_kses_post( $button )
		);
	}
}
