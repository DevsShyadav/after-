<?php
/**
 * Review request card template.
 *
 * @package APG
 *
 * @var \WC_Order $order
 * @var array[]   $products  id, name, image, url
 * @var string    $headline
 * @var string    $description
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="apg-card apg-card--review" data-module="review">
	<div class="apg-card__icon">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m11.5 3.3 2.4 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9 5.5-.8z"/></svg>
	</div>

	<h3 class="apg-card__title"><?php echo esc_html( $headline ); ?></h3>

	<?php if ( '' !== trim( (string) $description ) ) : ?>
		<p class="apg-card__text"><?php echo esc_html( $description ); ?></p>
	<?php endif; ?>

	<ul class="apg-review__list">
		<?php foreach ( $products as $product ) : ?>
			<li class="apg-review__item">
				<img class="apg-review__thumb" src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['name'] ); ?>" loading="lazy" />
				<span class="apg-review__name"><?php echo esc_html( $product['name'] ); ?></span>
				<a class="apg-btn apg-btn--soft apg-js-track" data-module="review" href="<?php echo esc_url( $product['url'] ); ?>">
					<span class="apg-review__stars" aria-hidden="true">★★★★★</span>
					<?php esc_html_e( 'Review', 'after-purchase-goldmine' ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</article>
