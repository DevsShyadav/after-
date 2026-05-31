<?php
/**
 * Admin-side AJAX controller.
 *
 * @package APG
 */

namespace APG\Ajax;

use APG\Analytics\Analytics;
use APG\Modules\Upsell\OfferRepository;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Handles authenticated admin AJAX actions for the SaaS UI.
 */
final class AjaxController {

	/**
	 * Nonce action name.
	 *
	 * @var string
	 */
	const NONCE = 'apg_admin';

	/**
	 * Register all AJAX handlers.
	 *
	 * @return void
	 */
	public function register() {
		$actions = array(
			'apg_save_settings'      => 'save_settings',
			'apg_save_offer'         => 'save_offer',
			'apg_delete_offer'       => 'delete_offer',
			'apg_toggle_module'      => 'toggle_module',
			'apg_reorder_modules'    => 'reorder_modules',
			'apg_complete_onboarding' => 'complete_onboarding',
			'apg_search_products'    => 'search_products',
		);

		foreach ( $actions as $hook => $method ) {
			add_action( 'wp_ajax_' . $hook, array( $this, $method ) );
		}
	}

	/**
	 * Verify nonce + capability for every request. Dies on failure.
	 *
	 * @return void
	 */
	private function guard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'after-purchase-goldmine' ) ), 403 );
		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Please refresh and try again.', 'after-purchase-goldmine' ) ), 400 );
		}
	}

	/**
	 * Decode a JSON payload field into an array.
	 *
	 * @param string $field Request field name.
	 * @return array
	 */
	private function payload( $field = 'payload' ) {
		if ( empty( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return array();
		}

		$raw     = wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded = json_decode( (string) $raw, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Persist plugin settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		$this->guard();

		$data   = $this->payload();
		$stored = Options::update( $data );
		Analytics::flush();

		wp_send_json_success(
			array(
				'message'  => __( 'Settings saved.', 'after-purchase-goldmine' ),
				'settings' => $stored,
			)
		);
	}

	/**
	 * Create or update an upsell offer.
	 *
	 * @return void
	 */
	public function save_offer() {
		$this->guard();

		$data = $this->payload();
		$id   = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		if ( empty( $data['product_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a product to offer.', 'after-purchase-goldmine' ) ) );
		}

		if ( $id ) {
			OfferRepository::update( $id, $data );
		} else {
			$id = OfferRepository::create( $data );
		}

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Could not save the offer.', 'after-purchase-goldmine' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Offer saved.', 'after-purchase-goldmine' ),
				'offer'   => OfferRepository::get( $id ),
			)
		);
	}

	/**
	 * Delete an upsell offer.
	 *
	 * @return void
	 */
	public function delete_offer() {
		$this->guard();

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $id || ! OfferRepository::delete( $id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete the offer.', 'after-purchase-goldmine' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Offer deleted.', 'after-purchase-goldmine' ) ) );
	}

	/**
	 * Toggle a module on or off.
	 *
	 * @return void
	 */
	public function toggle_module() {
		$this->guard();

		$module  = isset( $_POST['module'] ) ? sanitize_key( wp_unslash( $_POST['module'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled = isset( $_POST['enabled'] ) && 'true' === $_POST['enabled']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$modules = (array) Options::get( 'modules' );

		if ( ! array_key_exists( $module, $modules ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown module.', 'after-purchase-goldmine' ) ) );
		}

		$modules[ $module ] = $enabled;
		Options::update( array( 'modules' => $modules ) );

		wp_send_json_success( array( 'message' => __( 'Module updated.', 'after-purchase-goldmine' ) ) );
	}

	/**
	 * Reorder modules.
	 *
	 * @return void
	 */
	public function reorder_modules() {
		$this->guard();

		$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order = array_map( 'sanitize_key', $order );

		Options::update( array( 'module_order' => $order ) );

		wp_send_json_success( array( 'message' => __( 'Order saved.', 'after-purchase-goldmine' ) ) );
	}

	/**
	 * Mark onboarding complete and persist initial settings.
	 *
	 * @return void
	 */
	public function complete_onboarding() {
		$this->guard();

		$data = $this->payload();

		if ( ! empty( $data ) ) {
			Options::update( $data );
		}

		update_option( 'apg_onboarding_complete', true );
		delete_transient( 'apg_activation_redirect' );

		wp_send_json_success( array( 'message' => __( 'You are all set!', 'after-purchase-goldmine' ) ) );
	}

	/**
	 * Lightweight product search for offer selection.
	 *
	 * @return void
	 */
	public function search_products() {
		$this->guard();

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$args = array(
			'status' => 'publish',
			'limit'  => 20,
			'return' => 'objects',
		);

		if ( '' !== $term ) {
			$args['s'] = $term;
		}

		$products = wc_get_products( $args );
		$results  = array();

		foreach ( $products as $product ) {
			$results[] = array(
				'id'    => $product->get_id(),
				'text'  => sprintf( '%s (#%d)', $product->get_name(), $product->get_id() ),
				'price' => wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ),
			);
		}

		wp_send_json_success( array( 'products' => $results ) );
	}
}
