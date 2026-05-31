<?php
/**
 * Admin settings template.
 *
 * @package APG
 *
 * @var array $settings All settings.
 */

defined( 'ABSPATH' ) || exit;

$g  = $settings['general'];
$u  = $settings['upsell'];
$dt = $settings['discount_timer'];
$r  = $settings['referral'];
$rv = $settings['review'];
$ss = $settings['social_share'];

$apg_networks = array(
	'x'        => 'X (Twitter)',
	'facebook' => 'Facebook',
	'whatsapp' => 'WhatsApp',
	'linkedin' => 'LinkedIn',
	'email'    => __( 'Email', 'after-purchase-goldmine' ),
	'copy'     => __( 'Copy link', 'after-purchase-goldmine' ),
);
?>
<div class="apg-app" data-apg-page="settings">
	<?php require __DIR__ . '/partials-topbar.php'; ?>

	<div class="apg-wrap">
		<div class="apg-pagehead">
			<div>
				<h1 class="apg-pagehead__title"><?php esc_html_e( 'Settings', 'after-purchase-goldmine' ); ?></h1>
				<p class="apg-pagehead__sub"><?php esc_html_e( 'Fine-tune appearance, copy and behavior for every module.', 'after-purchase-goldmine' ); ?></p>
			</div>
			<div class="apg-pagehead__actions">
				<button type="button" class="apg-btn apg-btn--primary apg-js-save-settings">
					<span class="apg-btn__label"><?php esc_html_e( 'Save changes', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-btn__spinner" aria-hidden="true"></span>
				</button>
			</div>
		</div>

		<div class="apg-settings">
			<aside class="apg-settings__nav">
				<button type="button" class="apg-tab is-active" data-tab="appearance"><?php esc_html_e( 'Appearance', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="upsell"><?php esc_html_e( 'Upsell', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="timer"><?php esc_html_e( 'Discount Timer', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="referral"><?php esc_html_e( 'Referral', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="review"><?php esc_html_e( 'Review', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="social"><?php esc_html_e( 'Social Share', 'after-purchase-goldmine' ); ?></button>
				<button type="button" class="apg-tab" data-tab="advanced"><?php esc_html_e( 'Advanced', 'after-purchase-goldmine' ); ?></button>
			</aside>

			<form class="apg-settings__body" id="apg-settings-form">

				<!-- Appearance -->
				<section class="apg-tabpanel is-active" data-panel="appearance">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Appearance', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Section title', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="general[title]" value="<?php echo esc_attr( $g['title'] ); ?>" />
							<p class="apg-help"><?php esc_html_e( 'Supports {site} and {first_name} merge tags.', 'after-purchase-goldmine' ); ?></p>
						</div>
						<div class="apg-field-row">
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
						</div>
						<div class="apg-field-row">
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Card corner radius', 'after-purchase-goldmine' ); ?></label>
								<input type="number" min="0" max="40" class="apg-input" name="general[card_radius]" value="<?php echo esc_attr( $g['card_radius'] ); ?>" />
							</div>
						</div>
						<label class="apg-checkrow">
							<input type="checkbox" name="general[glass]" <?php checked( $g['glass'] ); ?> />
							<span><?php esc_html_e( 'Enable glassmorphism blur effect', 'after-purchase-goldmine' ); ?></span>
						</label>
						<label class="apg-checkrow">
							<input type="checkbox" name="general[animations]" <?php checked( $g['animations'] ); ?> />
							<span><?php esc_html_e( 'Enable entrance animations', 'after-purchase-goldmine' ); ?></span>
						</label>
					</div>
				</section>

				<!-- Upsell -->
				<section class="apg-tabpanel" data-panel="upsell">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Upsell labels', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Accept button label', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="upsell[button_label]" value="<?php echo esc_attr( $u['button_label'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Decline link label', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="upsell[decline_label]" value="<?php echo esc_attr( $u['decline_label'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Success message', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="upsell[success_label]" value="<?php echo esc_attr( $u['success_label'] ); ?>" />
						</div>
						<p class="apg-help"><?php esc_html_e( 'Manage the products and discounts under the Upsell Offers tab.', 'after-purchase-goldmine' ); ?></p>
					</div>
				</section>

				<!-- Discount Timer -->
				<section class="apg-tabpanel" data-panel="timer">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Discount timer', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field-row">
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Discount type', 'after-purchase-goldmine' ); ?></label>
								<select class="apg-input" name="discount_timer[discount_type]">
									<option value="percent" <?php selected( $dt['discount_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'after-purchase-goldmine' ); ?></option>
									<option value="fixed" <?php selected( $dt['discount_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'after-purchase-goldmine' ); ?></option>
								</select>
							</div>
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Discount value', 'after-purchase-goldmine' ); ?></label>
								<input type="number" step="0.01" min="0" class="apg-input" name="discount_timer[discount_amount]" value="<?php echo esc_attr( $dt['discount_amount'] ); ?>" />
							</div>
						</div>
						<div class="apg-field-row">
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Expires after (hours)', 'after-purchase-goldmine' ); ?></label>
								<input type="number" min="1" max="720" class="apg-input" name="discount_timer[expiry_hours]" value="<?php echo esc_attr( $dt['expiry_hours'] ); ?>" />
							</div>
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Minimum spend', 'after-purchase-goldmine' ); ?></label>
								<input type="number" step="0.01" min="0" class="apg-input" name="discount_timer[min_spend]" value="<?php echo esc_attr( $dt['min_spend'] ); ?>" />
							</div>
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Headline', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="discount_timer[headline]" value="<?php echo esc_attr( $dt['headline'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Description', 'after-purchase-goldmine' ); ?></label>
							<textarea class="apg-input" name="discount_timer[description]" rows="2"><?php echo esc_textarea( $dt['description'] ); ?></textarea>
						</div>
						<label class="apg-checkrow">
							<input type="checkbox" name="discount_timer[email_coupon]" <?php checked( $dt['email_coupon'] ); ?> />
							<span><?php esc_html_e( 'Include the coupon in the order confirmation email', 'after-purchase-goldmine' ); ?></span>
						</label>
					</div>
				</section>

				<!-- Referral -->
				<section class="apg-tabpanel" data-panel="referral">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Referral program', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field-row">
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Referrer reward type', 'after-purchase-goldmine' ); ?></label>
								<select class="apg-input" name="referral[reward_type]">
									<option value="fixed" <?php selected( $r['reward_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'after-purchase-goldmine' ); ?></option>
									<option value="percent" <?php selected( $r['reward_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'after-purchase-goldmine' ); ?></option>
								</select>
							</div>
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Referrer reward value', 'after-purchase-goldmine' ); ?></label>
								<input type="number" step="0.01" min="0" class="apg-input" name="referral[reward_amount]" value="<?php echo esc_attr( $r['reward_amount'] ); ?>" />
							</div>
						</div>
						<div class="apg-field-row">
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Friend discount type', 'after-purchase-goldmine' ); ?></label>
								<select class="apg-input" name="referral[friend_discount_type]">
									<option value="fixed" <?php selected( $r['friend_discount_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'after-purchase-goldmine' ); ?></option>
									<option value="percent" <?php selected( $r['friend_discount_type'], 'percent' ); ?>><?php esc_html_e( 'Percentage', 'after-purchase-goldmine' ); ?></option>
								</select>
							</div>
							<div class="apg-field">
								<label class="apg-label"><?php esc_html_e( 'Friend discount value', 'after-purchase-goldmine' ); ?></label>
								<input type="number" step="0.01" min="0" class="apg-input" name="referral[friend_discount_amount]" value="<?php echo esc_attr( $r['friend_discount_amount'] ); ?>" />
							</div>
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Cookie lifetime (days)', 'after-purchase-goldmine' ); ?></label>
							<input type="number" min="1" max="365" class="apg-input" name="referral[cookie_days]" value="<?php echo esc_attr( $r['cookie_days'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Headline', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="referral[headline]" value="<?php echo esc_attr( $r['headline'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Description', 'after-purchase-goldmine' ); ?></label>
							<textarea class="apg-input" name="referral[description]" rows="2"><?php echo esc_textarea( $r['description'] ); ?></textarea>
						</div>
					</div>
				</section>

				<!-- Review -->
				<section class="apg-tabpanel" data-panel="review">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Review request', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Headline', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="review[headline]" value="<?php echo esc_attr( $rv['headline'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Description', 'after-purchase-goldmine' ); ?></label>
							<textarea class="apg-input" name="review[description]" rows="2"><?php echo esc_textarea( $rv['description'] ); ?></textarea>
						</div>
					</div>
				</section>

				<!-- Social -->
				<section class="apg-tabpanel" data-panel="social">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Social share', 'after-purchase-goldmine' ); ?></h2>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Headline', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="social_share[headline]" value="<?php echo esc_attr( $ss['headline'] ); ?>" />
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Description', 'after-purchase-goldmine' ); ?></label>
							<textarea class="apg-input" name="social_share[description]" rows="2"><?php echo esc_textarea( $ss['description'] ); ?></textarea>
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Share text', 'after-purchase-goldmine' ); ?></label>
							<input type="text" class="apg-input" name="social_share[share_text]" value="<?php echo esc_attr( $ss['share_text'] ); ?>" />
							<p class="apg-help"><?php esc_html_e( 'Supports the {site} merge tag.', 'after-purchase-goldmine' ); ?></p>
						</div>
						<div class="apg-field">
							<label class="apg-label"><?php esc_html_e( 'Enabled networks', 'after-purchase-goldmine' ); ?></label>
							<div class="apg-checkgrid">
								<?php foreach ( $apg_networks as $apg_net_key => $apg_net_label ) : ?>
									<label class="apg-checkrow">
										<input type="checkbox" name="social_share[networks][]" value="<?php echo esc_attr( $apg_net_key ); ?>" <?php checked( in_array( $apg_net_key, (array) $ss['networks'], true ) ); ?> />
										<span><?php echo esc_html( $apg_net_label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</section>

				<!-- Advanced -->
				<section class="apg-tabpanel" data-panel="advanced">
					<div class="apg-panel">
						<h2 class="apg-panel__title"><?php esc_html_e( 'Advanced', 'after-purchase-goldmine' ); ?></h2>
						<label class="apg-checkrow">
							<input type="checkbox" name="general[delete_data_on_uninstall]" <?php checked( $g['delete_data_on_uninstall'] ); ?> />
							<span><?php esc_html_e( 'Delete all plugin data when the plugin is uninstalled', 'after-purchase-goldmine' ); ?></span>
						</label>
						<p class="apg-help"><?php esc_html_e( 'When disabled, your offers, analytics and settings are kept even if you remove the plugin.', 'after-purchase-goldmine' ); ?></p>
					</div>
				</section>

			</form>
		</div>
	</div>
</div>
