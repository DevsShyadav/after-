<?php
/**
 * Main plugin orchestrator.
 *
 * @package APG
 */

namespace APG;

use APG\Admin\Admin;
use APG\Ajax\AjaxController;
use APG\Analytics\Analytics;
use APG\Analytics\EventRepository;
use APG\Frontend\Assets as FrontendAssets;
use APG\Frontend\ThankYou;
use APG\Modules\ModuleManager;
use APG\Rest\RestController;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton that bootstraps and holds shared services.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Module manager.
	 *
	 * @var ModuleManager|null
	 */
	private $modules = null;

	/**
	 * Guard against double-boot.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin: wire services and register hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Run any pending schema upgrade.
		add_action( 'plugins_loaded', array( $this, 'maybe_upgrade' ), 30 );

		// Core modules.
		$this->modules = new ModuleManager();
		$this->modules->register_hooks();

		// Frontend.
		( new ThankYou( $this->modules ) )->register();
		( new FrontendAssets() )->register();

		// REST API.
		add_action(
			'rest_api_init',
			static function () {
				( new RestController() )->register_routes();
			}
		);

		// Admin only.
		if ( is_admin() ) {
			( new Admin( $this->modules ) )->register();
			( new AjaxController() )->register();
		}

		// Scheduled maintenance.
		add_action( 'apg_daily_maintenance', array( $this, 'run_maintenance' ) );

		// Plugin row action link.
		add_filter( 'plugin_action_links_' . APG_PLUGIN_BASENAME, array( $this, 'action_links' ) );

		/**
		 * Fires once the plugin has fully booted.
		 *
		 * @param Plugin $plugin Plugin instance.
		 */
		do_action( 'apg_booted', $this );
	}

	/**
	 * Get the module manager.
	 *
	 * @return ModuleManager
	 */
	public function modules() {
		return $this->modules;
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'after-purchase-goldmine', false, dirname( APG_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Run schema upgrades when the stored version is behind.
	 *
	 * @return void
	 */
	public function maybe_upgrade() {
		$stored = get_option( 'apg_version' );

		if ( APG_VERSION === $stored ) {
			return;
		}

		Install\Schema::install();

		// 1.0.1: migrate the legacy indigo accent to the new red default,
		// but only when the merchant has not picked their own color.
		$settings = get_option( 'apg_settings' );
		if ( is_array( $settings ) && isset( $settings['general']['accent'] ) && '#6366f1' === $settings['general']['accent'] ) {
			$settings['general']['accent'] = '#ef4444';
			update_option( 'apg_settings', $settings );
			Support\Options::flush_cache();
		}

		Support\Options::seed_defaults();
		update_option( 'apg_version', APG_VERSION );
	}

	/**
	 * Daily maintenance: prune old events and refresh aggregates.
	 *
	 * @return void
	 */
	public function run_maintenance() {
		EventRepository::prune( 365 );
		Analytics::flush();
	}

	/**
	 * Add a Settings link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$settings = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=apg-dashboard' ) ),
			esc_html__( 'Dashboard', 'after-purchase-goldmine' )
		);

		array_unshift( $links, $settings );

		return $links;
	}
}
