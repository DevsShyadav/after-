<?php
/**
 * Renders enabled modules on the WooCommerce thank-you page.
 *
 * @package APG
 */

namespace APG\Frontend;

use APG\Analytics\EventRepository;
use APG\Modules\ModuleManager;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into `woocommerce_thankyou` and composes the module cards.
 */
final class ThankYou {

	/**
	 * Module manager instance.
	 *
	 * @var ModuleManager
	 */
	private $manager;

	/**
	 * Whether the renderer already ran for this request.
	 *
	 * @var bool
	 */
	private $rendered = false;

	/**
	 * Constructor.
	 *
	 * @param ModuleManager $manager Module manager.
	 */
	public function __construct( ModuleManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register the thank-you hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_thankyou', array( $this, 'render' ), 8, 1 );
	}

	/**
	 * Render the module cards for an order.
	 *
	 * @param int $order_id Order id.
	 * @return void
	 */
	public function render( $order_id ) {
		if ( $this->rendered ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$modules = $this->manager->enabled_ordered();

		if ( empty( $modules ) ) {
			return;
		}

		$cards = '';

		foreach ( $modules as $module ) {
			if ( ! $module->should_render( $order ) ) {
				continue;
			}

			$html = $module->render( $order );

			if ( '' === trim( (string) $html ) ) {
				continue;
			}

			// Record a de-duplicated impression for accurate analytics.
			if ( ! EventRepository::impression_exists( $module->id(), $order_id ) ) {
				EventRepository::record( $module->id(), 'impression', array( 'order_id' => $order_id ) );
			}

			$cards .= sprintf(
				'<div class="apg-card-slot" data-module="%s">%s</div>',
				esc_attr( $module->id() ),
				$html
			);
		}

		if ( '' === trim( $cards ) ) {
			return;
		}

		$this->rendered = true;

		echo Helpers::get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'thankyou/wrapper.php',
			array(
				'order' => $order,
				'cards' => $cards,
				'title' => Helpers::merge_tags( Options::get( 'general', 'title' ), array( 'first_name' => Helpers::first_name( $order ) ) ),
			)
		);
	}
}
