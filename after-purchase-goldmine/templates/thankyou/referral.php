<?php
/**
 * Referral program card template.
 *
 * @package APG
 *
 * @var \WC_Order $order
 * @var string    $code
 * @var string    $share_url
 * @var string    $headline
 * @var string    $description
 * @var string    $reward_label
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="apg-card apg-card--referral" data-module="referral">
	<div class="apg-card__icon">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v9H4v-9"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
	</div>

	<h3 class="apg-card__title"><?php echo esc_html( $headline ); ?></h3>

	<?php if ( '' !== trim( (string) $description ) ) : ?>
		<p class="apg-card__text"><?php echo esc_html( $description ); ?></p>
	<?php endif; ?>

	<div class="apg-referral__link">
		<input type="text" readonly class="apg-referral__input" value="<?php echo esc_url( $share_url ); ?>" aria-label="<?php esc_attr_e( 'Your referral link', 'after-purchase-goldmine' ); ?>" />
		<button type="button" class="apg-btn apg-btn--primary apg-js-copy" data-clipboard="<?php echo esc_url( $share_url ); ?>">
			<?php esc_html_e( 'Copy link', 'after-purchase-goldmine' ); ?>
		</button>
	</div>

	<div class="apg-referral__share">
		<a class="apg-share-pill apg-share-pill--x" target="_blank" rel="noopener nofollow" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?url=' . rawurlencode( $share_url ) ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2H21.5l-7.5 8.57L23 22h-6.594l-5.165-6.75L5.34 22H2.08l8.02-9.166L1 2h6.76l4.67 6.17L18.244 2Zm-1.157 18h1.832L7.01 3.88H5.04L17.087 20Z"/></svg>
		</a>
		<a class="apg-share-pill apg-share-pill--fb" target="_blank" rel="noopener nofollow" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $share_url ) ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
		</a>
		<a class="apg-share-pill apg-share-pill--wa" target="_blank" rel="noopener nofollow" href="<?php echo esc_url( 'https://api.whatsapp.com/send?text=' . rawurlencode( $share_url ) ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.4 15.18L2 22l4.95-1.3A9.9 9.9 0 1 0 12.04 2Zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.34-.5.05-1.12.24-3.66-.78-3.09-1.25-5.06-4.42-5.21-4.62-.15-.2-1.25-1.66-1.25-3.17 0-1.5.79-2.24 1.07-2.55.28-.3.61-.38.81-.38.2 0 .41 0 .58.01.19.01.44-.07.69.53.24.6.83 2.07.9 2.22.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.45.52-.15.15-.3.31-.13.6.17.3.76 1.25 1.63 2.03 1.12 1 2.06 1.31 2.36 1.46.3.15.47.13.64-.08.17-.2.74-.86.94-1.16.2-.3.4-.25.67-.15.27.1 1.73.82 2.02.97.3.15.5.22.57.35.07.13.07.72-.17 1.4Z"/></svg>
		</a>
		<a class="apg-share-pill apg-share-pill--in" target="_blank" rel="noopener nofollow" href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $share_url ) ); ?>">
			<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5A2.5 2.5 0 1 0 5 8.5a2.5 2.5 0 0 0-.02-5ZM3 9h4v12H3zM9 9h3.8v1.64h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.4c0-1.29-.02-2.95-1.8-2.95-1.8 0-2.08 1.4-2.08 2.85V21H9z"/></svg>
		</a>
	</div>

	<?php if ( '' !== trim( (string) $reward_label ) ) : ?>
		<div class="apg-referral__reward">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M2 12h20"/><circle cx="12" cy="12" r="10"/></svg>
			<?php
			printf(
				/* translators: %s: reward label, e.g. $10. */
				esc_html__( 'You earn %s for every friend who orders.', 'after-purchase-goldmine' ),
				'<strong>' . esc_html( $reward_label ) . '</strong>'
			);
			?>
		</div>
	<?php endif; ?>
</article>
