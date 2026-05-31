<?php
/**
 * Onboarding wizard template.
 *
 * @package APG
 *
 * @var array $settings All settings.
 * @var array $modules  Module metadata list.
 */

defined( 'ABSPATH' ) || exit;

$g = $settings['general'];
?>
<div class="apg-app apg-app--wizard" data-apg-page="onboarding">
	<div class="apg-wizard" id="apg-wizard">
		<div class="apg-wizard__aside">
			<div class="apg-wizard__brand">
				<span class="apg-logo apg-logo--lg">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 2.9 6.26L21.5 9l-5 4.6L18 21l-6-3.3L6 21l1.5-7.4-5-4.6 6.6-.74z"/></svg>
				</span>
				<h1><?php esc_html_e( 'After-Purchase Goldmine', 'after-purchase-goldmine' ); ?></h1>
				<p><?php esc_html_e( 'Turn your thank-you page into your most profitable page in under two minutes.', 'after-purchase-goldmine' ); ?></p>
			</div>
			<ul class="apg-wizard__steps">
				<li class="is-active" data-step-dot="1"><span>1</span><?php esc_html_e( 'Welcome', 'after-purchase-goldmine' ); ?></li>
				<li data-step-dot="2"><span>2</span><?php esc_html_e( 'Modules', 'after-purchase-goldmine' ); ?></li>
				<li data-step-dot="3"><span>3</span><?php esc_html_e( 'Branding', 'after-purchase-goldmine' ); ?></li>
				<li data-step-dot="4"><span>4</span><?php esc_html_e( 'Reward', 'after-purchase-goldmine' ); ?></li>
				<li data-step-dot="5"><span>5</span><?php esc_html_e( 'Finish', 'after-purchase-goldmine' ); ?></li>
			</ul>
		</div>

		<form class="apg-wizard__main" id="apg-wizard-form">

			<section class="apg-step is-active" data-step="1">
				<h2><?php esc_html_e( 'Welcome aboard 👋', 'after-purchase-goldmine' ); ?></h2>
				<p class="apg-step__lead"><?php esc_html_e( 'Your customers trust you most right after they buy. Let\'s use that moment to grow revenue, reviews and referrals — automatically.', 'after-purchase-goldmine' ); ?></p>
				<ul class="apg-feature-list">
					<li><?php esc_html_e( 'One-click upsells with no re-entered payment', 'after-purchase-goldmine' ); ?></li>
					<li><?php esc_html_e( 'Automatic next-purchase discount coupons', 'after-purchase-goldmine' ); ?></li>
					<li><?php esc_html_e( 'Built-in referral program and social sharing', 'after-purchase-goldmine' ); ?></li>
				</ul>
			</section>

			<section class="apg-step" data-step="2">
				<h2><?php esc_html_e( 'Choose your modules', 'after-purchase-goldmine' ); ?></h2>
				<p class="apg-step__lead"><?php esc_html_e( 'Enable everything for maximum impact — you can change this anytime.', 'after-purchase-goldmine' ); ?></p>
				<div class="apg-wizard__modules">
					<?php foreach ( $modules as $apg_mod ) : ?>
						<label class="apg-pickcard">
							<input type="checkbox" name="modules[<?php echo esc_attr( $apg_mod['id'] ); ?>]" <?php checked( $apg_mod['enabled'] ); ?> />
							<span class="apg-pickcard__icon"><?php echo $apg_mod['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="apg-pickcard__title"><?php echo esc_html( $apg_mod['title'] ); ?></span>
							<span class="apg-pickcard__desc"><?php echo esc_html( $apg_mod['description'] ); ?></span>
							<span class="apg-pickcard__check" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="apg-step" data-step="3">
				<h2><?php esc_html_e( 'Match your brand', 'after-purchase-goldmine' ); ?></h2>
				<p class="apg-step__lead"><?php esc_html_e( 'Pick a theme and accent color. The cards will adapt instantly.', 'after-purchase-goldmine' ); ?></p>
				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Theme', 'after-purchase-goldmine' ); ?></label>
					<select class="apg-input" name="general[theme]">
						<option value="auto" <?php selected( $g['theme'], 'auto' ); ?>><?php esc_html_e( 'Auto (match device)', 'after-purchase-goldmine' ); ?></option>
						<option value="light" <?php selected( $g['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'after-purchase-goldmine' ); ?></option>
						<option value="dark" <?php selected( $g['theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'after-purchase-goldmine' ); ?></option>
					</select>
				</div>
				<div class="apg-field">
					<label class="apg-label"><?php esc_html_e( 'Accent color', 'after-purchase-goldmine' ); ?></label>
					<div class="apg-colorpick">
						<input type="color" name="general[accent]" value="<?php echo esc_attr( $g['accent'] ); ?>" />
						<input type="text" class="apg-input apg-js-accent-text" value="<?php echo esc_attr( $g['accent'] ); ?>" />
					</div>
				</div>
			</section>

			<section class="apg-step" data-step="4">
				<h2><?php esc_html_e( 'Set your comeback reward', 'after-purchase-goldmine' ); ?></h2>
				<p class="apg-step__lead"><?php esc_html_e( 'Every buyer gets a unique, expiring coupon for their next order.', 'after-purchase-goldmine' ); ?></p>
				<div class="apg-field-row">
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Discount type', 'after-purchase-goldmine' ); ?></label>
						<select class="apg-input" name="discount_timer[discount_type]">
							<option value="percent" <?php selected( $settings['discount_timer']['discount_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'after-purchase-goldmine' ); ?></option>
							<option value="fixed" <?php selected( $settings['discount_timer']['discount_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'after-purchase-goldmine' ); ?></option>
						</select>
					</div>
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Value', 'after-purchase-goldmine' ); ?></label>
						<input type="number" step="0.01" min="0" class="apg-input" name="discount_timer[discount_amount]" value="<?php echo esc_attr( $settings['discount_timer']['discount_amount'] ); ?>" />
					</div>
					<div class="apg-field">
						<label class="apg-label"><?php esc_html_e( 'Expires (hours)', 'after-purchase-goldmine' ); ?></label>
						<input type="number" min="1" max="720" class="apg-input" name="discount_timer[expiry_hours]" value="<?php echo esc_attr( $settings['discount_timer']['expiry_hours'] ); ?>" />
					</div>
				</div>
			</section>

			<section class="apg-step" data-step="5">
				<div class="apg-finish">
					<div class="apg-finish__check">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
					</div>
					<h2><?php esc_html_e( 'You\'re ready to earn more', 'after-purchase-goldmine' ); ?></h2>
					<p class="apg-step__lead"><?php esc_html_e( 'Place a test order to preview your new thank-you page, then create your first upsell offer.', 'after-purchase-goldmine' ); ?></p>
				</div>
			</section>

			<footer class="apg-wizard__foot">
				<button type="button" class="apg-btn apg-btn--ghost apg-js-wizard-back" hidden><?php esc_html_e( 'Back', 'after-purchase-goldmine' ); ?></button>
				<div class="apg-wizard__foot-right">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apg-dashboard' ) ); ?>" class="apg-btn apg-btn--ghost"><?php esc_html_e( 'Skip', 'after-purchase-goldmine' ); ?></a>
					<button type="button" class="apg-btn apg-btn--primary apg-js-wizard-next"><?php esc_html_e( 'Continue', 'after-purchase-goldmine' ); ?></button>
					<button type="button" class="apg-btn apg-btn--primary apg-js-wizard-finish" hidden>
						<span class="apg-btn__label"><?php esc_html_e( 'Finish setup', 'after-purchase-goldmine' ); ?></span>
						<span class="apg-btn__spinner" aria-hidden="true"></span>
					</button>
				</div>
			</footer>
		</form>
	</div>
</div>
