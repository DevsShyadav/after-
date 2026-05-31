<?php
/**
 * One-click upsell card template.
 *
 * @package APG
 *
 * @var \WC_Order   $order
 * @var array       $offer
 * @var \WC_Product $product
 * @var string      $headline
 * @var string      $description
 * @var string      $image_url
 * @var float       $regular_price
 * @var float       $price
 * @var float       $savings
 * @var string      $button_label
 * @var string      $decline_label
 */

defined( 'ABSPATH' ) || exit;

$apg_has_discount = $savings > 0;
?>
<article class="apg-card apg-card--upsell" data-module="upsell"
	data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"
	data-order-key="<?php echo esc_attr( $order->get_order_key() ); ?>"
	data-offer-id="<?php echo esc_attr( $offer['id'] ); ?>">

	<div class="apg-card__badge apg-card__badge--hot">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2s4 4 4 8a4 4 0 0 1-8 0c0-1.5.5-2.5.5-2.5"/><path d="M12 13a3 3 0 0 0-3 3c0 2 3 6 3 6s3-4 3-6a3 3 0 0 0-3-3z"/></svg>
		<?php esc_html_e( 'Exclusive one-time offer', 'after-purchase-goldmine' ); ?>
	</div>

	<div class="apg-upsell">
		<div class="apg-upsell__media">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
			<?php if ( $apg_has_discount ) : ?>
				<span class="apg-upsell__save">
					<?php
					printf(
						/* translators: %s: amount saved. */
						esc_html__( 'Save %s', 'after-purchase-goldmine' ),
						wp_kses_post( wc_price( $savings ) )
					);
					?>
				</span>
			<?php endif; ?>
		</div>

		<div class="apg-upsell__body">
			<h3 class="apg-card__title"><?php echo esc_html( $headline ); ?></h3>

			<?php if ( '' !== trim( (string) $description ) ) : ?>
				<p class="apg-card__text"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<div class="apg-upsell__price">
				<span class="apg-upsell__price-now"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
				<?php if ( $apg_has_discount ) : ?>
					<span class="apg-upsell__price-was"><?php echo wp_kses_post( wc_price( $regular_price ) ); ?></span>
				<?php endif; ?>
			</div>

			<div class="apg-upsell__actions">
				<button type="button" class="apg-btn apg-btn--primary apg-js-upsell-accept">
					<span class="apg-btn__label"><?php echo esc_html( $button_label ); ?></span>
					<span class="apg-btn__spinner" aria-hidden="true"></span>
				</button>
				<button type="button" class="apg-btn apg-btn--ghost apg-js-upsell-decline"><?php echo esc_html( $decline_label ); ?></button>
			</div>

			<p class="apg-upsell__trust">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
				<?php esc_html_e( 'Secure one-click checkout — no need to re-enter your details.', 'after-purchase-goldmine' ); ?>
			</p>

			<div class="apg-upsell__result" hidden></div>
		</div>
	</div>
</article>
