<?php
/**
 * Shared admin top navigation bar.
 *
 * @package APG
 */

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$apg_current = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'apg-dashboard';

$apg_nav = array(
	'apg-dashboard' => array( __( 'Dashboard', 'after-purchase-goldmine' ), 'M3 13h8V3H3v10Zm0 8h8v-6H3v6Zm10 0h8V11h-8v10Zm0-18v6h8V3h-8Z' ),
	'apg-modules'   => array( __( 'Modules', 'after-purchase-goldmine' ), 'M4 4h7v7H4zM13 4h7v7h-7zM13 13h7v7h-7zM4 13h7v7H4z' ),
	'apg-offers'    => array( __( 'Upsell Offers', 'after-purchase-goldmine' ), 'M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0l-7.59-7.6a2 2 0 0 1-.58-1.41V4a2 2 0 0 1 2-2h7.59a2 2 0 0 1 1.41.58l7.17 7.17a2 2 0 0 1 0 2.83zM7.5 7.5h.01' ),
	'apg-referrals' => array( __( 'Referrals', 'after-purchase-goldmine' ), 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87' ),
	'apg-settings'  => array( __( 'Settings', 'after-purchase-goldmine' ), 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z' ),
);
?>
<header class="apg-topbar">
	<div class="apg-topbar__brand">
		<span class="apg-logo">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 2.9 6.26L21.5 9l-5 4.6L18 21l-6-3.3L6 21l1.5-7.4-5-4.6 6.6-.74z"/></svg>
		</span>
		<span class="apg-topbar__name"><?php esc_html_e( 'Goldmine', 'after-purchase-goldmine' ); ?></span>
		<span class="apg-topbar__pill"><?php esc_html_e( 'PRO', 'after-purchase-goldmine' ); ?></span>
	</div>

	<nav class="apg-topbar__nav">
		<?php foreach ( $apg_nav as $apg_slug => $apg_item ) : ?>
			<a
				class="apg-navlink <?php echo $apg_current === $apg_slug ? 'is-active' : ''; ?>"
				href="<?php echo esc_url( admin_url( 'admin.php?page=' . $apg_slug ) ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?php echo esc_attr( $apg_item[1] ); ?>"/></svg>
				<span><?php echo esc_html( $apg_item[0] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="apg-topbar__right">
		<a class="apg-navlink apg-navlink--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=apg-onboarding' ) ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
			<span><?php esc_html_e( 'Setup', 'after-purchase-goldmine' ); ?></span>
		</a>
	</div>
</header>
<div class="apg-toast" id="apg-toast" hidden></div>
