<?php
/**
 * Admin dashboard template.
 *
 * @package APG
 *
 * @var array $stats    Analytics payload.
 * @var array $modules  Module metadata list.
 * @var array $settings All settings.
 */

defined( 'ABSPATH' ) || exit;

$apg_currency = get_woocommerce_currency_symbol();
$apg_revenue  = wp_strip_all_tags( wc_price( $stats['revenue'] ) );

$apg_module_labels = array(
	'upsell'         => __( 'Upsell', 'after-purchase-goldmine' ),
	'discount_timer' => __( 'Discount Timer', 'after-purchase-goldmine' ),
	'referral'       => __( 'Referral', 'after-purchase-goldmine' ),
	'review'         => __( 'Review', 'after-purchase-goldmine' ),
	'social_share'   => __( 'Social Share', 'after-purchase-goldmine' ),
);
?>
<div class="apg-app" data-apg-page="dashboard">
	<?php require __DIR__ . '/partials-topbar.php'; ?>

	<div class="apg-wrap">
		<div class="apg-pagehead">
			<div>
				<h1 class="apg-pagehead__title"><?php esc_html_e( 'Dashboard', 'after-purchase-goldmine' ); ?></h1>
				<p class="apg-pagehead__sub"><?php esc_html_e( 'Your thank-you page performance at a glance.', 'after-purchase-goldmine' ); ?></p>
			</div>
			<div class="apg-pagehead__actions">
				<span class="apg-chip"><?php esc_html_e( 'Last 30 days', 'after-purchase-goldmine' ); ?></span>
			</div>
		</div>

		<div class="apg-stats">
			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--green">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Extra revenue', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value"><?php echo wp_kses_post( $apg_revenue ); ?></span>
				</div>
			</div>

			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--accent">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Conversions', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value" data-apg-count="<?php echo esc_attr( $stats['conversions'] ); ?>">0</span>
				</div>
			</div>

			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--blue">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Impressions', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value" data-apg-count="<?php echo esc_attr( $stats['impressions'] ); ?>">0</span>
				</div>
			</div>

			<div class="apg-stat">
				<div class="apg-stat__icon apg-stat__icon--amber">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m23 6-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
				</div>
				<div class="apg-stat__body">
					<span class="apg-stat__label"><?php esc_html_e( 'Conversion rate', 'after-purchase-goldmine' ); ?></span>
					<span class="apg-stat__value"><?php echo esc_html( $stats['conv_rate'] ); ?>%</span>
				</div>
			</div>
		</div>

		<div class="apg-grid-2">
			<div class="apg-panel">
				<div class="apg-panel__head">
					<h2 class="apg-panel__title"><?php esc_html_e( 'Revenue trend', 'after-purchase-goldmine' ); ?></h2>
				</div>
				<canvas id="apg-chart-revenue" height="260"></canvas>
			</div>

			<div class="apg-panel">
				<div class="apg-panel__head">
					<h2 class="apg-panel__title"><?php esc_html_e( 'Conversions by module', 'after-purchase-goldmine' ); ?></h2>
				</div>
				<canvas id="apg-chart-modules" height="260"></canvas>
			</div>
		</div>

		<div class="apg-panel">
			<div class="apg-panel__head">
				<h2 class="apg-panel__title"><?php esc_html_e( 'Module breakdown', 'after-purchase-goldmine' ); ?></h2>
			</div>
			<div class="apg-table-wrap">
				<table class="apg-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Module', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Impressions', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Conversions', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Conv. rate', 'after-purchase-goldmine' ); ?></th>
							<th><?php esc_html_e( 'Revenue', 'after-purchase-goldmine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['by_module'] as $apg_mid => $apg_row ) : ?>
							<?php
							$apg_imp  = (int) $apg_row['impression'];
							$apg_conv = (int) $apg_row['conversion'];
							$apg_rate = $apg_imp > 0 ? round( ( $apg_conv / $apg_imp ) * 100, 1 ) : 0;
							?>
							<tr>
								<td class="apg-table__name"><?php echo esc_html( isset( $apg_module_labels[ $apg_mid ] ) ? $apg_module_labels[ $apg_mid ] : $apg_mid ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $apg_imp ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $apg_conv ) ); ?></td>
								<td><?php echo esc_html( $apg_rate ); ?>%</td>
								<td><?php echo wp_kses_post( wc_price( $apg_row['revenue'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<script type="application/json" id="apg-dashboard-data">
		<?php echo wp_json_encode( $stats ); ?>
	</script>
</div>
