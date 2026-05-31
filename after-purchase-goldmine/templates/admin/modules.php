<?php
/**
 * Admin modules management template.
 *
 * @package APG
 *
 * @var array $modules  Module metadata list (ordered).
 * @var array $settings All settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="apg-app" data-apg-page="modules">
	<?php require __DIR__ . '/partials-topbar.php'; ?>

	<div class="apg-wrap">
		<div class="apg-pagehead">
			<div>
				<h1 class="apg-pagehead__title"><?php esc_html_e( 'Modules', 'after-purchase-goldmine' ); ?></h1>
				<p class="apg-pagehead__sub"><?php esc_html_e( 'Toggle modules on or off and drag to set the order they appear on the thank-you page.', 'after-purchase-goldmine' ); ?></p>
			</div>
		</div>

		<div class="apg-modules" id="apg-modules-list">
			<?php foreach ( $modules as $apg_mod ) : ?>
				<div class="apg-module" data-module="<?php echo esc_attr( $apg_mod['id'] ); ?>" draggable="true">
					<span class="apg-module__handle" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/></svg>
					</span>

					<span class="apg-module__icon"><?php echo $apg_mod['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>

					<div class="apg-module__body">
						<h3 class="apg-module__title"><?php echo esc_html( $apg_mod['title'] ); ?></h3>
						<p class="apg-module__desc"><?php echo esc_html( $apg_mod['description'] ); ?></p>
					</div>

					<label class="apg-switch">
						<input type="checkbox" class="apg-js-toggle-module" data-module="<?php echo esc_attr( $apg_mod['id'] ); ?>" <?php checked( $apg_mod['enabled'] ); ?> />
						<span class="apg-switch__track"><span class="apg-switch__thumb"></span></span>
					</label>
				</div>
			<?php endforeach; ?>
		</div>

		<p class="apg-hint">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
			<?php esc_html_e( 'Configure each module on the Settings page. Upsell offers are managed under Upsell Offers.', 'after-purchase-goldmine' ); ?>
		</p>
	</div>
</div>
