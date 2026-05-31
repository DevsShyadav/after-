<?php
/**
 * Next-purchase discount timer card template.
 *
 * @package APG
 *
 * @var \WC_Order $order
 * @var array     $coupon       code, amount, type, expires_at, expires_ts
 * @var string    $amount_text
 * @var string    $headline
 * @var string    $description
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="apg-card apg-card--timer" data-module="discount_timer">
	<div class="apg-timer__head">
		<div class="apg-timer__discount"><?php echo esc_html( $amount_text ); ?></div>
		<div class="apg-timer__heading">
			<h3 class="apg-card__title"><?php echo esc_html( $headline ); ?></h3>
			<?php if ( '' !== trim( (string) $description ) ) : ?>
				<p class="apg-card__text"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="apg-timer__coupon">
		<button type="button" class="apg-coupon apg-js-copy" data-clipboard="<?php echo esc_attr( $coupon['code'] ); ?>">
			<span class="apg-coupon__code"><?php echo esc_html( $coupon['code'] ); ?></span>
			<span class="apg-coupon__copy">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
				<span class="apg-coupon__copy-label"><?php esc_html_e( 'Copy', 'after-purchase-goldmine' ); ?></span>
			</span>
		</button>
	</div>

	<?php if ( ! empty( $coupon['expires_ts'] ) ) : ?>
		<div class="apg-timer__countdown apg-js-countdown" data-expires="<?php echo esc_attr( $coupon['expires_ts'] ); ?>">
			<div class="apg-timer__segment"><span class="apg-timer__num" data-unit="days">00</span><span class="apg-timer__lbl"><?php esc_html_e( 'days', 'after-purchase-goldmine' ); ?></span></div>
			<div class="apg-timer__segment"><span class="apg-timer__num" data-unit="hours">00</span><span class="apg-timer__lbl"><?php esc_html_e( 'hrs', 'after-purchase-goldmine' ); ?></span></div>
			<div class="apg-timer__segment"><span class="apg-timer__num" data-unit="minutes">00</span><span class="apg-timer__lbl"><?php esc_html_e( 'min', 'after-purchase-goldmine' ); ?></span></div>
			<div class="apg-timer__segment"><span class="apg-timer__num" data-unit="seconds">00</span><span class="apg-timer__lbl"><?php esc_html_e( 'sec', 'after-purchase-goldmine' ); ?></span></div>
		</div>
	<?php endif; ?>
</article>
