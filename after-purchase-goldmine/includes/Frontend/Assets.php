<?php
/**
 * Frontend asset registration & data localization.
 *
 * @package APG
 */

namespace APG\Frontend;

use APG\Rest\RestController;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues frontend CSS/JS only where needed and injects theme variables.
 */
final class Assets {

	/**
	 * Register the enqueue hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Whether we are on an order-received (thank-you) screen.
	 *
	 * @return bool
	 */
	private function is_thankyou() {
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return true;
		}

		return is_wc_endpoint_url( 'order-received' );
	}

	/**
	 * Enqueue and localize assets.
	 *
	 * @return void
	 */
	public function enqueue() {
		if ( ! $this->is_thankyou() ) {
			return;
		}

		$order = $this->current_order();

		if ( ! $order ) {
			return;
		}

		wp_enqueue_style(
			'apg-frontend',
			APG_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			APG_VERSION
		);

		wp_add_inline_style( 'apg-frontend', $this->inline_theme_css() );

		wp_enqueue_script(
			'apg-frontend',
			APG_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			APG_VERSION,
			true
		);

		wp_localize_script(
			'apg-frontend',
			'apgFrontend',
			array(
				'restUrl'   => esc_url_raw( rest_url( RestController::NAMESPACE . '/' ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'orderId'   => $order->get_id(),
				'orderKey'  => $order->get_order_key(),
				'theme'     => Options::get( 'general', 'theme', 'auto' ),
				'animations' => (bool) Options::get( 'general', 'animations', true ),
				'i18n'      => array(
					'copied'     => __( 'Copied!', 'after-purchase-goldmine' ),
					'copy'       => __( 'Copy', 'after-purchase-goldmine' ),
					'processing' => __( 'Processing…', 'after-purchase-goldmine' ),
					'added'      => Options::get( 'upsell', 'success_label' ),
					'error'      => __( 'Something went wrong. Please try again.', 'after-purchase-goldmine' ),
					'expired'    => __( 'Offer expired', 'after-purchase-goldmine' ),
					'days'       => __( 'd', 'after-purchase-goldmine' ),
					'hours'      => __( 'h', 'after-purchase-goldmine' ),
					'minutes'    => __( 'm', 'after-purchase-goldmine' ),
					'seconds'    => __( 's', 'after-purchase-goldmine' ),
				),
			)
		);
	}

	/**
	 * Resolve the current order from the endpoint URL.
	 *
	 * @return \WC_Order|null
	 */
	private function current_order() {
		global $wp;

		$order_id = 0;

		if ( ! empty( $wp->query_vars['order-received'] ) ) {
			$order_id = absint( $wp->query_vars['order-received'] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $order_id && isset( $_GET['order'] ) ) {
			$order_id = absint( $_GET['order'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Build inline CSS variables from settings.
	 *
	 * @return string
	 */
	private function inline_theme_css() {
		$accent = Options::get( 'general', 'accent', '#10b981' );
		$radius = (int) Options::get( 'general', 'card_radius', 20 );
		$rgb    = Helpers::hex_to_rgb( $accent );
		$strong = Helpers::darken( $accent, 0.18 );

		return sprintf(
			'.apg-goldmine{--apg-accent:%1$s;--apg-accent-rgb:%2$s;--apg-accent-strong:%3$s;--apg-radius:%4$dpx;}',
			esc_attr( $accent ),
			esc_attr( $rgb ),
			esc_attr( $strong ),
			$radius
		);
	}
}
