<?php
/**
 * Registers and resolves thank-you modules.
 *
 * @package APG
 */

namespace APG\Modules;

use APG\Modules\Upsell\UpsellModule;
use APG\Modules\DiscountTimer\DiscountTimerModule;
use APG\Modules\Referral\ReferralModule;
use APG\Modules\Review\ReviewModule;
use APG\Modules\SocialShare\SocialShareModule;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the lifecycle of all modules.
 */
final class ModuleManager {

	/**
	 * Registered modules keyed by id.
	 *
	 * @var AbstractModule[]
	 */
	private $modules = array();

	/**
	 * Construct and instantiate all bundled modules.
	 */
	public function __construct() {
		$defaults = array(
			new UpsellModule(),
			new DiscountTimerModule(),
			new ReferralModule(),
			new ReviewModule(),
			new SocialShareModule(),
		);

		/**
		 * Filter the list of module instances.
		 *
		 * @param AbstractModule[] $defaults Default module instances.
		 */
		$modules = apply_filters( 'apg_modules', $defaults );

		foreach ( $modules as $module ) {
			if ( $module instanceof AbstractModule ) {
				$this->modules[ $module->id() ] = $module;
			}
		}
	}

	/**
	 * Register module hooks for all modules (enabled gating is per-module).
	 *
	 * @return void
	 */
	public function register_hooks() {
		foreach ( $this->modules as $module ) {
			$module->register();
		}
	}

	/**
	 * Get a single module by id.
	 *
	 * @param string $id Module id.
	 * @return AbstractModule|null
	 */
	public function get( $id ) {
		return isset( $this->modules[ $id ] ) ? $this->modules[ $id ] : null;
	}

	/**
	 * Get all module instances keyed by id.
	 *
	 * @return AbstractModule[]
	 */
	public function all() {
		return $this->modules;
	}

	/**
	 * Get enabled modules in the admin-defined display order.
	 *
	 * @return AbstractModule[]
	 */
	public function enabled_ordered() {
		$order  = (array) Options::get( 'module_order', null, array() );
		$result = array();

		foreach ( $order as $id ) {
			if ( isset( $this->modules[ $id ] ) && $this->modules[ $id ]->is_enabled() ) {
				$result[] = $this->modules[ $id ];
			}
		}

		// Include any enabled modules missing from the order list.
		foreach ( $this->modules as $id => $module ) {
			if ( $module->is_enabled() && ! in_array( $module, $result, true ) ) {
				$result[] = $module;
			}
		}

		return $result;
	}
}
