<?php
/**
 * Admin menus, screens and asset loading.
 *
 * @package APG
 */

namespace APG\Admin;

use APG\Analytics\Analytics;
use APG\Ajax\AjaxController;
use APG\Modules\ModuleManager;
use APG\Modules\Referral\ReferralRepository;
use APG\Modules\Upsell\OfferRepository;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the premium SaaS admin experience.
 */
final class Admin {

	/**
	 * Menu slug for the dashboard page.
	 *
	 * @var string
	 */
	const SLUG = 'apg-dashboard';

	/**
	 * Module manager.
	 *
	 * @var ModuleManager
	 */
	private $manager;

	/**
	 * Page hook suffixes registered by this plugin.
	 *
	 * @var string[]
	 */
	private $hooks = array();

	/**
	 * Constructor.
	 *
	 * @param ModuleManager $manager Module manager.
	 */
	public function __construct( ModuleManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_onboarding' ) );
	}

	/**
	 * Register the admin menu and subpages.
	 *
	 * @return void
	 */
	public function register_menu() {
		$cap = 'manage_woocommerce';

		$this->hooks['dashboard'] = add_menu_page(
			__( 'After-Purchase Goldmine', 'after-purchase-goldmine' ),
			__( 'Goldmine', 'after-purchase-goldmine' ),
			$cap,
			self::SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-chart-line',
			58
		);

		$this->hooks['dashboard'] = add_submenu_page( self::SLUG, __( 'Dashboard', 'after-purchase-goldmine' ), __( 'Dashboard', 'after-purchase-goldmine' ), $cap, self::SLUG, array( $this, 'render_dashboard' ) );
		$this->hooks['modules']   = add_submenu_page( self::SLUG, __( 'Modules', 'after-purchase-goldmine' ), __( 'Modules', 'after-purchase-goldmine' ), $cap, 'apg-modules', array( $this, 'render_modules' ) );
		$this->hooks['offers']    = add_submenu_page( self::SLUG, __( 'Upsell Offers', 'after-purchase-goldmine' ), __( 'Upsell Offers', 'after-purchase-goldmine' ), $cap, 'apg-offers', array( $this, 'render_offers' ) );
		$this->hooks['referrals'] = add_submenu_page( self::SLUG, __( 'Referrals', 'after-purchase-goldmine' ), __( 'Referrals', 'after-purchase-goldmine' ), $cap, 'apg-referrals', array( $this, 'render_referrals' ) );
		$this->hooks['settings']  = add_submenu_page( self::SLUG, __( 'Settings', 'after-purchase-goldmine' ), __( 'Settings', 'after-purchase-goldmine' ), $cap, 'apg-settings', array( $this, 'render_settings' ) );

		// Onboarding is accessible but hidden from the menu.
		$this->hooks['onboarding'] = add_submenu_page( self::SLUG, __( 'Welcome', 'after-purchase-goldmine' ), __( 'Welcome', 'after-purchase-goldmine' ), $cap, 'apg-onboarding', array( $this, 'render_onboarding' ) );
		remove_submenu_page( self::SLUG, 'apg-onboarding' );
	}

