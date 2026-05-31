<?php
/**
 * REST API controller for frontend (thank-you page) interactions.
 *
 * @package APG
 */

namespace APG\Rest;

use APG\Analytics\Analytics;
use APG\Analytics\EventRepository;
use APG\Modules\Upsell\OfferRepository;
use APG\Modules\Upsell\OrderProcessor;
use APG\Support\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and handles all `apg/v1` REST routes.
 */
final class RestController {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'apg/v1';

	/**
	 * Register all routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/event',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'track_event' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'module'    => array( 'required' => true, 'type' => 'string' ),
					'type'      => array( 'required' => true, 'type' => 'string' ),
					'order_id'  => array( 'required' => false, 'type' => 'integer' ),
					'order_key' => array( 'required' => false, 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/upsell/accept',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'accept_upsell' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'  => array( 'required' => true, 'type' => 'integer' ),
					'order_key' => array( 'required' => true, 'type' => 'string' ),
					'offer_id'  => array( 'required' => true, 'type' => 'integer' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'range' => array( 'required' => false, 'type' => 'integer', 'default' => 30 ),
				),
			)
		);
	}

	/**
	 * Permission callback for admin-only routes.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Record a lightweight analytics event from the frontend.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function track_event( WP_REST_Request $request ) {
		$module = sanitize_key( (string) $request->get_param( 'module' ) );
		$type   = sanitize_key( (string) $request->get_param( 'type' ) );

		if ( ! in_array( $type, array( 'click', 'impression' ), true ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 200 );
		}

		$order_id  = absint( $request->get_param( 'order_id' ) );
		$order_key = (string) $request->get_param( 'order_key' );

		// If an order is referenced, it must be authentic to be counted.
		if ( $order_id && ! Helpers::verify_order_access( $order_id, $order_key ) ) {
			return new WP_REST_Response( array( 'ok' => false ), 200 );
		}

		EventRepository::record(
			$module,
			$type,
			array(
				'order_id' => $order_id,
				'meta'     => array( 'ua' => substr( (string) $request->get_header( 'user_agent' ), 0, 120 ) ),
			)
		);

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	/**
	 * Accept the one-click upsell offer.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function accept_upsell( WP_REST_Request $request ) {
		$order_id  = absint( $request->get_param( 'order_id' ) );
		$order_key = (string) $request->get_param( 'order_key' );
		$offer_id  = absint( $request->get_param( 'offer_id' ) );

		$order = Helpers::verify_order_access( $order_id, $order_key );

		if ( ! $order ) {
			return new WP_Error( 'apg_forbidden', __( 'This action is not allowed.', 'after-purchase-goldmine' ), array( 'status' => 403 ) );
		}

		$offer = OfferRepository::get( $offer_id );

		if ( empty( $offer ) || 'active' !== $offer['status'] ) {
			return new WP_Error( 'apg_no_offer', __( 'This offer is no longer available.', 'after-purchase-goldmine' ), array( 'status' => 404 ) );
		}

		// Re-validate the offer matches this order to prevent tampering.
		$valid = OfferRepository::find_for_order( $order );

		if ( empty( $valid ) || (int) $valid['id'] !== (int) $offer['id'] ) {
			return new WP_Error( 'apg_no_offer', __( 'This offer is no longer available.', 'after-purchase-goldmine' ), array( 'status' => 409 ) );
		}

		$processor = new OrderProcessor();
		$result    = $processor->process( $order, $offer );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
		}

		EventRepository::record(
			'upsell',
			'conversion',
			array(
				'order_id' => $order_id,
				'offer_id' => $offer_id,
				'revenue'  => $result['amount'],
			)
		);

		Analytics::flush();

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Return the dashboard analytics payload.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_analytics( WP_REST_Request $request ) {
		$range = absint( $request->get_param( 'range' ) );
		$range = $range ? $range : 30;

		return new WP_REST_Response( Analytics::dashboard( $range, true ), 200 );
	}
}
