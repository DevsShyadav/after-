<?php
/**
 * Review request module.
 *
 * @package APG
 */

namespace APG\Modules\Review;

use APG\Modules\AbstractModule;
use APG\Support\Helpers;
use APG\Support\Options;

defined( 'ABSPATH' ) || exit;

/**
 * Prompts the customer to review the products they purchased.
 */
final class ReviewModule extends AbstractModule {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return 'review';
	}

	/**
	 * {@inheritDoc}
	 */
	public function title() {
		return __( 'Review Request', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return __( 'Capture reviews at the peak satisfaction moment, right after purchase.', 'after-purchase-goldmine' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M11.5 3.5 14 8.6l5.6.8-4 4 1 5.6-5-2.7-5 2.7 1-5.6-4-4 5.6-.8z"/></svg>';
	}

	/**
	 * Build the reviewable product list for an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return array[] List of products with name, image, url.
	 */
	private function products( $order ) {
		$products = array();
		$seen     = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			$pid = $product->get_id();

			if ( isset( $seen[ $pid ] ) || ! comments_open( $pid ) ) {
				continue;
			}

			$seen[ $pid ] = true;

			$image_id = $product->get_image_id();

			$products[] = array(
				'id'    => $pid,
				'name'  => $product->get_name(),
				'image' => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_gallery_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
				'url'   => trailingslashit( $product->get_permalink() ) . '#tab-reviews',
			);
		}

		return $products;
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( $order ) {
		$products = $this->products( $order );

		if ( empty( $products ) ) {
			return '';
		}

		return Helpers::get_template(
			'thankyou/review.php',
			array(
				'order'       => $order,
				'products'    => $products,
				'headline'    => Options::get( 'review', 'headline' ),
				'description' => Options::get( 'review', 'description' ),
			)
		);
	}
}