	/**
	 * Redirect to onboarding on first activation.
	 *
	 * @return void
	 */
	public function maybe_redirect_onboarding() {
		if ( ! get_transient( 'apg_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'apg_activation_redirect' );

		if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=apg-onboarding' ) );
		exit;
	}

	/**
	 * Determine if the current screen belongs to this plugin.
	 *
	 * @param string $hook Current admin page hook.
	 * @return bool
	 */
	private function is_plugin_screen( $hook ) {
		if ( in_array( $hook, $this->hooks, true ) ) {
			return true;
		}

		// Robust fallback: match our menu pages by their page slug.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		return 0 === strpos( $page, 'apg-' );
	}

	/**
	 * Enqueue admin assets only on plugin screens.
	 *
	 * @param string $hook Current page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->is_plugin_screen( $hook ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'apg-admin',
			APG_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			APG_VERSION
		);

		wp_add_inline_style( 'apg-admin', $this->inline_theme_css() );

		wp_enqueue_script(
			'apg-charts',
			APG_PLUGIN_URL . 'assets/js/charts.js',
			array(),
			APG_VERSION,
			true
		);

		wp_enqueue_script(
			'apg-admin',
			APG_PLUGIN_URL . 'assets/js/admin.js',
			array( 'apg-charts', 'jquery' ),
			APG_VERSION,
			true
		);

		wp_localize_script(
			'apg-admin',
			'apgAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => esc_url_raw( rest_url( 'apg/v1/' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'nonce'     => wp_create_nonce( AjaxController::NONCE ),
				'dashboardUrl' => admin_url( 'admin.php?page=' . self::SLUG ),
				'currency'  => html_entity_decode( get_woocommerce_currency_symbol() ),
				'i18n'      => array(
					'saved'      => __( 'Saved', 'after-purchase-goldmine' ),
					'saving'     => __( 'Saving…', 'after-purchase-goldmine' ),
					'error'      => __( 'Something went wrong.', 'after-purchase-goldmine' ),
					'confirmDel' => __( 'Delete this offer? This cannot be undone.', 'after-purchase-goldmine' ),
					'selectImg'  => __( 'Select image', 'after-purchase-goldmine' ),
					'useImg'     => __( 'Use this image', 'after-purchase-goldmine' ),
				),
			)
		);
	}

	/**
	 * Inline accent variables for the admin UI.
	 *
	 * @return string
	 */
	private function inline_theme_css() {
		$accent = Options::get( 'general', 'accent', '#6366f1' );
		$rgb    = \APG\Support\Helpers::hex_to_rgb( $accent );

		return sprintf( ':root{--apg-accent:%1$s;--apg-accent-rgb:%2$s;}', esc_attr( $accent ), esc_attr( $rgb ) );
	}

	/**
	 * Include an admin template with extracted variables.
	 *
	 * @param string $template Template file name (without extension).
	 * @param array  $args     Variables.
	 * @return void
	 */
	private function view( $template, array $args = array() ) {
		$file = APG_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $args, EXTR_SKIP );
		include $file;
	}

	/**
	 * Build the module metadata list for admin display.
	 *
	 * @return array[]
	 */
	private function module_meta() {
		$order   = (array) Options::get( 'module_order', null, array() );
		$all     = $this->manager->all();
		$ordered = array();

		foreach ( $order as $id ) {
			if ( isset( $all[ $id ] ) ) {
				$ordered[ $id ] = $all[ $id ];
			}
		}
		foreach ( $all as $id => $module ) {
			if ( ! isset( $ordered[ $id ] ) ) {
				$ordered[ $id ] = $module;
			}
		}

		$meta = array();
		foreach ( $ordered as $id => $module ) {
			$meta[] = array(
				'id'          => $id,
				'title'       => $module->title(),
				'description' => $module->description(),
				'icon'        => $module->icon(),
				'enabled'     => $module->is_enabled(),
			);
		}

		return $meta;
	}

	/**
	 * Render the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$this->view(
			'dashboard',
			array(
				'stats'    => Analytics::dashboard( 30 ),
				'modules'  => $this->module_meta(),
				'settings' => Options::all(),
			)
		);
	}

	/**
	 * Render the modules page.
	 *
	 * @return void
	 */
	public function render_modules() {
		$this->view(
			'modules',
			array(
				'modules'  => $this->module_meta(),
				'settings' => Options::all(),
			)
		);
	}

	/**
	 * Render the offers page.
	 *
	 * @return void
	 */
	public function render_offers() {
		$this->view(
			'offers',
			array(
				'offers'   => OfferRepository::all(),
				'settings' => Options::all(),
			)
		);
	}

	/**
	 * Render the referrals page.
	 *
	 * @return void
	 */
	public function render_referrals() {
		$this->view(
			'referrals',
			array(
				'referrals' => ReferralRepository::list_conversions( 100 ),
				'stats'     => ReferralRepository::stats(),
				'settings'  => Options::all(),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings() {
		$this->view(
			'settings',
			array(
				'settings' => Options::all(),
			)
		);
	}

	/**
	 * Render the onboarding wizard.
	 *
	 * @return void
	 */
	public function render_onboarding() {
		$this->view(
			'onboarding',
			array(
				'settings' => Options::all(),
				'modules'  => $this->module_meta(),
			)
		);
	}
}
