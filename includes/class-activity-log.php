<?php
/**
 * Security activity log.
 *
 * @package WP_Shield_Lite
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPSL_Activity_Log
 *
 * Records security-relevant events into a dedicated table.
 */
class WPSL_Activity_Log {

	/**
	 * Table name without prefix.
	 */
	const TABLE = 'wpsl_activity_log';

	/**
	 * Full prefixed table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create the log table on activation.
	 *
	 * @return void
	 */
	public static function install_table() {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			logged_at DATETIME NOT NULL,
			event_type VARCHAR(40) NOT NULL,
			username VARCHAR(190) NOT NULL DEFAULT '',
			ip VARCHAR(45) NOT NULL DEFAULT '',
			message TEXT NOT NULL,
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY logged_at (logged_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register hooks (no-op placeholder kept for symmetry with other modules).
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Insert a log row.
	 *
	 * @param string $event_type Machine event key.
	 * @param string $message    Human readable message.
	 * @param string $username   Related username, if any.
	 * @return void
	 */
	public function record( $event_type, $message, $username = '' ) {
		if ( ! WPSL_Settings::get( 'activity_log' ) ) {
			return;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			self::table_name(),
			array(
				'logged_at'  => current_time( 'mysql' ),
				'event_type' => substr( sanitize_text_field( $event_type ), 0, 40 ),
				'username'   => substr( sanitize_text_field( $username ), 0, 190 ),
				'ip'         => WPSL_Plugin::get_client_ip(),
				'message'    => sanitize_text_field( $message ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Fetch a page of log rows, newest first.
	 *
	 * @param int $per_page Rows per page.
	 * @param int $page     1-based page number.
	 * @return array
	 */
	public function get_entries( $per_page = 50, $page = 1 ) {
		global $wpdb;

		$per_page = max( 1, absint( $per_page ) );
		$page     = max( 1, absint( $page ) );
		$offset   = ( $page - 1 ) * $per_page;
		$table    = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset )
		);
	}

	/**
	 * Total number of log rows.
	 *
	 * @return int
	 */
	public function count_entries() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Delete every log row.
	 *
	 * @return void
	 */
	public function clear() {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
