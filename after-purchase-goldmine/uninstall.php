<?php
/**
 * Uninstall handler for After-Purchase Goldmine.
 *
 * Only destroys data when the merchant explicitly opted in via settings.
 *
 * @package APG
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$apg_settings = get_option( 'apg_settings', array() );
$apg_delete   = is_array( $apg_settings ) && ! empty( $apg_settings['general']['delete_data_on_uninstall'] );

if ( ! $apg_delete ) {
	return;
}

global $wpdb;

// Drop custom tables.
$apg_tables = array(
	$wpdb->prefix . 'apg_offers',
	$wpdb->prefix . 'apg_events',
	$wpdb->prefix . 'apg_referrals',
	$wpdb->prefix . 'apg_coupons',
);

foreach ( $apg_tables as $apg_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$apg_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// Remove options.
delete_option( 'apg_settings' );
delete_option( 'apg_version' );
delete_option( 'apg_onboarding_complete' );

// Remove transients.
delete_transient( 'apg_activation_redirect' );
delete_transient( 'apg_dashboard_stats' );

// Clear scheduled events.
wp_clear_scheduled_hook( 'apg_daily_maintenance' );
