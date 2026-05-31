<?php
/**
 * Creates and (where possible) charges the one-click upsell order.
 *
 * @package APG
 */

namespace APG\Modules\Upsell;

use APG\Support\Logger;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a child order from the parent order and processes payment.
 *
 * Payment strategy:
 *  1. If a saved payment token exists for the parent order's gateway and a
 *     registered handler can charge it off-session (filter
 *     `apg_offsession_charge`), the order is paid instantly (true zero-click).
 *  2. Otherwise the order is created as "pending payment" and the customer is
 *     redirected to the WooCommerce "Pay for order" page, which is fully
 *     pre-filled — a genuine one-click flow that works with every gateway.
 */
final class OrderProcessor {

	/**
	 * Process the upsell purchase.
	 *
	 * @param \WC_Order $parent_order The original order.
	 * @param array     $offer        Offer row (already validated).
	 * @return array|WP_Error Result array or WP_Error on failure.
	 */
	public function process( $parent_order, array $offer ) {
		$product = wc_get_product( $offer['product_id'] );

		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return new WP_Error( 'apg_invalid_product', __( 'This offer is no longer available.', 'after-purchase-goldmine' ) );
		}

		$price = OfferRepository::effective_price( $offer, $product );

		try {
			$order = $this->build_order( $parent_order, $product, $price, $offer );
		} catch ( \Exception $e ) {
			Logger::error( 'Failed to build upsell order: ' . $e->getMessage(), array( 'parent' => $parent_order->get_id() ) );
			return new WP_Error( 'apg_order_failed', __( 'We could not create your order. Please try again.', 'after-purchase-goldmine' ) );
		}

		// Attempt an off-session (zero-click) charge if a handler is available.
		$charged = $this->maybe_charge_offsession( $order, $parent_order );

		if ( true === $charged ) {
			$order->payment_complete();
			$order->add_order_note( __( 'Paid via After-Purchase Goldmine one-click upsell (off-session).', 'after-purchase-goldmine' ) );
			$order->save();

			OfferRepository::record_conversion( (int) $offer['id'], $price );

			return array(
				'status'      => 'paid',
				'order_id'    => $order->get_id(),
				'amount'      => $price,
				'amount_html' => wp_strip_all_tags( wc_price( $price ) ),
				'redirect'    => $order->get_checkout_order_received_url(),
			);
		}

		// Universal fallback: one-step pre-filled pay page.
		OfferRepository::record_conversion( (int) $offer['id'], $price );

		return array(
			'status'      => 'pay',
			'order_id'    => $order->get_id(),
			'amount'      => $price,
			'amount_html' => wp_strip_all_tags( wc_price( $price ) ),
			'pay_url'     => $order->get_checkout_payment_url(),
		);
	}

	/**
	 * Build the child order from the parent order.
	 *
	 * @param \WC_Order   $parent_order Parent order.
	 * @param \WC_Product $product      Product to add.
	 * @param float       $price        Effective unit price.
	 * @param array       $offer        Offer row.
	 * @return \WC_Order
	 * @throws \Exception When order creation fails.
	 */
	private function build_order( $parent_order, $product, $price, array $offer ) {
		$order = wc_create_order(
			array(
				'customer_id' => $parent_order->get_customer_id(),
				'created_via' => 'after-purchase-goldmine',
			)
		);

		if ( is_wp_error( $order ) ) {
			throw new \Exception( $order->get_error_message() );
		}

		// Copy billing & shipping from the parent order.
		$order->set_address( $parent_order->get_address( 'billing' ), 'billing' );
		$order->set_address( $parent_order->get_address( 'shipping' ), 'shipping' );

		if ( ! $order->get_billing_email() ) {
			$order->set_billing_email( $parent_order->get_billing_email() );
		}

		// Add the upsell product at the offer price.
		$order->add_product(
			$product,
			1,
			array(
				'subtotal' => $price,
				'total'    => $price,
			)
		);

		// Mirror the payment method from the parent for continuity.
		$order->set_payment_method( $parent_order->get_payment_method() );
		$order->set_payment_method_title( $parent_order->get_payment_method_title() );

		// Link metadata for reporting and support.
		$order->update_meta_data( '_apg_upsell', 'yes' );
		$order->update_meta_data( '_apg_parent_order', $parent_order->get_id() );
		$order->update_meta_data( '_apg_offer_id', (int) $offer['id'] );

		$order->set_customer_note( __( 'One-click upsell added from your previous order.', 'after-purchase-goldmine' ) );
		$order->calculate_totals();
		$order->update_status( 'pending', __( 'Created by After-Purchase Goldmine upsell.', 'after-purchase-goldmine' ) );
		$order->save();

		// Record the cross-reference on the parent order too.
		$children = (array) $parent_order->get_meta( '_apg_child_orders' );
		$children[] = $order->get_id();
		$parent_order->update_meta_data( '_apg_child_orders', array_values( array_unique( array_filter( $children ) ) ) );
		$parent_order->save();

		return $order;
	}

	/**
	 * Attempt an off-session charge through a registered gateway handler.
	 *
	 * Ships with no default handler (to avoid fragile, gateway-specific
	 * behavior). Premium gateway add-ons can hook `apg_offsession_charge`
	 * and return true to enable true zero-click charging.
	 *
	 * @param \WC_Order $order        New upsell order.
	 * @param \WC_Order $parent_order Parent order.
	 * @return bool|WP_Error True on success; false to use the pay-page fallback.
	 */
	private function maybe_charge_offsession( $order, $parent_order ) {
		$token = $this->resolve_token( $parent_order );

		if ( ! $token ) {
			return false;
		}

		// Store the token reference on the order for the handler.
		$order->update_meta_data( '_apg_payment_token_id', $token->get_id() );
		$order->add_payment_token( $token );
		$order->save();

		/**
		 * Filter to perform an off-session charge for the upsell order.
		 *
		 * Handlers should return boolean true on success, or false / WP_Error
		 * to fall back to the pre-filled pay page.
		 *
		 * @param bool|WP_Error          $result Default false (no handler).
		 * @param \WC_Order              $order  Upsell order.
		 * @param \WC_Order              $parent Parent order.
		 * @param \WC_Payment_Token      $token  Saved payment token.
		 */
		$result = apply_filters( 'apg_offsession_charge', false, $order, $parent_order, $token );

		if ( is_wp_error( $result ) ) {
			Logger::error( 'Off-session charge failed: ' . $result->get_error_message(), array( 'order' => $order->get_id() ) );
			return false;
		}

		return (bool) $result;
	}

	/**
	 * Resolve a saved payment token for the parent order's gateway.
	 *
	 * @param \WC_Order $parent_order Parent order.
	 * @return \WC_Payment_Token|null
	 */
	private function resolve_token( $parent_order ) {
		$customer_id = $parent_order->get_customer_id();
		$gateway     = $parent_order->get_payment_method();

		if ( ! $customer_id || ! $gateway ) {
			return null;
		}

		$tokens = \WC_Payment_Tokens::get_customer_tokens( $customer_id, $gateway );

		if ( empty( $tokens ) ) {
			return null;
		}

		// Prefer the default token, else the first available.
		foreach ( $tokens as $token ) {
			if ( $token->is_default() ) {
				return $token;
			}
		}

		return reset( $tokens );
	}
}
