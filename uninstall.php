<?php
/**
 * Uninstall routine: remove all plugin data.
 *
 * @package WP_Shield_Lite
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'wpsl_settings' );
delete_option( 'wpsl_version' );

// Drop the activity-log table.
$table = $wpdb->prefix . 'wpsl_activity_log';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// Clean up any lingering lockout/attempt transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpsl_att_%' OR option_name LIKE '_transient_timeout_wpsl_att_%' OR option_name LIKE '_transient_wpsl_lock_%' OR option_name LIKE '_transient_timeout_wpsl_lock_%'" );
