<?php
/**
 * Plugin Name:       After-Purchase Goldmine
 * Plugin URI:        https://example.com/after-purchase-goldmine
 * Description:       Turn the WooCommerce thank-you page into a revenue engine: one-click upsells, referral program, review requests, social sharing, and a next-purchase discount timer — wrapped in a premium SaaS UI.
 * Version:           1.0.2
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Goldmine Labs
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       after-purchase-goldmine
 * Domain Path:       /languages
 * WC requires at least: 6.0
 * WC tested up to:   9.4
 *
 * @package APG
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Plugin constants.
// ---------------------------------------------------------------------------
define( 'APG_VERSION', '1.0.2' );
define( 'APG_PLUGIN_FILE', __FILE__ );
define( 'APG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'APG_MIN_PHP', '7.4' );
define( 'APG_MIN_WC', '6.0' );

// Trial build detection: drop an empty `trial.flag` file in the plugin root
// to turn any copy of the plugin into a self-deleting 24-hour trial.
if ( ! defined( 'APG_TRIAL' ) ) {
	define( 'APG_TRIAL', file_exists( APG_PLUGIN_DIR . 'trial.flag' ) );
}

// Where trial users are sent to upgrade (edit to your landing page URL).
if ( ! defined( 'APG_UPGRADE_URL' ) ) {
	define( 'APG_UPGRADE_URL', 'https://your-landing-page.example' );
}

// ---------------------------------------------------------------------------
// Autoloader.
// ---------------------------------------------------------------------------
require_once APG_PLUGIN_DIR . 'includes/Autoloader.php';
\APG\Autoloader::register();

/**
 * Render an admin notice when an environment requirement is not met.
 *
 * @param string $message The message to display (already translated).
 * @return void
 */
function apg_requirement_notice( $message ) {
	add_action(
		'admin_notices',
		static function () use ( $message ) {
			printf(
				'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'After-Purchase Goldmine:', 'after-purchase-goldmine' ),
				wp_kses_post( $message )
			);
		}
	);
}

/**
 * Verify the runtime environment meets the minimum requirements.
 *
 * @return bool True when the environment is compatible.
 */
function apg_environment_check() {
	if ( version_compare( PHP_VERSION, APG_MIN_PHP, '<' ) ) {
		apg_requirement_notice(
			sprintf(
				/* translators: 1: required PHP version, 2: current PHP version. */
				esc_html__( 'Requires PHP %1$s or higher. You are running %2$s.', 'after-purchase-goldmine' ),
				esc_html( APG_MIN_PHP ),
				esc_html( PHP_VERSION )
			)
		);
		return false;
	}

	return true;
}

// ---------------------------------------------------------------------------
// Declare HPOS (High-Performance Order Storage) compatibility.
// ---------------------------------------------------------------------------
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', APG_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', APG_PLUGIN_FILE, true );
		}
	}
);

// ---------------------------------------------------------------------------
// Activation / Deactivation hooks.
// ---------------------------------------------------------------------------
register_activation_hook( APG_PLUGIN_FILE, array( \APG\Install\Activator::class, 'activate' ) );
register_deactivation_hook( APG_PLUGIN_FILE, array( \APG\Install\Deactivator::class, 'deactivate' ) );

// ---------------------------------------------------------------------------
// Boot the plugin once all plugins are loaded.
// ---------------------------------------------------------------------------
add_action(
	'plugins_loaded',
	static function () {
		if ( ! apg_environment_check() ) {
			return;
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			apg_requirement_notice(
				esc_html__( 'WooCommerce must be installed and active for this plugin to work.', 'after-purchase-goldmine' )
			);
			return;
		}

		\APG\Plugin::instance()->boot();
	},
	20
);
