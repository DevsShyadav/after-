<?php
/**
 * One-click upsell module.
 *
 * @package APG
 */

namespace APG\Modules\Upsell;

use APG\Modules\AbstractModule;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Renders and powers the one-click upsell card.
 */
final class UpsellModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'upsell';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title() {
		return __( 'One-Click Upsell', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Offer a complementary product the customer can buy in one click — no re-entering payment.', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>';
	}

	/**
	 * Resolve the offer that should be shown for an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return array|null
	 */
	public function resolve_offer( $order ) {
		return OfferRepository::find_for_order( $order );
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $order ) {
		$offer = $this->resolve_offer( $order );

		if ( empty( $offer ) ) {
			return '';
		}

		$product = wc_get_product( $offer['product_id'] );

		if ( ! $product ) {
			return '';
		}

		OfferRepository::record_impression( (int) $offer['id'] );

		$regular = (float) wc_get_price_to_display( $product );
		$price   = OfferRepository::effective_price( $offer, $product );
		$savings = max( 0, $regular - $price );

		$image_id  = $offer['image_id'] ? (int) $offer['image_id'] : $product->get_image_id();
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );

		$headline = '' !== $offer['headline'] ? $offer['headline'] : $product->get_name();

		return Helpers::get_template(
			'thankyou/upsell.php',
			array(
				'order'         => $order,
				'offer'         => $offer,
				'product'       => $product,
				'headline'      => $headline,
				'description'   => $offer['description'],
				'image_url'     => $image_url,
				'regular_price' => $regular,
				'price'         => $price,
				'savings'       => $savings,
				'button_label'  => Options::get( 'upsell', 'button_label' ),
				'decline_label' => Options::get( 'upsell', 'decline_label' ),
			)
		);
	}
}
