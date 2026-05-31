<?php
/**
 * Thank-you modules wrapper template.
 *
 * @package APG
 *
 * @var \WC_Order $order The order.
 * @var string    $cards Rendered card HTML.
 * @var string    $title Section title.
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="apg-goldmine" data-apg-theme="<?php echo esc_attr( \APG\Support\Options::get( 'general', 'theme', 'auto' ) ); ?>">
	<div class="apg-goldmine__inner">
		<?php if ( '' !== trim( (string) $title ) ) : ?>
			<header class="apg-goldmine__header">
				<span class="apg-goldmine__eyebrow">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.2l1-5.8L3.5 9.2l5.9-.9z"/></svg>
					<?php esc_html_e( 'Just for you', 'after-purchase-goldmine' ); ?>
				</span>
				<h2 class="apg-goldmine__title"><?php echo esc_html( $title ); ?></h2>
			</header>
		<?php endif; ?>

		<div class="apg-goldmine__grid">
			<?php
			// $cards is composed of trusted, individually-escaped module templates.
			echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</section>
