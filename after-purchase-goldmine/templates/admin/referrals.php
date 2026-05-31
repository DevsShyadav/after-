<?php
/**
 * Admin referrals template.
 *
 * @package APG
 *
 * @var array $referrals List of referral conversion rows.
 * @var array $stats     Referral stats.
 * @var array $settings  All settings.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="apg-app" data-apg-page="referrals">
	<?php require __DIR__ . '/partials-topbar.php'; ?>

	<div class="apg-wrap">
		<div class="apg-pagehead">
			<div>
				<h1 class="apg-pagehead__title"><?php esc_html_e( 'Referrals', 'after-purchase-goldmine' ); ?></h1>
				<p class="apg-pagehead__sub"><?php esc_html_e( 'Track ambassadors, conversions and rewards from your referral program.', 'after-purchase-goldmine' ); ?></p>
			</div>
		</div>

		<div class="apg-stats">
			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--accent">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Ambassadors', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value" data-apg-count="<?php echo esc_attr( $stats['ambassadors'] ); ?>">0</span>
				</div>
			</div>
			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--green">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Conversions', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value" data-apg-count="<?php echo esc_attr( $stats['conversions'] ); ?>">0</span>
				</div>
			</div>
			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--amber">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Rewards issued', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value" data-apg-count="<?php echo esc_attr( $stats['rewards'] ); ?>">0</span>
				</div>
			</div>
			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--blue">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Referred revenue', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value"><?php echo wp_kses_post( wc_price( $stats['revenue'] ) ); ?></span>
				</div>
			</div>
		</div>

		<div class="apg-panel">
			<div class="apg-panel__head">
				<h2 class="apg-panel__title"><?php esc_html_e( 'Recent conversions', 'after-purchase-goldmine' ); ?></h2>
			</div>
			<div class="apg-table-wrap">
				<table class="apg-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Referrer', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Referred order', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Order total', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Reward', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Status', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Date', 'after-purchase-goldmine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $referrals ) ) : ?>
							<tr class="apg-empty-row">
								<td colspan="6">
									<div class="apg-empty">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/></svg>
										<h3><?php esc_html_e( 'No referrals yet', 'after-purchase-goldmine' ); ?></h3>
										<p><?php esc_html_e( 'Once customers share their link and friends order, conversions appear here.', 'after-purchase-goldmine' ); ?></p>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $referrals as $apg_ref ) : ?>
								<tr>
									<td class="apg-table__name"><?php echo esc_html( $apg_ref['referrer_email'] ); ?></td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $apg_ref['referred_order_id'] . '&action=edit' ) ); ?>">
											#<?php echo esc_html( $apg_ref['referred_order_id'] ); ?>
										</a>
									</td>
									<td><?php echo wp_kses_post( wc_price( $apg_ref['order_total'] ) ); ?></td>
									<td><?php echo $apg_ref['reward_coupon'] ? '<code>' . esc_html( $apg_ref['reward_coupon'] ) . '</code>' : '—'; ?></td>
									<td>
										<span class="apg-tag apg-tag--<?php echo 'issued' === $apg_ref['reward_status'] ? 'on' : 'pending'; ?>">
											<?php echo esc_html( ucfirst( $apg_ref['reward_status'] ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $apg_ref['created_at'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
