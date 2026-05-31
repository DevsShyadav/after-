<?php
/**
 * Base class shared by every thank-you module.
 *
 * @package APG
 */

namespace APG\Modules;

use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the contract and shared behavior for thank-you modules.
 */
abstract class AbstractModule {

	/**
	 * Unique module identifier (matches settings keys).
	 *
	 * @return string
	 */
	abstract public function id();

	/**
	 * Human readable module title for the admin UI.
	 *
	 * @return string
	 */
	abstract public function title();

	/**
	 * Short admin description.
	 *
	 * @return string
	 */
	abstract public function description();

	/**
	 * Inline SVG icon markup for the admin UI.
	 *
	 * @return string
	 */
	abstract public function icon();

	/**
	 * Render the module's HTML for the thank-you page.
	 *
	 * @param \WC_Order $order The current order.
	 * @return string HTML output (empty string to render nothing).
	 */
	abstract public function render( $order );

	/**
	 * Register module-specific WordPress/WooCommerce hooks.
	 *
	 * Called once during boot regardless of thank-you rendering so that
	 * background behaviors (emails, order hooks) always run.
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Whether the module is enabled in settings.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return Options::module_enabled( $this->id() );
	}

	/**
	 * Per-order render gate. Override for module-specific conditions.
	 *
	 * @param \WC_Order $order The current order.
	 * @return bool
	 */
	public function should_render( $order ) {
		return $order instanceof \WC_Order;
	}
}
